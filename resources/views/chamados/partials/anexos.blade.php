<div class="bg-white p-6 rounded-lg shadow mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Anexos ({{ $chamado->anexos->count() }})</h2>

    @if($chamado->anexos->isEmpty())
        <p class="text-gray-500 mb-4">Nenhum arquivo anexado.</p>
    @else
        <ul class="divide-y divide-gray-100 mb-4">
            @foreach($chamado->anexos as $anexo)
                <li class="py-2 flex flex-wrap items-center justify-between gap-2">
                    <a href="{{ route('chamados.anexos.show', [$chamado, $anexo]) }}" class="text-indigo-600 hover:text-indigo-900 break-all">
                        {{ $anexo->nome_arquivo }}
                    </a>
                    <span class="flex items-center gap-3 text-sm text-gray-500">
                        {{ $anexo->tamanhoFormatado() }} · {{ $anexo->created_at->format('d/m/Y H:i') }}
                        @if($isAdmin)
                            <form action="{{ route('chamados.anexos.destroy', [$chamado, $anexo]) }}" method="POST"
                                  onsubmit="return confirm(@js('Excluir o anexo ' . $anexo->nome_arquivo . '?'));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Excluir</button>
                            </form>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    @can('anexar', $chamado)
        <form action="{{ route('chamados.anexos.store', $chamado) }}" method="POST" enctype="multipart/form-data"
              class="border-t border-gray-100 pt-4">
            @csrf
            <label for="anexos" class="block text-gray-700 font-medium mb-2">Enviar arquivos</label>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-start">
                <div class="flex-1">
                    <x-campo-anexos required />
                </div>
                <x-button type="submit">Enviar</x-button>
            </div>
        </form>
    @endcan
</div>
