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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->enum('mode_paiement', ['en_espece','en_ligne']);
            $table->decimal('montant_paye',10,2);
            $table->date('date_paiement');
            $table->string('reference_transaction', 100)->unique()->nullable();
            $table->string('statut');
            $table->string('numero_telephone')->nullable();
            $table->string('note')->nullable();
            $table->enum('operateur', ['orange_money','free_money','wave','carte_bancaire'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
