<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // VT, AC, BQ, CA, OD
            $table->string('name'); // Journal des ventes, achats, etc.
            $table->enum('type', [
                'sales',      // Ventes
                'purchases',  // Achats
                'bank',       // Banque
                'cash',       // Caisse
                'general',    // Opérations diverses
            ]);
            $table->text('description')->nullable();
            $table->foreignId('default_debit_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_credit_account_id')->nullable()->constrained('accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
