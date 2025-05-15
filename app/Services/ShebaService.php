<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidRequestException;
use App\Models\Account;
use App\Models\ShebaRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShebaService
{
    public function getAllRequests(): array
    {
        $requests = ShebaRequest::orderBy( 'created_at', 'asc' )->get();

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
    }

    /**
     * @throws InvalidRequestException
     * @throws InsufficientBalanceException
     */
    public function createRequest( int $price, string $fromShebaNumber, string $toShebaNumber, ?string $note = null ): array
    {
        $sourceAccount = Account::where( 'sheba_number', $fromShebaNumber )->first();

        if ( !$sourceAccount ) {
            throw new InvalidRequestException( 'Source account not found' );
        }

        if ( $sourceAccount->balance < $price ) {
            throw new InsufficientBalanceException( 'Insufficient balance for this transfer' );
        }

        return DB::transaction( function () use ( $price, $fromShebaNumber, $toShebaNumber, $note, $sourceAccount ) {
            $updated = DB::table( 'accounts' )
                ->where( 'id', $sourceAccount->id )
                ->where( 'balance', '>=', $price )
                ->update( [
                    'balance' => DB::raw( "balance - $price" )
                ] );

            if ( !$updated ) {
                throw new InsufficientBalanceException( 'Insufficient balance for this transfer' );
            }

            $requestId = (string)Str::uuid();
            $shebaRequest = new ShebaRequest();
            $shebaRequest->id = $requestId;
            $shebaRequest->price = $price;
            $shebaRequest->from_sheba_number = $fromShebaNumber;
            $shebaRequest->to_sheba_number = $toShebaNumber;
            $shebaRequest->note = $note;
            $shebaRequest->status = 'pending';
            $shebaRequest->save();

            Transaction::create( [
                'account_id'       => $sourceAccount->id,
                'sheba_request_id' => $requestId,
                'type'             => 'debit',
                'amount'           => $price,
                'note'             => $note ?? 'Transfer deduction'
            ] );

            return [
                'id'              => $requestId,
                'price'           => $price,
                'status'          => 'pending',
                'fromShebaNumber' => $fromShebaNumber,
                'ToShebaNumber'   => $toShebaNumber,
                'createdAt'       => $shebaRequest->created_at->toIso8601String()
            ];
        } );
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
}
