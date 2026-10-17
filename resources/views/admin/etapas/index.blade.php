@extends('layouts.main')

@section('title', 'Etapas do chamado')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-2">
        <h1 class="text-2xl font-bold text-gray-800">Etapas do chamado</h1>
        <x-button href="{{ route('admin.etapas.create') }}">+ Nova Etapa</x-button>
    </div>
    <p class="text-sm text-gray-500 mb-6">
        São os passos que o cliente vê no andamento do chamado, na ordem abaixo. Novos chamados começam na primeira etapa.
    </p>

    @if($etapas->isEmpty())
        <div class="text-center py-10 text-gray-500">Nenhuma etapa cadastrada.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição para o cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chamados</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($etapas as $etapa)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $etapa->ordem }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $etapa->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $etapa->descricao ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $etapa->chamados_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.etapas.edit', $etapa) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                @if($etapa->chamados_count === 0)
                                    <form action="{{ route('admin.etapas.destroy', $etapa) }}" method="POST" class="inline ml-3"
                                          onsubmit="return confirm(@js('Excluir a etapa ' . $etapa->nome . '?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Excluir</button>
                                    </form>
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
