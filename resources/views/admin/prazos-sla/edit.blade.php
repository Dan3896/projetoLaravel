@extends('layouts.main')

@section('title', 'Prazos de SLA')

@section('content')
<div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Prazos de SLA</h1>
    <p class="text-sm text-gray-500 mb-6">
        Tempo máximo de atendimento, em horas, conforme a prioridade da triagem GUT. O tempo em "Pendente cliente" não conta.
        Deixe em branco para não ter prazo naquela prioridade.
    </p>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-700 border border-red-400 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.prazos-sla.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-4 mb-6">
            @foreach($prioridades as $prioridade)
                <div class="flex items-center gap-4">
                    <label for="prazo-{{ $loop->index }}" class="w-24 font-medium text-gray-800">{{ $prioridade }}</label>
                    <input type="number" min="1" max="8760" name="prazos[{{ $prioridade }}]" id="prazo-{{ $loop->index }}"
                           value="{{ old("prazos.{$prioridade}", $prazos[$prioridade] ?? '') }}"
                           class="w-32 px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-gray-500 text-sm">horas</span>
                </div>
            @endforeach
        </div>

        <x-button type="submit">Salvar prazos</x-button>
    </form>
</div>
@endsection
