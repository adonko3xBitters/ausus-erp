<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->text('description')->nullable();

            // Pour les comptes auxiliaires (clients/fournisseurs)
            $table->nullableMorphs('transactionable'); // Ex: Customer, Vendor

            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index(['transactionable_type', 'transactionable_id'], 'idx_transactionable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
