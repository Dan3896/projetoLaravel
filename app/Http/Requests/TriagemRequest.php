<?php

namespace App\Http\Requests;

use App\Services\TriagemService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TriagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'gut_gravidade' => ['required', 'integer', 'between:1,5'],
            'gut_urgencia' => ['required', 'integer', 'between:1,5'],
            'gut_tendencia' => ['required', 'integer', 'between:1,5'],
            'tipo_chamado_id' => ['required', 'exists:tipo_chamados,id'],
            'proximo_status' => ['required', Rule::in(array_keys(TriagemService::PROXIMOS_STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'gut_gravidade' => 'gravidade',
            'gut_urgencia' => 'urgência',
            'gut_tendencia' => 'tendência',
            'tipo_chamado_id' => 'tipo de chamado',
            'proximo_status' => 'próximo passo',
        ];
    }
}
