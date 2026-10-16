@extends('layouts.main')

@section('title', 'Detalhes da OS #' . $chamado->id)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ordem de Serviço #{{ $chamado->id }}</h1>
        <div class="flex gap-2">
            @role('admin')
                @unless($chamado->estaEncerrado())
                    <x-button href="{{ route('chamados.triagem.create', $chamado) }}">
                        {{ $chamado->triagem ? 'Refazer triagem' : 'Triar' }}
                    </x-button>
                @endunless
            @endrole
            <a href="{{ route('chamados.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                &larr; Voltar
            </a>
        </div>
    </div>

    <div class="bg-white p-8 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Cliente</h3>
                <p class="text-lg font-medium text-gray-900">{{ $chamado->cliente->nome_empresa }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Status</h3>
                <p><x-status-badge :status="$chamado->status->descricao" class="text-sm" /></p>
            </div>
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

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Linha do tempo</h2>
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

    @role('admin')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Alterar status</h2>
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
    @endrole
</div>
@endsection
