<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mantém só a triagem mais recente (maior id) de cada chamado; senão o unique falharia.
        // O MySQL não aceita subquery na própria tabela do DELETE, então os ids saem por PHP.
        $manter = DB::table('chamado_triagens')->selectRaw('MAX(id) as id')->groupBy('chamado_id')->pluck('id');

        DB::table('chamado_triagens')->whereNotIn('id', $manter)->delete();

        Schema::table('chamado_triagens', function (Blueprint $table) {
            $table->unique('chamado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamado_triagens', function (Blueprint $table) {
            $table->dropUnique(['chamado_id']);
        });
    }
};
