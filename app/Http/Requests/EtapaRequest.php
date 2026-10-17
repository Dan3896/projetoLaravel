<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:50'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ordem' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return ['descricao' => 'descrição'];
    }
}
