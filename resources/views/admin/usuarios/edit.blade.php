@extends('layouts.main')

@section('title', 'Editar Usuário')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Editar Usuário</h1>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nome</label>
            <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required autofocus
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nova senha</label>
            <input type="password" name="password" autocomplete="new-password"
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-sm text-gray-500 mt-1">Deixe em branco para manter a senha atual.</p>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Empresa</label>
            <select name="cliente_id" @required($usuario->hasRole('cliente'))
                    class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">{{ $usuario->hasRole('cliente') ? 'Selecione uma empresa' : 'Nenhuma' }}</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(old('cliente_id', $usuario->cliente_id) == $cliente->id)>
                        {{ $cliente->nome_empresa }}
                    </option>
                @endforeach
            </select>
            <p class="text-sm text-gray-500 mt-1">O cliente só vê os chamados da empresa a que está vinculado.</p>
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Salvar</x-button>
            <a href="{{ route('admin.usuarios.index') }}" class="text-gray-600 hover:text-gray-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection
