<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Espèces, Virement, Chèque, Mobile Money...
            $table->string('code')->unique(); // CASH, TRANSFER, CHECK, MOBILE
            $table->text('description')->nullable();
            $table->foreignId('default_account_id')->nullable()->constrained('accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
