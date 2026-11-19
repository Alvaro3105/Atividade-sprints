<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ticket #009: tabela pivot N:M dos votos (upvotes) em perguntas.
     */
    public function up(): void
    {
        Schema::create('pergunta_user', function (Blueprint $table) {
            $table->foreignId('pergunta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Um usuário só pode votar uma vez na mesma pergunta
            $table->unique(['pergunta_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergunta_user');
    }
};
