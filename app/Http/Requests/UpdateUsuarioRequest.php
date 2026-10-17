<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'password' => ['nullable', 'string', 'min:8'],
            // Usuário cliente precisa estar vinculado a uma empresa.
            'cliente_id' => [$usuario->hasRole('cliente') ? 'required' : 'nullable', 'exists:clientes,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'password' => 'senha',
            'cliente_id' => 'empresa',
        ];
    }
}
