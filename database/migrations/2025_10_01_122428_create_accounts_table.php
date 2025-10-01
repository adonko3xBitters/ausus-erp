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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 101, 401, 6011...
            $table->string('name');
            $table->enum('type', [
                'asset',           // Actif (Classe 1-5)
                'liability',       // Passif (Classe 1-5)
                'equity',          // Capitaux propres
                'revenue',         // Produits (Classe 7)
                'expense',         // Charges (Classe 6)
                'other'            // Classe 8-9
            ]);
            $table->enum('category', [
                'class_1',  // Comptes de ressources durables
                'class_2',  // Comptes d'actif immobilisé
                'class_3',  // Comptes de stocks
                'class_4',  // Comptes de tiers
                'class_5',  // Comptes de trésorerie
                'class_6',  // Comptes de charges
                'class_7',  // Comptes de produits
                'class_8',  // Comptes spéciaux
                'class_9'   // Comptes analytiques
            ]);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->text('description')->nullable();
            $table->boolean('is_sub_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Comptes système non supprimables
            $table->timestamps();

            $table->index('code');
            $table->index('type');
            $table->index('category');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
