<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-04: horas estimadas por tipo de chamado (catálogo) e orçamento detalhado
     * em planejamento, desenvolvimento e QA, com status de aprovação.
     */
    public function up(): void
    {
        Schema::table('tipo_chamados', function (Blueprint $table) {
            $table->decimal('horas_planejamento', 6, 2)->default(0);
            $table->decimal('horas_desenvolvimento', 6, 2)->default(0);
            $table->decimal('horas_qa', 6, 2)->default(0);
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('status', 20)->default('pendente');
            $table->decimal('valor_hora', 10, 2)->default(0);
            $table->decimal('horas_planejamento', 6, 2)->default(0);
            $table->decimal('horas_desenvolvimento', 6, 2)->default(0);
            $table->decimal('horas_qa', 6, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->text('motivo_recusa')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('respondido_em')->nullable();
        });

        Schema::table('preco_clientes', function (Blueprint $table) {
            $table->unique('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('preco_clientes', function (Blueprint $table) {
            $table->dropUnique(['cliente_id']);
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'valor_hora', 'horas_planejamento', 'horas_desenvolvimento', 'horas_qa',
                'observacoes', 'motivo_recusa', 'enviado_em', 'respondido_em',
            ]);
        });

        Schema::table('tipo_chamados', function (Blueprint $table) {
            $table->dropColumn(['horas_planejamento', 'horas_desenvolvimento', 'horas_qa']);
        });
    }
};
