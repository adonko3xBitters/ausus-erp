<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number')->unique(); // MVT-2025-001
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer']); // Entrée, Sortie, Ajustement, Transfert
            $table->decimal('quantity', 20, 2);
            $table->decimal('cost_per_unit', 20, 2)->default(0); // Coût unitaire
            $table->decimal('total_cost', 20, 2)->default(0); // Coût total
            $table->date('movement_date');
            $table->string('reference')->nullable(); // N° document source
            $table->text('notes')->nullable();

            // Source du mouvement (polymorphique)
            $table->nullableMorphs('movable'); // Invoice, Bill, StockAdjustment...

            // Transfert entre entrepôts
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('movement_number');
            $table->index('movement_date');
            $table->index('type');
            $table->index(['movable_type', 'movable_id'], 'idx_movable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
