<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlterarEtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'etapa_id' => ['required', 'exists:etapas,id'],
        ];
    }
}
