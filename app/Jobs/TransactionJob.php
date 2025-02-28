<?php

namespace App\Jobs;

use App\Service\TransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TransactionJob implements ShouldQueue
{
    use Queueable;

    public $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(array $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transactionService = new TransactionService();
        $transactionService->processSingleTransaction($this->transaction);
    }
}
