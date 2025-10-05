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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_total',10,2);
            $table->enum('statut', ['en_préparation','prete','en_livraison','livrée','annulée']);           
            $table->date('date_commande');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('code_promo_id')->nullable()->constrained('code_promos')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
