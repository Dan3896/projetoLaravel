<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anotações internas da equipe sobre o chamado. O cliente não vê.
     */
    public function up(): void
    {
        Schema::create('chamado_historicos_internos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamado_historicos_internos');
    }
};
