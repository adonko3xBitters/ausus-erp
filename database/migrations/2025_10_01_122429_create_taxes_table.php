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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // TVA 18%, AIB 1%...
            $table->string('code')->unique(); // TVA_18, AIB_1
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('rate', 8, 4); // 18.0000 pour 18%
            $table->text('description')->nullable();
            $table->foreignId('tax_account_id')->nullable()->constrained('accounts');
            $table->boolean('is_compound')->default(false); // Taxe composée
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
