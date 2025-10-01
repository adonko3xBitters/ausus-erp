<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('item_type')->default('product'); // product, service
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->string('unit')->default('pce');
            $table->decimal('unit_price', 20, 2);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained();
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('amount', 20, 2); // Total ligne TTC
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
