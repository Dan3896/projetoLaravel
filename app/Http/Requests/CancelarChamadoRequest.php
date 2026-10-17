<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancelar', $this->route('chamado'));
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
