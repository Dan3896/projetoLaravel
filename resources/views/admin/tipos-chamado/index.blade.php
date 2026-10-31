@extends('layouts.main')

@use('App\Models\Orcamento')

@section('title', 'Tipos de chamado')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-2">
        <h1 class="text-2xl font-bold text-gray-800">Tipos de chamado</h1>
        <x-button href="{{ route('admin.tipos-chamado.create') }}">+ Novo Tipo</x-button>
    </div>
    <p class="text-sm text-gray-500 mb-6">
        As horas estimadas de cada tipo, multiplicadas pelo valor-hora da empresa, geram o pré-orçamento na abertura do chamado.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Planejamento</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desenvolvimento</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">QA</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($tipos as $tipo)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $tipo->descricao }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ Orcamento::formatarHoras($tipo->horas_planejamento) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ Orcamento::formatarHoras($tipo->horas_desenvolvimento) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ Orcamento::formatarHoras($tipo->horas_qa) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right font-medium">{{ Orcamento::formatarHoras($tipo->totalHoras()) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.tipos-chamado.edit', $tipo) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
