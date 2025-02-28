<?php

namespace App\Http\Controllers;

use App\Jobs\TransactionJob;
use App\Service\TransactionService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function transaction(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array|min:1',
            'transactions.*.type' => 'required|string|in:DEPOSIT,WITHDRAW,TRANSFER',
            'transactions.*.sourceAccount' => 'required|string',
            'transactions.*.targetAccount' => [
                'required_if:transactions.*.type,TRANSFER',
                'string'
            ],
            'transactions.*.amount' => 'required|integer|min:1',
        ]);

        foreach ($request->transactions as $transaction) {
            TransactionJob::dispatch($transaction);
        }

        return response()->json(['message' => 'As transações serão processadas em segundo plano'], 200);
    }
}
