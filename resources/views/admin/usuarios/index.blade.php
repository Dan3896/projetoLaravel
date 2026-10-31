@extends('layouts.main')

@section('title', 'Usuários')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Usuários</h1>
        <x-button href="{{ route('admin.usuarios.create') }}">+ Novo Usuário</x-button>
    </div>

    @if($usuarios->isEmpty())
        <div class="text-center py-10 text-gray-500">
            Nenhum usuário encontrado.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Papel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acesso</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($usuarios as $usuario)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $usuario->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $usuario->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $usuario->roles->pluck('name')->implode(', ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $usuario->cliente?->nome_empresa ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($usuario->ativo)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">Desativado</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                @unless($usuario->is(auth()->user()))
                                    <form action="{{ route('admin.usuarios.ativo', $usuario) }}" method="POST" class="inline ml-3"
                                          onsubmit="return confirm(@js(($usuario->ativo ? 'Desativar o acesso de ' : 'Reativar o acesso de ') . $usuario->name . '?'));">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="{{ $usuario->ativo ? 'text-red-600 hover:text-red-800' : 'text-green-700 hover:text-green-900' }}">
                                            {{ $usuario->ativo ? 'Desativar' : 'Reativar' }}
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
