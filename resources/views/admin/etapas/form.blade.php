@extends('layouts.main')

@section('title', $etapa->exists ? 'Editar Etapa' : 'Nova Etapa')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ $etapa->exists ? 'Editar Etapa' : 'Nova Etapa' }}</h1>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ $etapa->exists ? route('admin.etapas.update', $etapa) : route('admin.etapas.store') }}" method="POST">
        @csrf
        @if($etapa->exists)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label for="nome" class="block text-gray-700 text-sm font-bold mb-2">Nome</label>
            <input type="text" name="nome" id="nome" value="{{ old('nome', $etapa->nome) }}" required maxlength="50" autofocus
                   placeholder="Ex.: Em andamento"
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="descricao" class="block text-gray-700 text-sm font-bold mb-2">Descrição para o cliente (opcional)</label>
            <input type="text" name="descricao" id="descricao" value="{{ old('descricao', $etapa->descricao) }}" maxlength="255"
                   placeholder="Ex.: A equipe está trabalhando na solução."
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label for="ordem" class="block text-gray-700 text-sm font-bold mb-2">Ordem</label>
            <input type="number" name="ordem" id="ordem" value="{{ old('ordem', $etapa->ordem) }}" required min="0" max="999"
                   class="w-32 px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-sm text-gray-500 mt-1">Define a posição da etapa no andamento (menor primeiro).</p>
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Salvar</x-button>
            <a href="{{ route('admin.etapas.index') }}" class="text-gray-600 hover:text-gray-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection
