<?php

use App\Jobs\TransactionJob;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);
beforeEach(function () {
    Queue::fake(); // Evita que os jobs sejam realmente enfileirados
});

it('processa um depósito com sucesso', function () {
    $account = Account::factory()->create(['balance' => 5000]);

    $response = $this->postJson('/api/account/transaction', [
        'transactions' => [
            [
                'type' => 'DEPOSIT',
                'sourceAccount' => $account->id,
                'amount' => 1000,
            ],
        ],
    ]);

    $response->assertStatus(200);
    Queue::assertPushed(TransactionJob::class);
});

it('processa um saque com sucesso', function () {
    $account = Account::factory()->create([
        'balance' => 5000,
        'credit_limit' => 1000,
    ]);

    $response = $this->postJson('/api/account/transaction', [
        'transactions' => [
            [
                'type' => 'WITHDRAW',
                'sourceAccount' => $account->id,
                'amount' => 2000,
            ],
        ],
    ]);

    $response->assertStatus(200);
    Queue::assertPushed(TransactionJob::class);
});

it('processa uma transferência entre contas com sucesso', function () {
    $sourceAccount = Account::factory()->create(['balance' => 5000]);
    $targetAccount = Account::factory()->create(['balance' => 2000]);

    $response = $this->postJson('/api/account/transaction', [
        'transactions' => [
            [
                'type' => 'TRANSFER',
                'sourceAccount' => $sourceAccount->id,
                'targetAccount' => $targetAccount->id,
                'amount' => 1000,
            ],
        ],
    ]);

    $response->assertStatus(200);
    Queue::assertPushed(TransactionJob::class);
});
