<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique(); // FACT-FRS-2025-001
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->date('bill_date');
            $table->date('due_date');
            $table->string('reference')->nullable(); // N° facture fournisseur
            $table->text('notes')->nullable();

            // Montants
            $table->decimal('subtotal', 20, 2)->default(0); // HT
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0); // TVA
            $table->decimal('total', 20, 2)->default(0); // TTC
            $table->decimal('amount_paid', 20, 2)->default(0);
            $table->decimal('amount_due', 20, 2)->default(0); // Reste à payer

            // Devise
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate', 20, 6)->default(1);

            // Statut
            $table->enum('status', [
                'draft',      // Brouillon
                'received',   // Reçue
                'partial',    // Partiellement payée
                'paid',       // Payée
                'overdue',    // En retard
                'cancelled'   // Annulée
            ])->default('draft');

            // Dates
            $table->timestamp('received_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('bill_number');
            $table->index('vendor_id');
            $table->index('bill_date');
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
