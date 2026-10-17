<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Status;
use App\Models\TipoChamado;
use App\Models\User;
use App\Services\SlaService;
use App\Services\TriagemService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TipoChamadoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TriagemSlaTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, StatusSeeder::class, TipoChamadoSeeder::class]);
        $this->cliente = Cliente::create(['nome_empresa' => 'Empresa X']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function usuario(string $papel): User
    {
        $user = User::factory()->create(['cliente_id' => $papel === 'cliente' ? $this->cliente->id : null]);
        $user->assignRole($papel);

        return $user;
    }

    private function abrirChamado(): Chamado
    {
        $chamado = Chamado::create([
            'cliente_id' => $this->cliente->id,
            'tipo_chamado_id' => TipoChamado::first()->id,
            'status_id' => Status::where('descricao', 'ABERTO')->first()->id,
            'descricao' => 'O sistema caiu e ninguém consegue emitir nota fiscal.',
            'criticidade_cliente' => 'Alta',
            'aberto_em' => now(),
        ]);

        app(SlaService::class)->registrarAbertura($chamado);

        return $chamado;
    }

    private function dadosTriagem(array $sobrescrever = []): array
    {
        return array_merge([
            'gut_gravidade' => 4,
            'gut_urgencia' => 5,
            'gut_tendencia' => 3,
            'tipo_chamado_id' => TipoChamado::first()->id,
            'proximo_status' => 'EM_ANALISE',
        ], $sobrescrever);
    }

    public function test_abertura_registra_status_aberto_no_historico(): void
    {
        $this->actingAs($this->usuario('cliente'))->post('/chamados', [
            'cliente_id' => $this->cliente->id,
            'tipo_chamado_id' => TipoChamado::first()->id,
            'descricao' => 'Relatório de vendas não abre desde ontem.',
            'criticidade_cliente' => 'Média',
        ])->assertRedirect(route('chamados.index'));

        $chamado = Chamado::first();

        $this->assertCount(1, $chamado->historicosSla);
        $this->assertSame('ABERTO', $chamado->historicosSla->first()->status->descricao);

        $this->actingAs($this->usuario('admin'))->get('/chamados')->assertOk()->assertSee('Não triado');
    }

    public function test_cliente_nao_acessa_triagem_nem_painel(): void
    {
        $chamado = $this->abrirChamado();
        $cliente = $this->usuario('cliente');

        $this->actingAs($cliente)->get(route('chamados.triagem.create', $chamado))->assertForbidden();
        $this->actingAs($cliente)->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem())->assertForbidden();
        $this->actingAs($cliente)->patch(route('chamados.status.update', $chamado), ['status' => 'CONCLUIDO'])->assertForbidden();
        $this->actingAs($cliente)->get(route('dashboard'))->assertForbidden();

        $this->assertNull($chamado->fresh()->triagem);
    }

    public function test_admin_abre_tela_de_triagem(): void
    {
        $chamado = $this->abrirChamado();

        $this->actingAs($this->usuario('admin'))
            ->get(route('chamados.triagem.create', $chamado))
            ->assertOk()
            ->assertSee('Matriz GUT');
    }

    public function test_admin_registra_triagem_e_move_o_chamado(): void
    {
        $chamado = $this->abrirChamado();

        $this->actingAs($this->usuario('admin'))
            ->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem())
            ->assertRedirect(route('chamados.show', $chamado));

        $chamado->refresh();

        $this->assertSame(60, $chamado->triagem->pontuacao);
        $this->assertSame('Média', $chamado->triagem->prioridade());
        $this->assertSame('EM_ANALISE', $chamado->status->descricao);
        $this->assertNotNull($chamado->inicio_planejamento);
        $this->assertSame(['ABERTO', 'EM_ANALISE'], $chamado->historicosSla->map->status->pluck('descricao')->all());
    }

    public function test_refazer_triagem_atualiza_o_registro_existente(): void
    {
        $chamado = $this->abrirChamado();
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem(['proximo_status' => 'EM_TRIAGEM']));
        $this->actingAs($admin)->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem(['gut_gravidade' => 5, 'gut_tendencia' => 5]));

        $this->assertDatabaseCount('chamado_triagens', 1);
        $this->assertSame(125, $chamado->fresh()->triagem->pontuacao);
    }

    public function test_triagem_valida_notas_de_1_a_5(): void
    {
        $chamado = $this->abrirChamado();

        $this->actingAs($this->usuario('admin'))
            ->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem(['gut_gravidade' => 6, 'gut_urgencia' => 0, 'proximo_status' => 'CONCLUIDO']))
            ->assertSessionHasErrors(['gut_gravidade', 'gut_urgencia', 'proximo_status']);

        $this->assertNull($chamado->fresh()->triagem);
    }

    public function test_chamado_encerrado_nao_pode_ser_triado(): void
    {
        $chamado = $this->abrirChamado();
        app(SlaService::class)->mudarStatus($chamado, 'CANCELADO');

        $this->actingAs($this->usuario('admin'))
            ->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem())
            ->assertRedirect(route('chamados.show', $chamado))
            ->assertSessionHas('error');

        $this->assertNull($chamado->fresh()->triagem);
    }

    public function test_prioridade_segue_as_faixas_da_matriz_gut(): void
    {
        $this->assertSame('Baixa', TriagemService::prioridade(24));
        $this->assertSame('Média', TriagemService::prioridade(25));
        $this->assertSame('Média', TriagemService::prioridade(60));
        $this->assertSame('Alta', TriagemService::prioridade(61));
        $this->assertSame('Alta', TriagemService::prioridade(99));
        $this->assertSame('Crítica', TriagemService::prioridade(100));
        $this->assertSame('Não triado', TriagemService::prioridade(null));
    }

    public function test_sla_pausa_enquanto_pendente_do_cliente(): void
    {
        $sla = app(SlaService::class);
        $inicio = Carbon::parse('2026-10-01 08:00');

        Carbon::setTestNow($inicio);
        $chamado = $this->abrirChamado();

        Carbon::setTestNow($inicio->copy()->addHour());
        $sla->mudarStatus($chamado, 'EM_ANALISE');

        Carbon::setTestNow($inicio->copy()->addHours(2));
        $sla->mudarStatus($chamado, 'PENDENTE_CLIENTE');

        Carbon::setTestNow($inicio->copy()->addHours(5));
        $sla->mudarStatus($chamado, 'EM_ANALISE');

        Carbon::setTestNow($inicio->copy()->addHours(6));
        $tempo = $sla->calcular($chamado->fresh());

        $this->assertSame(3 * 3600, $tempo['liquido']);
        $this->assertSame(3 * 3600, $tempo['pausado']);
    }

    public function test_sla_para_de_contar_quando_o_chamado_e_concluido(): void
    {
        $sla = app(SlaService::class);
        $inicio = Carbon::parse('2026-10-01 08:00');

        Carbon::setTestNow($inicio);
        $chamado = $this->abrirChamado();

        Carbon::setTestNow($inicio->copy()->addHours(2));
        $sla->mudarStatus($chamado, 'CONCLUIDO');

        Carbon::setTestNow($inicio->copy()->addDays(3));
        $chamado->refresh();

        $this->assertSame(2 * 3600, $sla->calcular($chamado)['liquido']);
        $this->assertEquals($inicio->copy()->addHours(2), $chamado->fechado_em);
    }

    public function test_marcos_de_planejamento_dev_e_qa_sao_preenchidos(): void
    {
        $sla = app(SlaService::class);
        $chamado = $this->abrirChamado();

        foreach (['EM_ANALISE', 'APROVADO', 'EM_DESENVOLVIMENTO', 'EM_QA', 'CONCLUIDO'] as $status) {
            $sla->mudarStatus($chamado, $status);
        }

        $chamado->refresh();

        foreach (['inicio_planejamento', 'fim_planejamento', 'inicio_dev', 'fim_dev', 'inicio_qa', 'fim_qa', 'fechado_em'] as $marco) {
            $this->assertNotNull($chamado->{$marco}, "Marco {$marco} não foi preenchido.");
        }
    }

    public function test_painel_ordena_a_fila_pela_pontuacao_gut(): void
    {
        $admin = $this->usuario('admin');
        $baixa = $this->abrirChamado();
        $critica = $this->abrirChamado();
        $semTriagem = $this->abrirChamado();

        $this->actingAs($admin)->post(route('chamados.triagem.store', $baixa), $this->dadosTriagem(['gut_gravidade' => 1, 'gut_urgencia' => 2, 'gut_tendencia' => 2]));
        $this->actingAs($admin)->post(route('chamados.triagem.store', $critica), $this->dadosTriagem(['gut_gravidade' => 5, 'gut_urgencia' => 5, 'gut_tendencia' => 5]));

        $resposta = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $fila = $resposta->viewData('fila');
        $this->assertSame([$critica->id, $baixa->id], $fila->pluck('id')->all());
        $this->assertSame([$semTriagem->id], $resposta->viewData('aguardandoTriagem')->pluck('id')->all());
        $this->assertSame(1, $resposta->viewData('indicadores')['Críticos']);
    }

    public function test_detalhe_do_chamado_mostra_triagem_e_linha_do_tempo(): void
    {
        $chamado = $this->abrirChamado();
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post(route('chamados.triagem.store', $chamado), $this->dadosTriagem());

        $this->actingAs($admin)
            ->get(route('chamados.show', $chamado))
            ->assertOk()
            ->assertSee('Triagem GUT')
            ->assertSee('Linha do tempo')
            ->assertSee('EM ANALISE');
    }

    public function test_admin_altera_status_e_historico_registra(): void
    {
        $chamado = $this->abrirChamado();

        $this->actingAs($this->usuario('admin'))
            ->patch(route('chamados.status.update', $chamado), ['status' => 'PENDENTE_CLIENTE'])
            ->assertRedirect(route('chamados.show', $chamado));

        $this->assertSame('PENDENTE_CLIENTE', $chamado->fresh()->status->descricao);
        $this->assertCount(2, $chamado->fresh()->historicosSla);
    }
}
