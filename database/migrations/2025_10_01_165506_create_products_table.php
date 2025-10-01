<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique(); // Référence unique
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained();

            // Prix
            $table->decimal('purchase_price', 20, 2)->default(0); // Prix d'achat
            $table->decimal('sale_price', 20, 2)->default(0); // Prix de vente
            $table->decimal('cost_price', 20, 2)->default(0); // Coût de revient (CMP)

            // Taxes
            $table->foreignId('sale_tax_id')->nullable()->constrained('taxes');
            $table->foreignId('purchase_tax_id')->nullable()->constrained('taxes');

            // Comptes comptables
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts');
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts');

            // Gestion des stocks
            $table->boolean('track_inventory')->default(true);
            $table->enum('cost_method', ['fifo', 'lifo', 'average'])->default('average'); // FIFO, LIFO, CMP
            $table->integer('alert_quantity')->default(10); // Alerte stock bas

            // Images
            $table->json('images')->nullable();
            $table->string('featured_image')->nullable();

            // Variantes
            $table->boolean('has_variants')->default(false);

            // Statut
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('slug');
            $table->index('name');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
