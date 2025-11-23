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
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');   
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');         
            $table->tinyInteger('note')->check('note BETWEEN 1 AND 5');
            $table->text('commentaire')->nullable();
            $table->timestamp('dateAvis')->useCurrent();
            $table->boolean('estVerifie')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
