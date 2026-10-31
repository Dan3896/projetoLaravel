<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrazosSlaRequest;
use App\Models\PrazoSla;
use App\Services\TriagemService;
use Illuminate\Support\Facades\DB;

class PrazoSlaController extends Controller
{
    public function edit()
    {
        $prazos = PrazoSla::pluck('horas', 'prioridade');
        $prioridades = TriagemService::PRIORIDADES;

        return view('admin.prazos-sla.edit', compact('prazos', 'prioridades'));
    }

    public function update(PrazosSlaRequest $request)
    {
        DB::transaction(function () use ($request) {
            foreach (TriagemService::PRIORIDADES as $prioridade) {
                $horas = $request->validated("prazos.{$prioridade}");

                if ($horas === null) {
                    PrazoSla::where('prioridade', $prioridade)->delete();
                } else {
                    PrazoSla::updateOrCreate(['prioridade' => $prioridade], ['horas' => $horas]);
                }
            }
        });

        return redirect()->route('admin.prazos-sla.edit')->with('success', 'Prazos de SLA atualizados.');
    }
}
