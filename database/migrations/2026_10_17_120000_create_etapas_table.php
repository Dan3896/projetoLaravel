<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapas que o cliente vê no chamado (Recebido, Triagem, Em andamento...).
     * São cadastradas pelo Márcio e não se confundem com os status internos do SLA.
     */
    public function up(): void
    {
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->string('descricao', 255)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        Schema::table('chamados', function (Blueprint $table) {
            $table->foreignId('etapa_id')->nullable()->after('status_id')->constrained('etapas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etapa_id');
        });

        Schema::dropIfExists('etapas');
    }
};
