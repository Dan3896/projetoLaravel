@props(['progresso', 'chamado'])

@php
    $cancelado = $chamado->status->descricao === 'CANCELADO';
    $percentual = $progresso['total'] > 0 ? round($progresso['atual'] / $progresso['total'] * 100) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'bg-white p-6 rounded-lg shadow']) }}>
    <div class="flex flex-wrap justify-between items-baseline gap-2 mb-3">
        <h2 class="text-lg font-bold text-gray-800">Andamento</h2>
        @if($cancelado)
            <span class="text-sm font-semibold text-red-700">Ordem cancelada</span>
        @elseif($progresso['total'] > 0)
            <span class="text-sm text-gray-600">
                Etapa <strong>{{ $progresso['atual'] }}/{{ $progresso['total'] }}</strong>
                @if($chamado->etapa) · {{ $chamado->etapa->nome }} @endif
            </span>
        @endif
    </div>

    @if($progresso['total'] === 0)
        <p class="text-gray-500">Nenhuma etapa cadastrada.</p>
    @else
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4" role="progressbar"
             aria-valuenow="{{ $progresso['atual'] }}" aria-valuemin="0" aria-valuemax="{{ $progresso['total'] }}">
            <div class="h-2 rounded-full {{ $cancelado ? 'bg-gray-400' : 'bg-blue-600' }}" style="width: {{ $percentual }}%"></div>
        </div>

        <ol class="flex flex-col sm:flex-row sm:justify-between gap-2">
            @foreach($progresso['etapas'] as $indice => $etapa)
                @php
                    $passo = $indice + 1;
                    $feito = $passo < $progresso['atual'] || ($passo === $progresso['atual'] && $progresso['atual'] === $progresso['total']);
                    $atual = $passo === $progresso['atual'] && ! $feito;
                @endphp
                <li class="flex sm:flex-col items-center gap-2 sm:text-center sm:flex-1" title="{{ $etapa->descricao }}">
                    <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $feito ? 'bg-blue-600 text-white' : ($atual ? 'bg-white border-2 border-blue-600 text-blue-700' : 'bg-gray-200 text-gray-500') }}">
                        {{ $feito ? '✓' : $passo }}
                    </span>
                    <span class="text-sm {{ $atual ? 'font-semibold text-gray-900' : 'text-gray-600' }}">{{ $etapa->nome }}</span>
                </li>
            @endforeach
        </ol>

        @if($chamado->etapa?->descricao && ! $cancelado)
            <p class="text-sm text-gray-500 mt-4">{{ $chamado->etapa->descricao }}</p>
        @endif
    @endif
</div>
