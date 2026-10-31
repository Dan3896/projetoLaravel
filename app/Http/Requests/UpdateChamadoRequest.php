<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tipo_chamado_id' => ['required', 'exists:tipo_chamados,id'],
            'descricao' => ['required', 'string', 'min:20'],
            'criticidade_cliente' => ['nullable', 'string', 'in:Baixa,Média,Alta,Crítica'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_chamado_id' => 'tipo de solicitação',
            'descricao' => 'descrição',
            'criticidade_cliente' => 'urgência',
        ];
    }
}
