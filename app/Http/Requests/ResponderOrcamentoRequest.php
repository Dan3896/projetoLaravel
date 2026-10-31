<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('responderOrcamento', [$this->route('chamado'), $this->route('orcamento')]);
    }

    public function rules(): array
    {
        return [
            'decisao' => ['required', 'in:aprovar,recusar'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
