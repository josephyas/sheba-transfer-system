<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\ProcessTransferApproval;
use App\Jobs\ProcessTransferRequest;
use App\Models\Account;
use App\Models\ShebaRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShebaService
{
    public function getAllRequests( ?string $status = null ): array
    {
        $cacheKey = 'sheba_requests_' . ( $status ?? 'all' );

        return Cache::remember( $cacheKey, 3, function () use ( $status ) {
            $query = ShebaRequest::query();

            if ( $status ) {
                $query->where( 'status', $status );
            }

            $requests = $query->orderBy( 'created_at', 'asc' )->get();

            return $requests->map( function ( $request ) {
                return [
                    'id'              => $request->id,
                    'price'           => $request->price,
                    'status'          => $request->status,
                    'fromShebaNumber' => $request->from_sheba_number,
                    'ToShebaNumber'   => $request->to_sheba_number,
                    'createdAt'       => $request->created_at->toIso8601String()
                ];
            } )->toArray();
        } );
    }

    /**
     * @throws InvalidRequestException
     * @throws InsufficientBalanceException
     */
    public function createRequest( int $price, string $fromShebaNumber, string $toShebaNumber, ?string $note = null, ?string $idempotencyKey = null ): array
    {
        if ( $idempotencyKey ) {
            $existingTransfer = ShebaRequest::where( 'idempotency_key', $idempotencyKey )->first();

            if ( $existingTransfer ) {
                Log::info( 'Duplicate request detected using idempotency key', [
                    'idempotency_key' => $idempotencyKey,
                    'transfer_id'     => $existingTransfer->id
                ] );

                return [
                    'id'              => $existingTransfer->id,
                    'price'           => $existingTransfer->price,
                    'status'          => $existingTransfer->status,
                    'fromShebaNumber' => $existingTransfer->from_sheba_number,
                    'ToShebaNumber'   => $existingTransfer->to_sheba_number,
                    'createdAt'       => $existingTransfer->created_at->toIso8601String()
                ];
            }
        }

        $job = new ProcessTransferRequest( $price, $fromShebaNumber, $toShebaNumber, $note, $idempotencyKey );
        return $job->handle();
    }

    public function updateRequestStatus( string $requestId, string $status, ?string $note = null ): array
    {
        $lock = Cache::lock( 'transfer-lock-' . $requestId, 10 );

        if ( $lock->get() ) {
            try {
                $job = new ProcessTransferApproval( $requestId, $status, $note );

                return $job->handle();

            } finally {
                $lock->release();
            }
        }

        Log::warning( 'Could not acquire lock for transfer', [ 'id' => $requestId ] );
        throw new InvalidRequestException( 'Transfer is currently being processed by another request' );

    }

    private function confirmRequest( ShebaRequest $shebaRequest ): void
    {
        $destAccount = Account::where( 'sheba_number', $shebaRequest->to_sheba_number )->first();

        if ( $destAccount ) {
            DB::table( 'accounts' )
                ->where( 'id', $destAccount->id )
                ->update( [
                    'balance' => DB::raw( "balance + {$shebaRequest->price}" )
                ] );

            Transaction::create( [
                'account_id'       => $destAccount->id,
                'sheba_request_id' => $shebaRequest->id,
                'type'             => 'credit',
                'amount'           => $shebaRequest->price,
                'note'             => $shebaRequest->note ?? 'Transfer credit'
            ] );
        }

        $shebaRequest->status = 'confirmed';
        $shebaRequest->confirmed_at = now();
        $shebaRequest->save();
    }

    private function cancelRequest( ShebaRequest $shebaRequest, ?string $note ): void
    {
        $sourceAccount = Account::where( 'sheba_number', $shebaRequest->from_sheba_number )->first();

        if ( $sourceAccount ) {
            DB::table( 'accounts' )
                ->where( 'id', $sourceAccount->id )
                ->update( [
                    'balance' => DB::raw( "balance + {$shebaRequest->price}" )
                ] );

            Transaction::create( [
                'account_id'       => $sourceAccount->id,
                'sheba_request_id' => $shebaRequest->id,
                'type'             => 'refund',
                'amount'           => $shebaRequest->price,
                'note'             => $note ?? 'Transfer refund'
            ] );
        }

        $shebaRequest->status = 'canceled';
        $shebaRequest->canceled_at = now();
        $shebaRequest->cancellation_note = $note;
        $shebaRequest->save();
    }

    private function clearCaches(): void
    {
        Cache::forget( 'sheba_requests_all' );
        Cache::forget( 'sheba_requests_pending' );
        Cache::forget( 'sheba_requests_completed' );
        Cache::forget( 'sheba_requests_canceled' );
    }
}
