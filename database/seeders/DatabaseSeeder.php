<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accountId = Account::factory()->create([
            'id' => Str::uuid(),
            'number' => 100001,
            'balance' => 50000,
            'credit_limit' => 10000,
        ]);

        Transaction::factory()->create([
            'id' => Str::uuid(),
            'account_id' => $accountId,
            'type' => 'DEPOSIT',
            'amount' => 50000,
            'fee' => 0,
            'balance_after' => 50000,
            'description' => 'Depósito inicial de 500,00 BRL',
        ]);

        $accountId = Account::factory()->create([
            'id' => Str::uuid(),
            'number' => 100002,
            'balance' => 75000,
            'credit_limit' => 15000,
        ]);

        Transaction::factory()->create([
            'id' => Str::uuid(),
            'account_id' => $accountId,
            'type' => 'DEPOSIT',
            'amount' => 75000,
            'fee' => 0,
            'balance_after' => 75000,
            'description' => 'Depósito inicial de 750,00 BRL',
        ]);
    }
}
