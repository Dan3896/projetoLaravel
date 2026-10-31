@extends('layouts.main')

@use('App\Models\Orcamento')

@section('title', 'Orçamento da OS #' . $chamado->id)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Orçamento da OS #{{ $chamado->id }}</h1>
        <a href="{{ route('chamados.show', $chamado) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">&larr; Voltar</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6 text-sm text-gray-700 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Empresa</span>
            {{ $chamado->cliente->nome_empresa }}
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Valor-hora cadastrado</span>
            {{ $chamado->cliente->precoCliente ? Orcamento::formatarMoeda($chamado->cliente->precoCliente->valor_hora) : 'Não cadastrado' }}
        </div>
        <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Estimativa do tipo ({{ $chamado->tipoChamado->descricao }})</span>
            {{ Orcamento::formatarHoras($chamado->tipoChamado->horas_planejamento) }} planej. ·
            {{ Orcamento::formatarHoras($chamado->tipoChamado->horas_desenvolvimento) }} dev ·
            {{ Orcamento::formatarHoras($chamado->tipoChamado->horas_qa) }} QA
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chamados.orcamentos.update', [$chamado, $orcamento]) }}" method="POST"
          class="bg-white p-6 rounded-lg shadow"
          x-data="{
              valorHora: @js((float) old('valor_hora', $orcamento->valor_hora)),
              horas: {
                  planejamento: @js((float) old('horas_planejamento', $orcamento->horas_planejamento)),
                  desenvolvimento: @js((float) old('horas_desenvolvimento', $orcamento->horas_desenvolvimento)),
                  qa: @js((float) old('horas_qa', $orcamento->horas_qa)),
              },
              valor(h) { return Math.round((Number(h) || 0) * (Number(this.valorHora) || 0) * 100) / 100; },
              total() { return this.valor(this.horas.planejamento) + this.valor(this.horas.desenvolvimento) + this.valor(this.horas.qa); },
              moeda(v) { return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); },
          }">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="valor_hora" class="block text-gray-700 font-medium mb-2">Valor-hora (R$)</label>
            <input type="number" step="0.01" min="0" name="valor_hora" id="valor_hora" required x-model="valorHora"
                   value="{{ old('valor_hora', $orcamento->valor_hora) }}"
                   class="w-48 border border-gray-300 rounded-md p-2">
        </div>

        <table class="min-w-full text-sm mb-6">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2 pr-4">Componente</th>
                    <th class="py-2 pr-4">Horas</th>
                    <th class="py-2 text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach(Orcamento::COMPONENTES as $chave => $rotulo)
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4"><label for="horas_{{ $chave }}">{{ $rotulo }}</label></td>
                        <td class="py-2 pr-4">
                            <input type="number" step="0.25" min="0" name="horas_{{ $chave }}" id="horas_{{ $chave }}" required
                                   x-model="horas.{{ $chave }}" value="{{ old("horas_{$chave}", $orcamento->{"horas_{$chave}"}) }}"
                                   class="w-28 border border-gray-300 rounded-md p-2">
                        </td>
                        <td class="py-2 text-right" x-text="moeda(valor(horas.{{ $chave }}))">
                            {{ Orcamento::formatarMoeda($orcamento->componentes()[$chave]['valor']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-bold">
                    <td class="py-2 pr-4" colspan="2">Total</td>
                    <td class="py-2 text-right" x-text="moeda(total())">{{ Orcamento::formatarMoeda($orcamento->valor_total) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="mb-6">
            <label for="observacoes" class="block text-gray-700 font-medium mb-2">Observações para o cliente (opcional)</label>
            <textarea name="observacoes" id="observacoes" rows="3" maxlength="2000"
                      class="w-full border border-gray-300 rounded-md p-2">{{ old('observacoes', $orcamento->observacoes) }}</textarea>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <button type="submit" name="acao" value="salvar"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-50 transition font-medium">
                Salvar rascunho
            </button>
            <x-button type="submit" name="acao" value="enviar"
                      onclick="return confirm('Enviar o orçamento ao cliente? Depois de enviado ele não pode mais ser alterado.');">
                Salvar e enviar ao cliente
            </x-button>
        </div>
    </form>
</div>
@endsection
