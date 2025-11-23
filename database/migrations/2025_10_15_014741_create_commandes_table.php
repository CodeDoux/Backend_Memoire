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
            $table->string('reference')->unique();
            $table->date('dateCommande')->useCurrent();
            // CodeList: STATUT_COMMANDE
            $table->enum('statut', ['EN_PREPARATION','EN_ATTENTE','EN_LIVRAISON','LIVREE','ANNULEE']);
            //$table->foreignId('statut_id')->constrained('code_lists')->onDelete('cascade');
            $table->double('montantTotal', 10, 2);
            $table->double('fraisLivraison', 10, 2)->default(0);
             // CodeList: MODE_LIVRAISON
            $table->enum('modeLivraison', ['DOMICILE','POINT_RELAIS','RETRAIT_MAGASIN']);
            //$table->foreignId('modeLivraison_id')->constrained('code_lists')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
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
