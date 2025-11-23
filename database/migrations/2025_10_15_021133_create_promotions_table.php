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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable(); // Code promotionnel (ex: PROMO10)
            $table->string('nom');
            $table->text('description')->nullable();
            $table->date('dateDebut');
            $table->date('dateFin');
            $table->integer('reduction');
            $table->decimal('seuilMinimum',10,2)->nullable();
            $table->integer('utilisationMax')->nullable();
            $table->integer('usage')->default(0);
            $table->boolean('estActif')->default(true);
            // CodeList: TYPE_PROMOTION
            //$table->foreignId('type_id')->constrained('code_lists')->onDelete('cascade');
            $table->enum('typePromo', ['PRODUIT','COMMANDE']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
