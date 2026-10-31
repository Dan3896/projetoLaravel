<?php

namespace App\Http\Requests;

use App\Models\ChamadoAnexo;
use Illuminate\Foundation\Http\FormRequest;

class AnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('anexar', $this->route('chamado'));
    }

    public function rules(): array
    {
        return [
            'anexos' => ['required', 'array', 'max:' . ChamadoAnexo::MAXIMO_POR_ENVIO],
            'anexos.*' => ChamadoAnexo::regras(),
        ];
    }

    public function attributes(): array
    {
        return ['anexos.*' => 'anexo'];
    }
}
