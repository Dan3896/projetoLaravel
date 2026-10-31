@extends('layouts.main')

@section('title', $tipo->exists ? 'Editar Tipo de Chamado' : 'Novo Tipo de Chamado')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ $tipo->exists ? 'Editar Tipo de Chamado' : 'Novo Tipo de Chamado' }}</h1>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ $tipo->exists ? route('admin.tipos-chamado.update', $tipo) : route('admin.tipos-chamado.store') }}" method="POST">
        @csrf
        @if($tipo->exists)
            @method('PUT')
        @endif

        <div class="mb-6">
            <label for="descricao" class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
            <input type="text" name="descricao" id="descricao" value="{{ old('descricao', $tipo->descricao) }}" required maxlength="50" autofocus
                   class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <fieldset class="mb-6">
            <legend class="block text-gray-700 text-sm font-bold mb-2">Horas estimadas</legend>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach(\App\Models\Orcamento::COMPONENTES as $chave => $rotulo)
                    <div>
                        <label for="horas_{{ $chave }}" class="block text-gray-600 text-sm mb-1">{{ $rotulo }}</label>
                        <input type="number" step="0.25" min="0" name="horas_{{ $chave }}" id="horas_{{ $chave }}" required
                               value="{{ old("horas_{$chave}", $tipo->{"horas_{$chave}"} ?? 0) }}"
                               class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                @endforeach
            </div>
        </fieldset>

        <div class="flex items-center gap-4">
            <x-button type="submit">Salvar</x-button>
            <a href="{{ route('admin.tipos-chamado.index') }}" class="text-gray-600 hover:text-gray-800">Cancelar</a>
        </div>
    </form>
</div>
@endsection
