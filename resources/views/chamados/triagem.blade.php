@extends('layouts.main')

@section('title', 'Triagem da OS #' . $chamado->id)

@php
    $perguntas = [
        'gut_gravidade' => [
            'titulo' => 'Gravidade',
            'ajuda' => 'Qual o impacto do problema no negócio do cliente?',
            'opcoes' => [1 => 'Mínimo / cosmético', 2 => 'Pouco grave', 3 => 'Grave', 4 => 'Muito grave', 5 => 'Operação parada / perda financeira'],
        ],
        'gut_urgencia' => [
            'titulo' => 'Urgência',
            'ajuda' => 'Em quanto tempo isso precisa ser resolvido?',
            'opcoes' => [1 => 'Pode aguardar', 2 => 'Pouco urgente', 3 => 'O mais rápido possível', 4 => 'Com alguma urgência', 5 => 'Ação imediata'],
        ],
        'gut_tendencia' => [
            'titulo' => 'Tendência',
            'ajuda' => 'Se nada for feito, o problema piora?',
            'opcoes' => [1 => 'Não vai piorar', 2 => 'Piora a longo prazo', 3 => 'Piora a médio prazo', 4 => 'Piora a curto prazo', 5 => 'Piora rapidamente'],
        ],
    ];

    $valoresIniciais = [
        'gut_gravidade' => (int) old('gut_gravidade', $chamado->triagem?->gut_gravidade ?? 0),
        'gut_urgencia' => (int) old('gut_urgencia', $chamado->triagem?->gut_urgencia ?? 0),
        'gut_tendencia' => (int) old('gut_tendencia', $chamado->triagem?->gut_tendencia ?? 0),
    ];
@endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Triagem da OS #{{ $chamado->id }}</h1>
        <a href="{{ route('chamados.show', $chamado) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
            &larr; Voltar
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Cliente</h3>
                <p class="text-gray-900 font-medium">{{ $chamado->cliente->nome_empresa }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Status atual</h3>
                <x-status-badge :status="$chamado->status->descricao" />
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Urgência informada pelo cliente</h3>
                <p class="text-gray-900">{{ $chamado->criticidade_cliente ?? 'Não informada' }}</p>
            </div>
        </div>
        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Descrição do cliente</h3>
        <div class="bg-gray-50 p-4 rounded text-gray-800 whitespace-pre-wrap">{{ $chamado->descricao }}</div>
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

    <form action="{{ route('chamados.triagem.store', $chamado) }}" method="POST"
          class="bg-white p-6 rounded-lg shadow"
          x-data="triagemGut(@js($valoresIniciais))">
        @csrf

        <h2 class="text-lg font-bold text-gray-800 mb-4">Matriz GUT</h2>

        @foreach ($perguntas as $campo => $pergunta)
            <fieldset class="mb-6">
                <legend class="font-medium text-gray-800">{{ $pergunta['titulo'] }}</legend>
                <p class="text-sm text-gray-500 mb-2">{{ $pergunta['ajuda'] }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                    @foreach ($pergunta['opcoes'] as $nota => $rotulo)
                        <label class="border rounded-md p-3 cursor-pointer text-sm hover:border-blue-400 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                            <input type="radio" name="{{ $campo }}" value="{{ $nota }}" class="mr-1"
                                   x-model.number="valores.{{ $campo }}"
                                   @checked($valoresIniciais[$campo] === $nota) required>
                            <span class="font-bold">{{ $nota }}</span>
                            <span class="block text-gray-600">{{ $rotulo }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 rounded-md" x-show="pontuacao() > 0" x-cloak>
            <span class="text-gray-700">Pontuação GUT:</span>
            <span class="text-2xl font-bold text-gray-900" x-text="pontuacao()"></span>
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="corPrioridade()" x-text="prioridade()"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="tipo_chamado_id" class="block text-gray-700 font-medium mb-2">Tipo de chamado</label>
                <select name="tipo_chamado_id" id="tipo_chamado_id" class="w-full border-gray-300 rounded-md border p-2 bg-white" required>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected(old('tipo_chamado_id', $chamado->tipo_chamado_id) == $tipo->id)>
                            {{ $tipo->descricao }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="proximo_status" class="block text-gray-700 font-medium mb-2">Próximo passo</label>
                <select name="proximo_status" id="proximo_status" class="w-full border-gray-300 rounded-md border p-2 bg-white" required>
                    @foreach ($proximosStatus as $status => $rotulo)
                        <option value="{{ $status }}" @selected(old('proximo_status', 'EM_ANALISE') === $status)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button type="submit">Salvar triagem</x-button>
        </div>
    </form>
</div>

<script>
    function triagemGut(valores) {
        return {
            valores,
            pontuacao() {
                return this.valores.gut_gravidade * this.valores.gut_urgencia * this.valores.gut_tendencia;
            },
            prioridade() {
                const p = this.pontuacao();
                if (p <= 24) return 'Baixa';
                if (p <= 60) return 'Média';
                if (p <= 99) return 'Alta';
                return 'Crítica';
            },
            corPrioridade() {
                return {
                    'Baixa': 'bg-green-100 text-green-800',
                    'Média': 'bg-yellow-100 text-yellow-800',
                    'Alta': 'bg-orange-100 text-orange-800',
                    'Crítica': 'bg-red-100 text-red-800',
                }[this.prioridade()];
            },
        };
    }
</script>
@endsection
