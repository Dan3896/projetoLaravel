@extends('layouts.main')

@section('title', 'Detalhes da OS #' . $chamado->id)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ordem de Serviço #{{ $chamado->id }}</h1>
        <a href="{{ route('chamados.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
            &larr; Voltar
        </a>
    </div>

    <div class="bg-white p-8 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Cliente</h3>
                <p class="text-lg font-medium text-gray-900">{{ $chamado->cliente->nome_empresa }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Status</h3>
                <p>
                    <span class="px-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $chamado->status->descricao }}
                    </span>
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Tipo de Solicitação</h3>
                <p class="text-gray-900">{{ $chamado->tipoChamado->descricao }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Criticidade Declarada</h3>
                <p class="text-gray-900">{{ $chamado->criticidade_cliente ?? 'Não informada' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Data de Abertura</h3>
                <p class="text-gray-900">{{ $chamado->aberto_em->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Descrição da Solicitação</h3>
            <div class="bg-gray-50 p-4 rounded text-gray-800 whitespace-pre-wrap">{{ $chamado->descricao }}</div>
        </div>
    </div>
</div>
@endsection
