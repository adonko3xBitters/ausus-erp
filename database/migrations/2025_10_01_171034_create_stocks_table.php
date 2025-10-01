<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 20, 2)->default(0);
            $table->decimal('reserved_quantity', 20, 2)->default(0); // Quantité réservée (commandes)
            $table->decimal('available_quantity', 20, 2)->default(0); // Quantité disponible
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'product_variant_id']);
            $table->index(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
