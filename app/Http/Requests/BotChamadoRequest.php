<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BotChamadoRequest extends FormRequest
{
    // A autenticação é feita pelo middleware AutenticarBotTriagem.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'tipo_chamado_id' => ['required', 'exists:tipo_chamados,id'],
            'descricao' => ['required', 'string', 'min:20'],
            'criticidade_cliente' => ['nullable', 'string', 'in:Baixa,Média,Alta,Crítica'],
            'conversa' => ['nullable', 'array', 'max:500'],
            'conversa.*.autor' => ['required', 'string', 'max:50'],
            'conversa.*.texto' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * O chamado é aberto em nome de um usuário cliente ativo e vinculado a uma empresa.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $usuario = $this->usuario();

                if (! $usuario || ! $usuario->ativo || $usuario->cliente_id === null) {
                    $validator->errors()->add('email', 'Nenhum cliente ativo e vinculado a uma empresa com este e-mail.');
                }
            },
        ];
    }

    public function usuario(): ?User
    {
        return User::where('email', $this->input('email'))->first();
    }
}
