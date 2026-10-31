@use('App\Models\Orcamento')

@php
    $orcamento = $chamado->orcamento;
    $coresStatus = [
        Orcamento::PENDENTE => 'bg-gray-100 text-gray-700',
        Orcamento::ENVIADO => 'bg-blue-100 text-blue-800',
        Orcamento::APROVADO => 'bg-green-100 text-green-800',
        Orcamento::RECUSADO => 'bg-red-100 text-red-800',
    ];
    $rotulosStatus = [
        Orcamento::PENDENTE => $isAdmin ? 'Pendente (não enviado)' : 'Pendente',
        Orcamento::ENVIADO => $isAdmin ? 'Enviado, aguardando o cliente' : 'Aguardando sua aprovação',
        Orcamento::APROVADO => 'Aprovado',
        Orcamento::RECUSADO => 'Recusado',
    ];
@endphp

@if($isAdmin || ($orcamento && $orcamento->visivelParaCliente()))
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-bold text-gray-800">Orçamento</h2>
            @if($orcamento)
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $coresStatus[$orcamento->status] ?? '' }}">
                    {{ $rotulosStatus[$orcamento->status] ?? $orcamento->status }}
                </span>
            @endif
        </div>

        @if(! $orcamento)
            <p class="text-gray-500 mb-4">
                Nenhum orçamento gerado.
                @unless($chamado->cliente->precoCliente)
                    A empresa não tem valor-hora cadastrado, por isso o pré-orçamento não foi criado na abertura.
                @endunless
            </p>
            @unless($chamado->estaEncerrado())
                <form action="{{ route('chamados.orcamentos.store', $chamado) }}" method="POST">
                    @csrf
                    <x-button type="submit">Criar orçamento</x-button>
                </form>
            @endunless
        @else
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-4">Componente</th>
                            <th class="py-2 pr-4 text-right">Horas</th>
                            <th class="py-2 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orcamento->componentes() as $componente)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pr-4">{{ $componente['rotulo'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ Orcamento::formatarHoras($componente['horas']) }}</td>
                                <td class="py-2 text-right">{{ Orcamento::formatarMoeda($componente['valor']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td class="py-2 pr-4">Total</td>
                            <td class="py-2 pr-4 text-right">{{ Orcamento::formatarHoras($orcamento->totalHoras()) }}</td>
                            <td class="py-2 text-right">{{ Orcamento::formatarMoeda($orcamento->valor_total) }}</td>
                        </tr>
                    </tfoot>
                </table>
                <p class="text-xs text-gray-500 mt-1">Valor-hora: {{ Orcamento::formatarMoeda($orcamento->valor_hora) }}</p>
            </div>

            @if($orcamento->observacoes)
                <p class="text-gray-700 whitespace-pre-wrap mb-4">{{ $orcamento->observacoes }}</p>
            @endif

            @if($orcamento->status === Orcamento::RECUSADO && $orcamento->motivo_recusa)
                <p class="text-sm text-red-700 mb-4">Motivo da recusa: {{ $orcamento->motivo_recusa }}</p>
            @endif

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('chamados.orcamentos.pdf', [$chamado, $orcamento]) }}"
                   class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition">
                    Baixar PDF
                </a>

                @if($isAdmin && $orcamento->editavel() && ! $chamado->estaEncerrado())
                    <x-button href="{{ route('chamados.orcamentos.edit', [$chamado, $orcamento]) }}">
                        {{ $orcamento->status === Orcamento::RECUSADO ? 'Revisar e reenviar' : 'Ajustar e enviar' }}
                    </x-button>
                @endif
            </div>

            @can('responderOrcamento', [$chamado, $orcamento])
                <div class="border-t border-gray-100 mt-4 pt-4">
                    @if($isAdmin)
                        <p class="text-sm text-gray-500 mb-3">Registrar a resposta do cliente recebida por outro canal:</p>
                    @endif
                    <div class="flex flex-col sm:flex-row gap-4 sm:items-start">
                        <form action="{{ route('chamados.orcamentos.responder', [$chamado, $orcamento]) }}" method="POST"
                              onsubmit="return confirm('Aprovar o orçamento de {{ Orcamento::formatarMoeda($orcamento->valor_total) }}?');">
                            @csrf
                            <input type="hidden" name="decisao" value="aprovar">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition font-medium">
                                Aprovar orçamento
                            </button>
                        </form>

                        <details class="flex-1">
                            <summary class="cursor-pointer font-medium text-red-700 py-2">Recusar</summary>
                            <form action="{{ route('chamados.orcamentos.responder', [$chamado, $orcamento]) }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="decisao" value="recusar">
                                <label for="motivo-recusa" class="block text-gray-700 text-sm mb-1">Motivo (opcional)</label>
                                <textarea name="motivo" id="motivo-recusa" rows="2" maxlength="500"
                                          class="w-full border border-gray-300 rounded-md p-2 mb-2"></textarea>
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">
                                    Confirmar recusa
                                </button>
                            </form>
                        </details>
                    </div>
                </div>
            @endcan
        @endif
    </div>
@endif
