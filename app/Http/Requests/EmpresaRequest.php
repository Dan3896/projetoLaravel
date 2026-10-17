<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nome_empresa' => ['required', 'string', 'max:100'],
            'perfil' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['nome_empresa' => 'nome da empresa'];
    }
}
