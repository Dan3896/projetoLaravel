@use('App\Models\Orcamento')
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Orçamento OS #{{ $chamado->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #1d4ed8; }
        .sub { color: #6b7280; margin-bottom: 24px; }
        .dados td { padding: 2px 12px 2px 0; vertical-align: top; }
        .dados .rotulo { color: #6b7280; }
        table.itens { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.itens th { text-align: left; border-bottom: 2px solid #d1d5db; padding: 6px; background: #f3f4f6; }
        table.itens td { border-bottom: 1px solid #e5e7eb; padding: 6px; }
        table.itens .num { text-align: right; }
        table.itens tfoot td { font-weight: bold; border-top: 2px solid #d1d5db; border-bottom: none; }
        .obs { margin-top: 24px; white-space: pre-wrap; }
        .rodape { margin-top: 40px; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <h1>OiCram · Orçamento</h1>
    <div class="sub">Ordem de Serviço #{{ $chamado->id }} · emitido em {{ now()->format('d/m/Y') }}</div>

    <table class="dados">
        <tr><td class="rotulo">Empresa</td><td>{{ $chamado->cliente->nome_empresa }}</td></tr>
        <tr><td class="rotulo">Tipo</td><td>{{ $chamado->tipoChamado->descricao }}</td></tr>
        <tr><td class="rotulo">Situação</td><td>{{ ucfirst($orcamento->status) }}</td></tr>
        <tr><td class="rotulo">Solicitação</td><td>{{ \Illuminate\Support\Str::limit($chamado->descricao, 400) }}</td></tr>
    </table>

    <table class="itens">
        <thead>
            <tr>
                <th>Componente</th>
                <th class="num">Horas</th>
                <th class="num">Valor-hora</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orcamento->componentes() as $componente)
                <tr>
                    <td>{{ $componente['rotulo'] }}</td>
                    <td class="num">{{ Orcamento::formatarHoras($componente['horas']) }}</td>
                    <td class="num">{{ Orcamento::formatarMoeda($orcamento->valor_hora) }}</td>
                    <td class="num">{{ Orcamento::formatarMoeda($componente['valor']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="num">{{ Orcamento::formatarHoras($orcamento->totalHoras()) }}</td>
                <td></td>
                <td class="num">{{ Orcamento::formatarMoeda($orcamento->valor_total) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($orcamento->observacoes)
        <div class="obs"><strong>Observações:</strong><br>{{ $orcamento->observacoes }}</div>
    @endif

    <div class="rodape">Valores estimados com base nas horas previstas para o tipo de solicitação e podem ser revistos após a análise técnica.</div>
</body>
</html>
