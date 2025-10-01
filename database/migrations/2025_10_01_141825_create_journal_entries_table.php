<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique(); // Ex: VT-2025-001
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reference')->nullable(); // N° facture, paiement...
            $table->text('description');
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');

            // Montants totaux (calculés automatiquement)
            $table->decimal('total_debit', 20, 2)->default(0);
            $table->decimal('total_credit', 20, 2)->default(0);

            // Source de l'écriture (polymorphique)
            $table->nullableMorphs('entryable'); // Ex: Invoice, Payment, Expense

            // Traçabilité
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('entry_number');
            $table->index('date');
            $table->index('status');
            $table->index(['entryable_type', 'entryable_id'], 'idx_entryable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
