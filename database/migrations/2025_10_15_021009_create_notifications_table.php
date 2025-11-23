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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destinataire_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('message');
            $table->boolean('estLu')->default(false);
            // CodeList: TYPE_NOTIFICATION
            //$table->foreignId('type_id')->constrained('code_lists')->onDelete('cascade');
            $table->string('typeNotification');
            $table->timestamp('dateEnvoi')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
