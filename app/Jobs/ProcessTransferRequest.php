<?php

namespace App\Jobs;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidRequestException;
use App\Models\Account;
use App\Models\ShebaRequest;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessTransferRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $price;
    protected $fromShebaNumber;
    protected $toShebaNumber;
    protected $note;
    protected $idempotencyKey;
    private   $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct( string $requestId, int $price, string $fromShebaNumber, string $toShebaNumber, ?string $note = null, ?string $idempotencyKey = null )
    {
        $this->requestId = $requestId;
        $this->price = $price;
        $this->fromShebaNumber = $fromShebaNumber;
        $this->toShebaNumber = $toShebaNumber;
        $this->note = $note;
        $this->idempotencyKey = $idempotencyKey;
    }

    /**
     * Execute the job.
     * @throws InvalidRequestException
     */
    public function handle(): array
    {
        Log::info( 'Processing transfer request', [
            'fromSheba'       => $this->fromShebaNumber,
            'toSheba'         => $this->toShebaNumber,
            'amount'          => $this->price,
            'idempotency_key' => $this->idempotencyKey
        ] );

        $sourceAccount = Account::where( 'sheba_number', $this->fromShebaNumber )->first();

        if ( !$sourceAccount ) {
            Log::error( 'Source account not found', [ 'sheba' => $this->fromShebaNumber ] );
            throw new InvalidRequestException( 'Source account not found' );
        }

        return DB::transaction( function () use ( $sourceAccount ) {
            $updated = DB::table( 'accounts' )
                ->where( 'id', $sourceAccount->id )
                ->where( 'balance', '>=', $this->price )
                ->update( [
                    'balance' => DB::raw( "balance - {$this->price}" )
                ] );

            if ( !$updated ) {
                Log::error( 'Insufficient balance', [
                    'account'  => $sourceAccount->id,
                    'balance'  => $sourceAccount->balance,
                    'required' => $this->price
                ] );
                throw new InsufficientBalanceException( 'Insufficient balance for this transfer' );
            }

            $shebaRequest = new ShebaRequest();
            $shebaRequest->id = $this->requestId;
            $shebaRequest->price = $this->price;
            $shebaRequest->from_sheba_number = $this->fromShebaNumber;
            $shebaRequest->to_sheba_number = $this->toShebaNumber;
            $shebaRequest->note = $this->note;
            $shebaRequest->status = 'pending';
            $shebaRequest->idempotency_key = $this->idempotencyKey;
            $shebaRequest->save();

            Transaction::create( [
                'account_id'       => $sourceAccount->id,
                'sheba_request_id' => $this->requestId,
                'type'             => 'debit',
                'amount'           => $this->price,
                'note'             => $this->note ?? 'Transfer deduction'
            ] );

            Log::info( 'Transfer request processed successfully', [
                'request_id' => $this->requestId,
                'status'     => 'pending'
            ] );

            return [
                'id'              => $this->requestId,
                'price'           => $this->price,
                'status'          => 'pending',
                'fromShebaNumber' => $this->fromShebaNumber,
                'ToShebaNumber'   => $this->toShebaNumber,
                'createdAt'       => $shebaRequest->created_at->toIso8601String()
            ];
        } );
    }
}
