<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'valor_hora' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'horas_planejamento' => ['required', 'numeric', 'min:0', 'max:9999'],
            'horas_desenvolvimento' => ['required', 'numeric', 'min:0', 'max:9999'],
            'horas_qa' => ['required', 'numeric', 'min:0', 'max:9999'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'acao' => ['required', 'in:salvar,enviar'],
        ];
    }

    public function attributes(): array
    {
        return [
            'valor_hora' => 'valor-hora',
            'horas_planejamento' => 'horas de planejamento',
            'horas_desenvolvimento' => 'horas de desenvolvimento',
            'horas_qa' => 'horas de QA',
            'observacoes' => 'observações',
        ];
    }
}
