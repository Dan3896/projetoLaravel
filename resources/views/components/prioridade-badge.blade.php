@props(['triagem' => null])

@php
    $prioridade = \App\Services\TriagemService::prioridade($triagem?->pontuacao);
    $cores = [
        'Crítica' => 'bg-red-100 text-red-800',
        'Alta' => 'bg-orange-100 text-orange-800',
        'Média' => 'bg-yellow-100 text-yellow-800',
        'Baixa' => 'bg-green-100 text-green-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . ($cores[$prioridade] ?? 'bg-gray-100 text-gray-600')]) }}>
    {{ $prioridade }}@if($triagem) · {{ $triagem->pontuacao }}@endif
</span>
