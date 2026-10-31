<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:50'],
            'horas_planejamento' => ['required', 'numeric', 'min:0', 'max:9999'],
            'horas_desenvolvimento' => ['required', 'numeric', 'min:0', 'max:9999'],
            'horas_qa' => ['required', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'descricao' => 'descrição',
            'horas_planejamento' => 'horas de planejamento',
            'horas_desenvolvimento' => 'horas de desenvolvimento',
            'horas_qa' => 'horas de QA',
        ];
    }
}
