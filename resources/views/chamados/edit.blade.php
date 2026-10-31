@extends('layouts.main')

@section('title', 'Editar OS #' . $chamado->id)

@section('content')
<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-2 text-gray-800">Editar OS #{{ $chamado->id }}</h1>
    <p class="text-sm text-gray-500 mb-6">{{ $chamado->cliente->nome_empresa }}. A alteração fica registrada no histórico interno.</p>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chamados.update', $chamado) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="tipo_chamado_id" class="block text-gray-700 font-medium mb-2">Tipo de Solicitação</label>
            <select name="tipo_chamado_id" id="tipo_chamado_id" class="w-full border border-gray-300 rounded-md p-2 bg-white" required>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo->id }}" @selected(old('tipo_chamado_id', $chamado->tipo_chamado_id) == $tipo->id)>{{ $tipo->descricao }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="criticidade_cliente" class="block text-gray-700 font-medium mb-2">Urgência informada pelo cliente</label>
            <select name="criticidade_cliente" id="criticidade_cliente" class="w-full border border-gray-300 rounded-md p-2 bg-white">
                <option value="">Não informada</option>
                @foreach(['Baixa', 'Média', 'Alta', 'Crítica'] as $urgencia)
                    <option value="{{ $urgencia }}" @selected(old('criticidade_cliente', $chamado->criticidade_cliente) === $urgencia)>{{ $urgencia }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-6">
            <label for="descricao" class="block text-gray-700 font-medium mb-2">Descrição</label>
            <textarea name="descricao" id="descricao" rows="6" minlength="20" required
                      class="w-full border border-gray-300 rounded-md p-2">{{ old('descricao', $chamado->descricao) }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('chamados.show', $chamado) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">Cancelar</a>
            <x-button type="submit">Salvar</x-button>
        </div>
    </form>
</div>
@endsection
