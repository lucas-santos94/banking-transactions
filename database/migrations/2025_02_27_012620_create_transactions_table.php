<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('parent_transaction_id')->nullable();
            $table->enum('type', ['DEPOSIT', 'WITHDRAW', 'TRANSFER_IN', 'TRANSFER_OUT'])->default('DEPOSIT');
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('fee')->default(0);
            $table->bigInteger('balance_after')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('parent_transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
