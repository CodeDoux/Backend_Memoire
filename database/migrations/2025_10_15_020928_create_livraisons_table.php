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
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('dateExpedition')->nullable();
            $table->date('dateLivraison')->nullable();
            // CodeList: STATUT_LIVRAISON
            //$table->foreignId('statut_id')->constrained('code_lists')->onDelete('cascade');
            $table->enum('statutLivraison', ['EN_COURS','LIVREE','NON_LIVREE']);
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->string('adresseLivraison_id')->constrained('adresses')->onDelete('set null');
            $table->foreignId('zoneLivraison_id')->constrained('zone_livraisons')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
