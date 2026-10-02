@extends('layouts.main')

@section('title', 'Novo Usuário')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Novo Usuário</h1>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.usuarios.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nome</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
            <input type="password" name="password" required
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Empresa (Cliente)</label>
            <select name="cliente_id" required
                    class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione uma empresa</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>
                        {{ $cliente->nome_empresa }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Salvar</x-button>
            <a href="{{ route('admin.usuarios.index') }}" class="text-gray-600 hover:text-gray-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection
