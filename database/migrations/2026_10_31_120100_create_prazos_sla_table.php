<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prazo de atendimento (em horas líquidas de SLA) por prioridade da matriz GUT.
     */
    public function up(): void
    {
        Schema::create('prazos_sla', function (Blueprint $table) {
            $table->id();
            $table->string('prioridade', 20)->unique();
            $table->unsignedInteger('horas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prazos_sla');
    }
};
