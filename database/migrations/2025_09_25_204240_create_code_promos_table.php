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
        Schema::create('code_promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: WELCOME10
            $table->string('description')->nullable();
            $table->date('dateDebut');
            $table->date('dateFin');
            $table->decimal('reduction',10,2);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_promos');
    }
};
