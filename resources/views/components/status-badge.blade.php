@props(['status'])

@php
    $cores = [
        'ABERTO' => 'bg-blue-100 text-blue-800',
        'EM_TRIAGEM' => 'bg-indigo-100 text-indigo-800',
        'EM_ANALISE' => 'bg-purple-100 text-purple-800',
        'PENDENTE_CLIENTE' => 'bg-yellow-100 text-yellow-800',
        'ORCAMENTO_GERADO' => 'bg-cyan-100 text-cyan-800',
        'APROVADO' => 'bg-teal-100 text-teal-800',
        'EM_DESENVOLVIMENTO' => 'bg-sky-100 text-sky-800',
        'EM_QA' => 'bg-orange-100 text-orange-800',
        'CONCLUIDO' => 'bg-green-100 text-green-800',
        'CANCELADO' => 'bg-gray-200 text-gray-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . ($cores[$status] ?? 'bg-gray-100 text-gray-800')]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
