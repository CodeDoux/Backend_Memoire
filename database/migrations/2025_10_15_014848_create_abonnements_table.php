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
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            // référence à CodeList (TYPE_ABONNEMENT)
           // $table->foreignId('type_id')->constrained('code_lists')->onDelete('cascade');
            $table->enum('typeAbonnement', ['GRATUIT','STANDARD','PREMIUM']);
            $table->text('description')->nullable();
            $table->double('prix', 10, 2);
            $table->integer('dureeJours'); // 30, 90, 365...
            $table->integer('maxProduits')->default(10);
            $table->date('dateDebut');
            $table->date('dateFin')->nullable();
            $table->enum('statut', ['ACTIF','EXPIRE','SUSPENDU']);
            //$table->foreignId('statut_id')->constrained('code_lists')->onDelete('cascade'); // ACTIF, EXPIRE, SUSPENDU
            $table->foreignId('producteur_id')->constrained('producteurs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
