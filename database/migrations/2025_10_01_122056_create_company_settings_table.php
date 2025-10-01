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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('legal_form')->nullable(); // SARL, SA, SAS...
            $table->string('tax_number')->nullable(); // Numéro IFU ou NIF
            $table->string('trade_register')->nullable(); // RCCM
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('country_id')->constrained();
            $table->foreignId('currency_id')->constrained(); // Devise de base
            $table->string('logo')->nullable();
            $table->string('fiscal_regime')->nullable(); // Réel Normal, Réel Simplifié, Synthèse
            $table->date('fiscal_year_end')->nullable(); // Date de clôture habituelle
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
