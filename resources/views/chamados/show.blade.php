@extends('layouts.main')

@section('title', 'Detalhes da OS #' . $chamado->id)

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="max-w-4xl mx-auto"
     x-data="{ aba: window.location.hash === '#historico-interno' ? 'interno' : 'detalhes' }">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ordem de Serviço #{{ $chamado->id }}</h1>
        <div class="flex gap-2">
            @if($isAdmin && ! $chamado->estaEncerrado())
                <x-button href="{{ route('chamados.triagem.create', $chamado) }}">
                    {{ $chamado->triagem ? 'Refazer triagem' : 'Triar' }}
                </x-button>
            @endif
            <a href="{{ route('chamados.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                &larr; Voltar
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-progresso-etapas :progresso="$progresso" :chamado="$chamado" class="mb-6" />

    @if($isAdmin)
        <nav class="flex gap-1 border-b border-gray-200 mb-6" role="tablist">
            <button type="button" role="tab" @click="aba = 'detalhes'; history.replaceState(null, '', '#detalhes')"
                    :class="aba === 'detalhes' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 -mb-px border-b-2 font-medium">
                Detalhes
            </button>
            <button type="button" role="tab" @click="aba = 'interno'; history.replaceState(null, '', '#historico-interno')"
                    :class="aba === 'interno' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 -mb-px border-b-2 font-medium">
                Histórico interno ({{ $chamado->historicosInternos->count() }})
            </button>
        </nav>
    @endif

    <div x-show="aba === 'detalhes'">
        <div class="bg-white p-8 rounded-lg shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase">Empresa</h3>
                    <p class="text-lg font-medium text-gray-900">{{ $chamado->cliente->nome_empresa }}</p>
                </div>
                @if($isAdmin)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">Status interno</h3>
                        <p><x-status-badge :status="$chamado->status->descricao" class="text-sm" /></p>
                    </div>
                @endif
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase">Tipo de Solicitação</h3>
                    <p class="text-gray-900">{{ $chamado->tipoChamado->descricao }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase">Urgência informada pelo cliente</h3>
                    <p class="text-gray-900">{{ $chamado->criticidade_cliente ?? 'Não informada' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase">Data de Abertura</h3>
                    <p class="text-gray-900">{{ $chamado->aberto_em->format('d/m/Y H:i') }}</p>
                </div>
                @if($chamado->fechado_em)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">Data de Fechamento</h3>
                        <p class="text-gray-900">{{ $chamado->fechado_em->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Descrição da Solicitação</h3>
                <div class="bg-gray-50 p-4 rounded text-gray-800 whitespace-pre-wrap">{{ $chamado->descricao }}</div>
            </div>
        </div>

        @if($isAdmin)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Triagem GUT</h2>
                    @if($chamado->triagem)
                        <dl class="grid grid-cols-3 gap-2 text-center mb-4">
                            <div class="bg-gray-50 rounded p-2">
                                <dt class="text-xs text-gray-500 uppercase">Gravidade</dt>
                                <dd class="text-xl font-bold">{{ $chamado->triagem->gut_gravidade }}</dd>
                            </div>
                            <div class="bg-gray-50 rounded p-2">
                                <dt class="text-xs text-gray-500 uppercase">Urgência</dt>
                                <dd class="text-xl font-bold">{{ $chamado->triagem->gut_urgencia }}</dd>
                            </div>
                            <div class="bg-gray-50 rounded p-2">
                                <dt class="text-xs text-gray-500 uppercase">Tendência</dt>
                                <dd class="text-xl font-bold">{{ $chamado->triagem->gut_tendencia }}</dd>
                            </div>
                        </dl>
                        <p class="text-gray-700">
                            Pontuação: <x-prioridade-badge :triagem="$chamado->triagem" />
                        </p>
                    @else
                        <p class="text-gray-500">Este chamado ainda não foi triado.</p>
                    @endif
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">SLA</h2>
                    <dl class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-gray-50 rounded p-2">
                            <dt class="text-xs text-gray-500 uppercase">Tempo de atendimento</dt>
                            <dd class="text-xl font-bold">{{ \App\Services\SlaService::formatarDuracao($tempoSla['liquido']) }}</dd>
                        </div>
                        <div class="bg-gray-50 rounded p-2">
                            <dt class="text-xs text-gray-500 uppercase">Pausado (cliente)</dt>
                            <dd class="text-xl font-bold">{{ \App\Services\SlaService::formatarDuracao($tempoSla['pausado']) }}</dd>
                        </div>
                    </dl>
                    <p class="text-xs text-gray-500 mt-2">O tempo em "Pendente cliente" não conta no atendimento.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">Etapa para o cliente</h2>
                    <p class="text-sm text-gray-500 mb-4">É o que o cliente vê no andamento do chamado.</p>
                    <form action="{{ route('chamados.etapa.update', $chamado) }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        @method('PATCH')
                        <select name="etapa_id" class="flex-1 border-gray-300 rounded-md border p-2 bg-white" required>
                            @foreach($etapas as $etapa)
                                <option value="{{ $etapa->id }}" @selected($etapa->id === $chamado->etapa_id)>
                                    {{ $loop->iteration }}. {{ $etapa->nome }}
                                </option>
                            @endforeach
                        </select>
                        <x-button type="submit">Salvar</x-button>
                    </form>
                    <a href="{{ route('admin.etapas.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900 mt-2 inline-block">Gerenciar etapas</a>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">Status interno</h2>
                    <p class="text-sm text-gray-500 mb-4">Controla o SLA. O cliente não vê.</p>
                    <form action="{{ route('chamados.status.update', $chamado) }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="flex-1 border-gray-300 rounded-md border p-2 bg-white" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status->descricao }}" @selected($status->id === $chamado->status_id)>
                                    {{ str_replace('_', ' ', $status->descricao) }}
                                </option>
                            @endforeach
                        </select>
                        <x-button type="submit">Salvar</x-button>
                    </form>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Linha do tempo do SLA</h2>
                @if($chamado->historicosSla->isEmpty())
                    <p class="text-gray-500">Nenhuma mudança de status registrada.</p>
                @else
                    <ol class="relative border-l border-gray-200 ml-2">
                        @foreach($chamado->historicosSla as $registro)
                            <li class="mb-4 ml-4">
                                <div class="absolute w-3 h-3 bg-blue-500 rounded-full -left-1.5 mt-1.5 border border-white"></div>
                                <time class="text-sm text-gray-500">{{ $registro->registrado_em->format('d/m/Y H:i') }}</time>
                                <p><x-status-badge :status="$registro->status->descricao" /></p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @can('cancelar', $chamado)
            <details class="bg-white p-6 rounded-lg shadow">
                <summary class="cursor-pointer font-medium text-red-700">Cancelar esta ordem de serviço</summary>
                <form action="{{ route('chamados.cancelar', $chamado) }}" method="POST" class="mt-4"
                      onsubmit="return confirm('Tem certeza que deseja cancelar a OS #{{ $chamado->id }}? Essa ação não pode ser desfeita pelo cliente.');">
                    @csrf
                    <label for="motivo" class="block text-gray-700 font-medium mb-2">Motivo (opcional)</label>
                    <textarea name="motivo" id="motivo" rows="3" maxlength="500"
                              class="w-full border-gray-300 rounded-md border p-2 mb-4">{{ old('motivo') }}</textarea>
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition font-medium">
                        Confirmar cancelamento
                    </button>
                </form>
            </details>
        @endcan
    </div>

    @if($isAdmin)
        <div x-show="aba === 'interno'" x-cloak id="historico-interno">
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-1">Nova anotação</h2>
                <p class="text-sm text-gray-500 mb-4">Visível só para a equipe. O cliente não tem acesso a este histórico.</p>
                <form action="{{ route('chamados.historicos-internos.store', $chamado) }}" method="POST">
                    @csrf
                    <textarea name="texto" rows="4" required maxlength="5000"
                              class="w-full border-gray-300 rounded-md border p-2 mb-4"
                              placeholder="Ex.: reproduzi o erro em homologação; causa provável no job de faturamento.">{{ old('texto') }}</textarea>
                    <div class="flex justify-end">
                        <x-button type="submit">Registrar</x-button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Histórico interno</h2>
                @forelse($chamado->historicosInternos as $registro)
                    <article class="border-b border-gray-100 last:border-0 py-4">
                        <header class="text-sm text-gray-500 mb-1">
                            <span class="font-medium text-gray-700">{{ $registro->autor?->name ?? 'Sistema' }}</span>
                            · {{ $registro->created_at->format('d/m/Y H:i') }}
                        </header>
                        <p class="text-gray-800 whitespace-pre-wrap">{{ $registro->texto }}</p>
                    </article>
                @empty
                    <p class="text-gray-500">Nenhuma anotação ainda.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
