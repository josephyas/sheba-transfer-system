<?php

namespace App\Jobs;

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

class ProcessTransferApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requestId;
    protected $status;
    protected $note;

    public function __construct(string $requestId, string $status, ?string $note = null)
    {
        $this->requestId = $requestId;
        $this->status = $status;
        $this->note = $note;
    }

    public function handle(): array
    {
        Log::info('Processing transfer approval/cancellation', [
            'request_id' => $this->requestId,
            'status' => $this->status
        ]);

        $shebaRequest = ShebaRequest::find($this->requestId);

        if (!$shebaRequest) {
            Log::error('Sheba request not found', ['request_id' => $this->requestId]);
            throw new InvalidRequestException('Sheba request not found');
        }

        if ($shebaRequest->status !== 'pending') {
            Log::error('Sheba request is not in pending status', [
                'request_id' => $this->requestId,
                'current_status' => $shebaRequest->status
            ]);
            throw new InvalidRequestException('Sheba request is not in pending status');
        }

        return DB::transaction(function () use ($shebaRequest) {
            if ($this->status === 'confirmed') {
                $this->confirmRequest($shebaRequest);
            } else if ($this->status === 'canceled') {
                $this->cancelRequest($shebaRequest);
            }

            Log::info('Transfer status updated successfully', [
                'request_id' => $this->requestId,
                'status' => $this->status
            ]);

            return [
                'id' => $shebaRequest->id,
                'price' => $shebaRequest->price,
                'status' => $shebaRequest->status,
                'fromShebaNumber' => $shebaRequest->from_sheba_number,
                'ToShebaNumber' => $shebaRequest->to_sheba_number,
                'createdAt' => $shebaRequest->created_at->toIso8601String()
            ];
        });
    }

    /**
     * Confirm a Sheba request.
     */
    private function confirmRequest(ShebaRequest $shebaRequest): void
    {
        $destAccount = Account::where('sheba_number', $shebaRequest->to_sheba_number)->first();

        if ($destAccount) {
            DB::table('accounts')
                ->where('id', $destAccount->id)
                ->update([
                    'balance' => DB::raw("balance + {$shebaRequest->price}")
                ]);

            Transaction::create([
                'account_id' => $destAccount->id,
                'sheba_request_id' => $shebaRequest->id,
                'type' => 'credit',
                'amount' => $shebaRequest->price,
                'note' => $shebaRequest->note ?? 'Transfer credit'
            ]);
        }

        $shebaRequest->status = 'confirmed';
        $shebaRequest->confirmed_at = now();
        $shebaRequest->save();
    }

    private function cancelRequest(ShebaRequest $shebaRequest): void
    {
        $sourceAccount = Account::where('sheba_number', $shebaRequest->from_sheba_number)->first();

        if ($sourceAccount) {
            DB::table('accounts')
                ->where('id', $sourceAccount->id)
                ->update([
                    'balance' => DB::raw("balance + {$shebaRequest->price}")
                ]);

            Transaction::create([
                'account_id' => $sourceAccount->id,
                'sheba_request_id' => $shebaRequest->id,
                'type' => 'refund',
                'amount' => $shebaRequest->price,
                'note' => $this->note ?? 'Transfer refund'
            ]);
        }

        $shebaRequest->status = 'canceled';
        $shebaRequest->canceled_at = now();
        $shebaRequest->cancellation_note = $this->note;
        $shebaRequest->save();
    }
}
