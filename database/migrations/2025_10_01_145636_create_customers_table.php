<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique(); // CLI-001
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('tax_number')->nullable(); // NIF/IFU
            $table->string('trade_register')->nullable(); // RCCM
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->foreignId('billing_country_id')->nullable()->constrained('countries');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->foreignId('shipping_country_id')->nullable()->constrained('countries');
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('account_id')->nullable()->constrained('accounts'); // Compte auxiliaire
            $table->integer('payment_terms')->default(30); // Jours
            $table->decimal('credit_limit', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_number');
            $table->index('name');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
