@extends('layouts.main')

@section('title', 'Nova Ordem de Serviço')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Abertura de Chamado / OS</h1>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chamados.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label for="cliente_id" class="block text-gray-700 font-medium mb-2">Cliente</label>
            <select name="cliente_id" id="cliente_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2 bg-white" required>
                <option value="">Selecione um cliente...</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                        {{ $cliente->nome_empresa }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="tipo_chamado_id" class="block text-gray-700 font-medium mb-2">Tipo de Solicitação</label>
            <select name="tipo_chamado_id" id="tipo_chamado_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2 bg-white" required>
                <option value="">Selecione o tipo...</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo->id }}" {{ old('tipo_chamado_id') == $tipo->id ? 'selected' : '' }}>
                        {{ $tipo->descricao }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="criticidade_cliente" class="block text-gray-700 font-medium mb-2">Criticidade</label>
            <select name="criticidade_cliente" id="criticidade_cliente" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2 bg-white">
                <option value="">(Opcional) Selecione a criticidade...</option>
                <option value="Baixa" {{ old('criticidade_cliente') == 'Baixa' ? 'selected' : '' }}>Baixa</option>
                <option value="Média" {{ old('criticidade_cliente') == 'Média' ? 'selected' : '' }}>Média</option>
                <option value="Alta" {{ old('criticidade_cliente') == 'Alta' ? 'selected' : '' }}>Alta</option>
                <option value="Crítica" {{ old('criticidade_cliente') == 'Crítica' ? 'selected' : '' }}>Crítica</option>
            </select>
        </div>

        <div class="mb-6">
            <label for="descricao" class="block text-gray-700 font-medium mb-2">Descrição Detalhada</label>
            <textarea name="descricao" id="descricao" rows="5" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2" required placeholder="Descreva detalhadamente o problema ou solicitação...">{{ old('descricao') }}</textarea>
            <p class="text-sm text-gray-500 mt-1">Mínimo de 10 caracteres.</p>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('chamados.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-300 transition inline-block text-center">Cancelar</a>
            <x-button type="submit">Criar Ordem de Serviço</x-button>
        </div>
    </form>
</div>
@endsection
