<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique(); // DEP-2025-001
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 20, 2);
            $table->foreignId('tax_id')->nullable()->constrained();
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2); // Montant TTC
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->foreignId('payment_method_id')->nullable()->constrained();
            $table->foreignId('account_id')->nullable()->constrained('accounts'); // Compte bancaire/caisse
            $table->string('reference')->nullable(); // N° facture, reçu...
            $table->text('description');
            $table->json('attachments')->nullable(); // Fichiers justificatifs
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');

            // Approbation
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Paiement
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_number');
            $table->index('expense_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
