@extends('layouts.main')

@section('title', $empresa->exists ? 'Editar Empresa' : 'Nova Empresa')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ $empresa->exists ? 'Editar Empresa' : 'Nova Empresa' }}</h1>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ $empresa->exists ? route('admin.empresas.update', $empresa) : route('admin.empresas.store') }}" method="POST">
        @csrf
        @if($empresa->exists)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label for="nome_empresa" class="block text-gray-700 text-sm font-bold mb-2">Nome da empresa</label>
            <input type="text" name="nome_empresa" id="nome_empresa" value="{{ old('nome_empresa', $empresa->nome_empresa) }}" required maxlength="100" autofocus
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label for="perfil" class="block text-gray-700 text-sm font-bold mb-2">Perfil (opcional)</label>
            <input type="text" name="perfil" id="perfil" value="{{ old('perfil', $empresa->perfil) }}" maxlength="255"
                   placeholder="Ex.: varejo, 3 lojas, sistema de PDV"
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Salvar</x-button>
            <a href="{{ route('admin.empresas.index') }}" class="text-gray-600 hover:text-gray-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection
