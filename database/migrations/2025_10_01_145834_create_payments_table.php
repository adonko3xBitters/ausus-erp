<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // PAY-2025-001
            $table->nullableMorphs('paymentable'); // Invoice ou Bill
            $table->date('payment_date');
            $table->decimal('amount', 20, 2);
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('account_id')->constrained('accounts'); // Compte bancaire/caisse
            $table->string('reference')->nullable(); // N° transaction, chèque...
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_number');
            $table->index(['paymentable_type', 'paymentable_id'], 'idx_paymentable');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
