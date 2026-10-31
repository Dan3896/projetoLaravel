@extends('layouts.main')

@section('title', 'Painel - OiCram')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">Painel de chamados</h1>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach($indicadores as $rotulo => $valor)
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-500">{{ $rotulo }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $valor }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-lg shadow lg:col-span-2">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Aguardando triagem</h2>
        <p class="text-sm text-gray-500 mb-4">Do mais antigo para o mais novo.</p>

        @if($aguardandoTriagem->isEmpty())
            <p class="text-gray-500">Nenhum chamado aguardando triagem.</p>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach($aguardandoTriagem as $chamado)
                    <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <a href="{{ route('chamados.show', $chamado) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                #{{ $chamado->id }} · {{ $chamado->cliente->nome_empresa }}
                            </a>
                            <p class="text-sm text-gray-500">
                                {{ $chamado->tipoChamado->descricao }} ·
                                urgência do cliente: {{ $chamado->criticidade_cliente ?? 'não informada' }} ·
                                aberto há {{ \App\Services\SlaService::formatarDuracao($chamado->sla['liquido']) }}
                            </p>
                        </div>
                        <x-button href="{{ route('chamados.triagem.create', $chamado) }}" class="text-sm">Triar</x-button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Por prioridade</h2>
            <ul class="space-y-2">
                @foreach($porPrioridade as $prioridade => $total)
                    <li class="flex justify-between">
                        <span>{{ $prioridade }}</span>
                        <span class="font-bold">{{ $total }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Por status</h2>
            <ul class="space-y-2">
                @foreach($porStatus as $status)
                    <li class="flex justify-between items-center">
                        <x-status-badge :status="$status->descricao" />
                        <span class="font-bold">{{ $status->chamados_count }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-lg font-bold text-gray-800 mb-1">Fila de atendimento</h2>
    <p class="text-sm text-gray-500 mb-4">Chamados triados e em aberto, ordenados pela pontuação GUT.</p>

    @if($fila->isEmpty())
        <p class="text-gray-500">Nenhum chamado triado em aberto.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tempo de atendimento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prazo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($fila as $chamado)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('chamados.show', $chamado) }}" class="text-indigo-600 hover:text-indigo-900">#{{ $chamado->id }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $chamado->cliente->nome_empresa }}</td>
                            <td class="px-4 py-3 text-sm"><x-prioridade-badge :triagem="$chamado->triagem" /></td>
                            <td class="px-4 py-3 text-sm"><x-status-badge :status="$chamado->status->descricao" /></td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ \App\Services\SlaService::formatarDuracao($chamado->sla['liquido']) }}
                                @if($chamado->sla['pausado'] > 0)
                                    <span class="text-gray-400">(+{{ \App\Services\SlaService::formatarDuracao($chamado->sla['pausado']) }} pausado)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @if(! $chamado->prazo)
                                    <span class="text-gray-400">-</span>
                                @elseif($chamado->prazo['atrasado'])
                                    <span class="font-semibold text-red-700">Atrasado {{ \App\Services\SlaService::formatarDuracao(-$chamado->prazo['restante']) }}</span>
                                @else
                                    <span class="text-green-700">Restam {{ \App\Services\SlaService::formatarDuracao($chamado->prazo['restante']) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
