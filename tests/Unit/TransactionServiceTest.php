<?php
use App\Models\Account;
use App\Models\Transaction;
use App\Service\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['fees.transfer' => 5, 'fees.withdraw' => 3]);
    $this->service = new TransactionService();
});

it('realiza um depósito com sucesso', function () {
    $account = Account::factory()->create(['balance' => 100]);

    $this->service->processSingleTransaction([
        'type' => 'DEPOSIT',
        'sourceAccount' => $account->id,
        'amount' => 50,
    ]);

    expect($account->fresh()->balance)->toBe(150);
    expect(Transaction::count())->toBe(1);
});

it('realiza um saque com sucesso', function () {
    $account = Account::factory()->create(['balance' => 100, 'credit_limit' => 50]);

    $this->service->processSingleTransaction([
        'type' => 'WITHDRAW',
        'sourceAccount' => $account->id,
        'amount' => 50,
    ]);

    expect($account->fresh()->balance)->toBe(47);
    expect(Transaction::count())->toBe(1);
});

it('impede saque acima do saldo e limite de crédito', function () {
    $account = Account::factory()->create(['balance' => 100, 'credit_limit' => 50]);

    $this->service->processSingleTransaction([
        'type' => 'WITHDRAW',
        'sourceAccount' => $account->id,
        'amount' => 200,
    ]);
})->throws(ValidationException::class, 'fundos insuficientes');

it('realiza uma transferência com sucesso', function () {
    $source = Account::factory()->create(['balance' => 200, 'credit_limit' => 50]);
    $target = Account::factory()->create(['balance' => 50]);

    $this->service->processSingleTransaction([
        'type' => 'TRANSFER',
        'sourceAccount' => $source->id,
        'targetAccount' => $target->id,
        'amount' => 100,
    ]);

    expect($source->fresh()->balance)->toBe(95);
    expect($target->fresh()->balance)->toBe(150);
    expect(Transaction::count())->toBe(2);
});

it('impede transferência acima do saldo e limite de crédito', function () {
    $source = Account::factory()->create(['balance' => 100, 'credit_limit' => 50]);
    $target = Account::factory()->create(['balance' => 50]);

    $this->service->processSingleTransaction([
        'type' => 'TRANSFER',
        'sourceAccount' => $source->id,
        'targetAccount' => $target->id,
        'amount' => 200,
    ]);
})->throws(ValidationException::class, 'fundos insuficientes');

it('impede transferência para a mesma conta', function () {
    $account = Account::factory()->create(['balance' => 200]);

    $this->service->processSingleTransaction([
        'type' => 'TRANSFER',
        'sourceAccount' => $account->id,
        'targetAccount' => $account->id,
        'amount' => 50,
    ]);
})->throws(ValidationException::class, 'não pode ser a mesma que a conta de destino');

it('lança exceção para tipo de transação inválido', function () {
    $account = Account::factory()->create();

    $this->service->processSingleTransaction([
        'type' => 'INVALID',
        'sourceAccount' => $account->id,
        'amount' => 50,
    ]);
})->throws(ValidationException::class, 'Tipo de transação inválido');
