<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'account_id',
        'parent_transaction_id',
        'type',
        'amount',
        'fee',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'balance_after' => 'integer',
        'type' => 'string',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function childTransactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }
}
