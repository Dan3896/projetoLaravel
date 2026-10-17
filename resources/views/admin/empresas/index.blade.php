@extends('layouts.main')

@section('title', 'Empresas')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Empresas</h1>
        <x-button href="{{ route('admin.empresas.create') }}">+ Nova Empresa</x-button>
    </div>

    @if($empresas->isEmpty())
        <div class="text-center py-10 text-gray-500">Nenhuma empresa cadastrada.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuários</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chamados</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($empresas as $empresa)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $empresa->nome_empresa }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $empresa->perfil ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $empresa->users_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $empresa->chamados_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.empresas.edit', $empresa) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
