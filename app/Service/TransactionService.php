<?php

namespace App\Service;

use App\Models\Account;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionService
{

    protected int $transferFee;
    protected int $withdrawFee;

    protected array $transactionMethods = [
        'DEPOSIT' => 'executeDeposit',
        'WITHDRAW' => 'executeWithdraw',
        'TRANSFER' => 'executeTransfer',
    ];

    public function __construct()
    {
        $this->transferFee = config('fees.transfer');
        $this->withdrawFee = config('fees.withdraw');
    }

    public function processSingleTransaction(array $transaction)
    {
        DB::transaction(function () use ($transaction) {
            try {
                $type = $transaction['type'];
                $method = $this->transactionMethods[$type];
            } catch (Exception $e) {
                $errorMessage = "Tipo de transação inválido: {$type}";
                Log::error($errorMessage);
                throw ValidationException::withMessages(['message' => $errorMessage]);
            }

            $sourceAccount = Account::where("id", $transaction['sourceAccount'])
                ->lockForUpdate()
                ->firstOrFail();

            return $this->{$method}($sourceAccount, $transaction);

        }, 3);
    }

    private function executeDeposit(Account $account, array $transaction)
    {
        $account->increment('balance', $transaction['amount']);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'DEPOSIT',
            'amount' => $transaction['amount'],
            'fee' => 0,
            'balance_after' => $account->balance,
            'description' => "Deposito realizado com sucesso",
        ]);

        if ($transaction) {
            Log::info("Depósito na conta {$account->number} realizado com sucesso");
        }
        return $transaction;
    }

    private function executeWithdraw(Account $account, array $transaction)
    {
        $availableBalance = $account->balance + $account->credit_limit;

        if (($availableBalance < ($transaction['amount'] + $this->withdrawFee))) {
            $messageError = "Saque na conta {$account->number} não realizado por motivo de fundos insuficientes";
            Log::error($messageError);
            throw ValidationException::withMessages(['message' => $messageError]);
        }

        $account->decrement('balance', $transaction['amount'] + $this->withdrawFee);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => 'WITHDRAW',
            'amount' => $transaction['amount'],
            'fee' => $this->withdrawFee,
            'balance_after' => $account->balance,
            'description' => 'Saque realizado com sucesso',
        ]);

        if ($transaction) {
            Log::info("Saque na conta {$account->number} realizado com sucesso");
        }
        return $transaction;
    }

    private function executeTransfer(Account $sourceAccount, array $transaction)
    {
        $targetAccount = Account::where('id', $transaction['targetAccount'])
            ->lockForUpdate()
            ->firstOrFail();

        if ($sourceAccount->number == $targetAccount->number) {
            $messageError = "A conta de origem {$sourceAccount->number} não pode ser a mesma que a conta de destino {$targetAccount->number} para uma transferência";
            Log::error($messageError);
            throw ValidationException::withMessages(['message' => $messageError]);
        }

        $availableBalance = $sourceAccount->balance + $sourceAccount->credit_limit;

        if (($availableBalance < ($transaction['amount'] + $this->withdrawFee))) {
            $messageError = "Transferência da conta {$sourceAccount->number} para a conta {$targetAccount->number} não realizada por motivo de fundos insuficientes";
            Log::error($messageError);
            throw ValidationException::withMessages(['message' => $messageError]);
        }

        $sourceAccount->decrement('balance', $transaction['amount'] + $this->transferFee);
        $targetAccount->increment('balance', $transaction['amount']);

        $parentTransaction = Transaction::create([
            'account_id' => $sourceAccount->id,
            'type' => 'TRANSFER_OUT',
            'amount' => $transaction['amount'],
            'fee' => $this->withdrawFee,
            'balance_after' => $sourceAccount->balance,
            'description' => 'Transaferência realizada',
        ]);

        $childTransaction = Transaction::create([
            'account_id' => $targetAccount->id,
            'parent_transaction_id' => $parentTransaction->id,
            'type' => 'TRANSFER_IN',
            'amount' => $transaction['amount'],
            'fee' => 0,
            'balance_after' => $targetAccount->balance,
            'description' => 'Transferência recebida',
        ]);

        if ($parentTransaction && $childTransaction) {
            Log::info("Transferência da conta {$sourceAccount->number} para a conta {$targetAccount->number} realizada com sucesso");
        }

        return $parentTransaction;
    }
}
