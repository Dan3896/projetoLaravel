@extends('layouts.main')

@section('title', 'Lista de Ordens de Serviço')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ordens de Serviço</h1>
        <x-button href="{{ route('chamados.create') }}">+ Nova OS</x-button>
    </div>

    <form method="GET" action="{{ route('chamados.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <input type="search" name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Buscar por nº, descrição{{ $isAdmin ? ' ou empresa' : '' }}"
               class="border border-gray-300 rounded-md p-2 lg:col-span-2">
        @if($isAdmin)
            <select name="empresa" class="border border-gray-300 rounded-md p-2 bg-white">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}" @selected(($filtros['empresa'] ?? '') == $empresa->id)>{{ $empresa->nome_empresa }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-gray-300 rounded-md p-2 bg-white">
                <option value="">Todos os status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->id }}" @selected(($filtros['status'] ?? '') == $status->id)>{{ str_replace('_', ' ', $status->descricao) }}</option>
                @endforeach
            </select>
        @endif
        <select name="etapa" class="border border-gray-300 rounded-md p-2 bg-white">
            <option value="">Todas as etapas</option>
            @foreach($etapas as $etapa)
                <option value="{{ $etapa->id }}" @selected(($filtros['etapa'] ?? '') == $etapa->id)>{{ $etapa->nome }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5 justify-end">
            @if(array_filter($filtros))
                <a href="{{ route('chamados.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">Limpar</a>
            @endif
            <x-button type="submit">Filtrar</x-button>
        </div>
    </form>

    @if($chamados->isEmpty())
        <div class="text-center py-10 text-gray-500">
            Nenhuma Ordem de Serviço encontrada.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        @if($isAdmin)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etapa</th>
                        @if($isAdmin)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridade</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($chamados as $chamado)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $chamado->id }}</td>
                            @if($isAdmin)
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ $chamado->cliente->nome_empresa }}
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $chamado->tipoChamado->descricao }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($chamado->status->descricao === 'CANCELADO')
                                    <span class="font-semibold text-red-700">Cancelado</span>
                                @else
                                    {{ $chamado->etapa?->nome ?? '-' }}
                                @endif
                            </td>
                            @if($isAdmin)
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-status-badge :status="$chamado->status->descricao" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-prioridade-badge :triagem="$chamado->triagem" />
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $chamado->aberto_em->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('chamados.show', $chamado) }}" class="text-indigo-600 hover:text-indigo-900">Detalhes</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $chamados->links() }}
        </div>
    @endif
</div>
@endsection
