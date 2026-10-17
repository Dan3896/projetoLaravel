<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistoricoInternoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'texto' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['texto' => 'anotação'];
    }
}
