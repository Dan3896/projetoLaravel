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
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('status_id')->constrained('status');
            $table->foreignId('tipo_chamado_id')->constrained('tipo_chamados');

            $table->text('descricao');
            $table->string('criticidade_cliente', 50)->nullable();

            $table->dateTime('aberto_em')->useCurrent();
            $table->dateTime('inicio_planejamento')->nullable();
            $table->dateTime('fim_planejamento')->nullable();
            $table->dateTime('inicio_dev')->nullable();
            $table->dateTime('fim_dev')->nullable();
            $table->dateTime('inicio_qa')->nullable();
            $table->dateTime('fim_qa')->nullable();
            $table->dateTime('fechado_em')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamados');
    }
};
