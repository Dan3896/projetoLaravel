<?php

namespace App\Http\Requests;

use App\Services\TriagemService;
use Illuminate\Foundation\Http\FormRequest;

class PrazosSlaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $regras = ['prazos' => ['required', 'array']];

        // Prazo vazio = prioridade sem prazo de SLA.
        foreach (TriagemService::PRIORIDADES as $prioridade) {
            $regras["prazos.{$prioridade}"] = ['nullable', 'integer', 'min:1', 'max:8760'];
        }

        return $regras;
    }

    public function attributes(): array
    {
        return collect(TriagemService::PRIORIDADES)
            ->mapWithKeys(fn ($prioridade) => ["prazos.{$prioridade}" => "prazo da prioridade {$prioridade}"])
            ->all();
    }
}
