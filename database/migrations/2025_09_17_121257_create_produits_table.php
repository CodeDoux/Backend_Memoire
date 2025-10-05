

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
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('description');
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('prix',8,2);
            $table->string('saison');
            $table->string('poids');
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->tinyInteger('note');
            $table->foreignId('producteur_id')->constrained('producteurs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};

