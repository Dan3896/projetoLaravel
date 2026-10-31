<?php

namespace App\Http\Requests;

use App\Models\Chamado;
use App\Models\ChamadoAnexo;
use Illuminate\Foundation\Http\FormRequest;

class StoreChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Chamado::class);
    }

    public function rules(): array
    {
        return [
            // Só o admin escolhe a empresa; para o cliente ela vem do vínculo do usuário.
            'cliente_id' => [$this->user()->isAdmin() ? 'required' : 'exclude', 'exists:clientes,id'],
            'tipo_chamado_id' => ['required', 'exists:tipo_chamados,id'],
            'descricao' => ['required', 'string', 'min:20'],
            'criticidade_cliente' => ['nullable', 'string', 'in:Baixa,Média,Alta,Crítica'],
            'anexos' => ['nullable', 'array', 'max:' . ChamadoAnexo::MAXIMO_POR_ENVIO],
            'anexos.*' => ChamadoAnexo::regras(),
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'empresa',
            'tipo_chamado_id' => 'tipo de solicitação',
            'descricao' => 'descrição',
            'criticidade_cliente' => 'urgência',
            'anexos.*' => 'anexo',
        ];
    }
}
