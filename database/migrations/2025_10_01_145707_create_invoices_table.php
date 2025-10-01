<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // FAC-2025-001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('reference')->nullable(); // N° commande client
            $table->text('terms')->nullable(); // Conditions de paiement
            $table->text('notes')->nullable();
            $table->text('footer')->nullable();

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
                'sent',       // Envoyée
                'viewed',     // Vue par le client
                'partial',    // Partiellement payée
                'paid',       // Payée
                'overdue',    // En retard
                'cancelled'   // Annulée
            ])->default('draft');

            // Dates importantes
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
