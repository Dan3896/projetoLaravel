<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\ChamadoAnexo;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\PrazoSla;
use App\Models\PrecoCliente;
use App\Models\TipoChamado;
use App\Models\User;
use App\Services\ChamadoService;
use App\Services\OrcamentoService;
use App\Services\SlaService;
use App\Services\TriagemService;
use Database\Seeders\EtapaSeeder;
use Database\Seeders\PrazoSlaSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TipoChamadoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AnexoOrcamentoPrazoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $empresaA;

    private Cliente $empresaB;

    private TipoChamado $bug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake(ChamadoAnexo::DISCO);
        $this->seed([RoleSeeder::class, StatusSeeder::class, TipoChamadoSeeder::class, EtapaSeeder::class, PrazoSlaSeeder::class]);

        $this->empresaA = Cliente::create(['nome_empresa' => 'Empresa A']);
        $this->empresaB = Cliente::create(['nome_empresa' => 'Empresa B']);
        $this->bug = TipoChamado::where('descricao', 'Defeito / Bug')->firstOrFail(); // 0,5h + 2h + 1h
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function clienteDe(Cliente $empresa): User
    {
        $user = User::factory()->create(['cliente_id' => $empresa->id]);
        $user->assignRole('cliente');

        return $user;
    }

    private function chamadoDe(Cliente $empresa, array $anexos = []): Chamado
    {
        return app(ChamadoService::class)->abrir([
            'cliente_id' => $empresa->id,
            'tipo_chamado_id' => $this->bug->id,
            'descricao' => 'O caixa 2 trava ao emitir a nota fiscal eletrônica.',
            'criticidade_cliente' => 'Alta',
        ], $anexos);
    }

    private function dadosAbertura(array $sobrescrever = []): array
    {
        return array_merge([
            'tipo_chamado_id' => $this->bug->id,
            'descricao' => 'O caixa 2 trava ao emitir a nota fiscal eletrônica.',
        ], $sobrescrever);
    }

    // --- RF-03: anexos ---

    public function test_cliente_abre_chamado_com_anexos(): void
    {
        $this->actingAs($this->clienteDe($this->empresaA))->post('/chamados', $this->dadosAbertura([
            'anexos' => [
                UploadedFile::fake()->image('erro.png'),
                UploadedFile::fake()->create('sistema.log', 20, 'text/plain'),
            ],
        ]))->assertSessionHasNoErrors();

        $chamado = Chamado::firstOrFail();
        $this->assertCount(2, $chamado->anexos);
        $this->assertSame('erro.png', $chamado->anexos[0]->nome_arquivo);

        foreach ($chamado->anexos as $anexo) {
            $this->assertStringStartsWith("chamados/{$chamado->id}/", $anexo->caminho);
            Storage::disk(ChamadoAnexo::DISCO)->assertExists($anexo->caminho);
        }
    }

    public function test_bloqueia_executaveis_e_arquivos_acima_de_10_mb(): void
    {
        $cliente = $this->clienteDe($this->empresaA);

        foreach ([
            UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
            UploadedFile::fake()->create('instalar.exe', 1, 'application/x-msdownload'),
            UploadedFile::fake()->create('deploy.sh', 1, 'text/x-shellscript'),
            UploadedFile::fake()->create('grande.pdf', ChamadoAnexo::TAMANHO_MAXIMO_KB + 1, 'application/pdf'),
        ] as $arquivo) {
            $this->actingAs($cliente)
                ->post('/chamados', $this->dadosAbertura(['anexos' => [$arquivo]]))
                ->assertSessionHasErrors('anexos.0');
        }

        $this->assertDatabaseCount('chamados', 0);
    }

    public function test_descricao_exige_20_caracteres(): void
    {
        $this->actingAs($this->clienteDe($this->empresaA))
            ->post('/chamados', $this->dadosAbertura(['descricao' => 'Não funciona.']))
            ->assertSessionHasErrors('descricao');
    }

    public function test_falha_na_abertura_desfaz_chamado_e_apaga_arquivos(): void
    {
        $this->mock(OrcamentoService::class)
            ->shouldReceive('gerarPrevia')
            ->andThrow(new RuntimeException('falha simulada'));

        try {
            $this->chamadoDe($this->empresaA, [UploadedFile::fake()->image('erro.png')]);
            $this->fail('A abertura deveria ter falhado.');
        } catch (RuntimeException $erro) {
            $this->assertSame('falha simulada', $erro->getMessage());
        }

        $this->assertDatabaseCount('chamados', 0);
        $this->assertDatabaseCount('chamado_anexos', 0);
        $this->assertEmpty(Storage::disk(ChamadoAnexo::DISCO)->allFiles());
    }

    public function test_download_do_anexo_respeita_a_empresa(): void
    {
        $chamado = $this->chamadoDe($this->empresaA, [UploadedFile::fake()->create('relatorio.pdf', 10, 'application/pdf')]);
        $anexo = $chamado->anexos()->firstOrFail();

        $this->actingAs($this->clienteDe($this->empresaA))
            ->get(route('chamados.anexos.show', [$chamado, $anexo]))
            ->assertOk()
            ->assertDownload('relatorio.pdf');

        $this->actingAs($this->clienteDe($this->empresaB))
            ->get(route('chamados.anexos.show', [$chamado, $anexo]))
            ->assertForbidden();
    }

    public function test_anexo_de_outro_chamado_nao_e_encontrado_pela_url(): void
    {
        $chamado = $this->chamadoDe($this->empresaA, [UploadedFile::fake()->image('a.png')]);
        $outro = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->admin())
            ->get(route('chamados.anexos.show', [$outro, $chamado->anexos()->first()]))
            ->assertNotFound();
    }

    public function test_cliente_envia_anexo_depois_e_so_admin_exclui(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($cliente)
            ->post(route('chamados.anexos.store', $chamado), ['anexos' => [UploadedFile::fake()->image('tela.jpg')]])
            ->assertRedirect(route('chamados.show', $chamado));

        $anexo = $chamado->anexos()->firstOrFail();

        $this->actingAs($cliente)->delete(route('chamados.anexos.destroy', [$chamado, $anexo]))->assertForbidden();

        $this->actingAs($this->admin())->delete(route('chamados.anexos.destroy', [$chamado, $anexo]))->assertRedirect();
        $this->assertModelMissing($anexo);
        Storage::disk(ChamadoAnexo::DISCO)->assertMissing($anexo->caminho);
    }

    public function test_cliente_de_outra_empresa_nao_envia_anexo(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->clienteDe($this->empresaB))
            ->post(route('chamados.anexos.store', $chamado), ['anexos' => [UploadedFile::fake()->image('tela.jpg')]])
            ->assertForbidden();
    }

    // --- RF-04: pré-orçamento ---

    public function test_abertura_gera_pre_orcamento_com_valor_hora_da_empresa(): void
    {
        PrecoCliente::create(['cliente_id' => $this->empresaA->id, 'valor_hora' => 150]);

        $orcamento = $this->chamadoDe($this->empresaA)->orcamento;

        $this->assertSame(Orcamento::PENDENTE, $orcamento->status);
        $this->assertEquals(3.5, $orcamento->totalHoras());
        $this->assertEquals(525.00, (float) $orcamento->valor_total);
        $this->assertEquals(300.00, $orcamento->componentes()['desenvolvimento']['valor']);
    }

    public function test_sem_valor_hora_nao_gera_orcamento_e_admin_cria_manualmente(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $this->assertNull($chamado->orcamento);

        $this->actingAs($this->admin())
            ->post(route('chamados.orcamentos.store', $chamado))
            ->assertRedirect();

        $orcamento = $chamado->fresh()->orcamento;
        $this->assertEquals(0, (float) $orcamento->valor_hora);
        $this->assertEquals(3.5, $orcamento->totalHoras());
    }

    public function test_cliente_nao_ve_orcamento_pendente(): void
    {
        PrecoCliente::create(['cliente_id' => $this->empresaA->id, 'valor_hora' => 150]);
        $chamado = $this->chamadoDe($this->empresaA);
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($cliente)->get(route('chamados.show', $chamado))->assertOk()->assertDontSee('R$ 525,00');
        $this->actingAs($cliente)->get(route('chamados.orcamentos.pdf', [$chamado, $chamado->orcamento]))->assertForbidden();
    }

    public function test_admin_ajusta_e_envia_e_cliente_aprova(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $orcamento = app(OrcamentoService::class)->criar($chamado);

        $this->actingAs($this->admin())->put(route('chamados.orcamentos.update', [$chamado, $orcamento]), [
            'valor_hora' => 200,
            'horas_planejamento' => 1,
            'horas_desenvolvimento' => 4,
            'horas_qa' => 1.5,
            'observacoes' => 'Inclui deploy em homologação.',
            'acao' => 'enviar',
        ])->assertRedirect(route('chamados.show', $chamado));

        $orcamento->refresh();
        $this->assertSame(Orcamento::ENVIADO, $orcamento->status);
        $this->assertEquals(1300.00, (float) $orcamento->valor_total);
        $this->assertSame('ORCAMENTO_GERADO', $chamado->fresh()->status->descricao);

        $cliente = $this->clienteDe($this->empresaA);
        $this->actingAs($cliente)->get(route('chamados.show', $chamado))->assertSee('R$ 1.300,00')->assertSee('Aprovar orçamento');

        $this->actingAs($cliente)
            ->post(route('chamados.orcamentos.responder', [$chamado, $orcamento]), ['decisao' => 'aprovar'])
            ->assertRedirect(route('chamados.show', $chamado));

        $this->assertSame(Orcamento::APROVADO, $orcamento->fresh()->status);
        $this->assertSame('APROVADO', $chamado->fresh()->status->descricao);

        // Depois de respondido não dá para responder de novo nem editar.
        $this->actingAs($cliente)
            ->post(route('chamados.orcamentos.responder', [$chamado, $orcamento]), ['decisao' => 'recusar'])
            ->assertForbidden();
        $this->actingAs($this->admin())
            ->get(route('chamados.orcamentos.edit', [$chamado, $orcamento]))
            ->assertSessionHas('error');
    }

    public function test_cliente_recusa_e_admin_pode_revisar(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $orcamento = app(OrcamentoService::class)->criar($chamado);
        $admin = $this->admin();
        app(OrcamentoService::class)->enviar($orcamento, $admin);

        $this->actingAs($this->clienteDe($this->empresaA))
            ->post(route('chamados.orcamentos.responder', [$chamado, $orcamento]), ['decisao' => 'recusar', 'motivo' => 'Acima do orçamento.'])
            ->assertRedirect();

        $orcamento->refresh();
        $this->assertSame(Orcamento::RECUSADO, $orcamento->status);
        $this->assertSame('Acima do orçamento.', $orcamento->motivo_recusa);
        $this->assertStringContainsString('Acima do orçamento.', $chamado->historicosInternos()->first()->texto);

        $this->actingAs($admin)->get(route('chamados.orcamentos.edit', [$chamado, $orcamento]))->assertOk();
    }

    public function test_cliente_de_outra_empresa_nao_responde_orcamento(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $orcamento = app(OrcamentoService::class)->criar($chamado);
        app(OrcamentoService::class)->enviar($orcamento, $this->admin());

        $this->actingAs($this->clienteDe($this->empresaB))
            ->post(route('chamados.orcamentos.responder', [$chamado, $orcamento]), ['decisao' => 'aprovar'])
            ->assertForbidden();

        $this->assertSame(Orcamento::ENVIADO, $orcamento->fresh()->status);
    }

    public function test_pdf_do_orcamento(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $orcamento = app(OrcamentoService::class)->criar($chamado);

        $resposta = $this->actingAs($this->admin())->get(route('chamados.orcamentos.pdf', [$chamado, $orcamento]));

        $resposta->assertOk()->assertDownload("orcamento-os-{$chamado->id}.pdf");
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_admin_cadastra_valor_hora_da_empresa_e_horas_do_tipo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.empresas.update', $this->empresaA), [
            'nome_empresa' => 'Empresa A',
            'valor_hora' => '180.50',
        ])->assertRedirect(route('admin.empresas.index'));
        $this->assertEquals(180.50, (float) $this->empresaA->fresh()->precoCliente->valor_hora);

        $this->actingAs($admin)->put(route('admin.empresas.update', $this->empresaA), ['nome_empresa' => 'Empresa A', 'valor_hora' => '']);
        $this->assertNull($this->empresaA->fresh()->precoCliente);

        $this->actingAs($admin)->put(route('admin.tipos-chamado.update', $this->bug), [
            'descricao' => 'Defeito / Bug',
            'horas_planejamento' => 1,
            'horas_desenvolvimento' => 3,
            'horas_qa' => 2,
        ])->assertRedirect(route('admin.tipos-chamado.index'));
        $this->assertEquals(6, $this->bug->fresh()->totalHoras());
    }

    // --- RF-05: prazo de SLA ---

    public function test_prazo_de_sla_por_prioridade_e_alerta_de_atraso(): void
    {
        $inicio = Carbon::parse('2026-10-01 08:00');
        Carbon::setTestNow($inicio);
        $chamado = $this->chamadoDe($this->empresaA);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('chamados.triagem.store', $chamado), [
            'gut_gravidade' => 5, 'gut_urgencia' => 5, 'gut_tendencia' => 5, // Crítica: prazo de 4h
            'tipo_chamado_id' => $this->bug->id,
            'proximo_status' => 'EM_ANALISE',
        ]);

        $sla = app(SlaService::class);

        Carbon::setTestNow($inicio->copy()->addHours(3));
        $prazo = $sla->situacaoPrazo($chamado->fresh());
        $this->assertSame(4, $prazo['horas']);
        $this->assertFalse($prazo['atrasado']);
        $this->assertSame(3600, $prazo['restante']);

        Carbon::setTestNow($inicio->copy()->addHours(5));
        $this->assertTrue(app(SlaService::class)->situacaoPrazo($chamado->fresh())['atrasado']);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertViewHas('indicadores', fn ($indicadores) => $indicadores['Prazo estourado'] === 1);
        $this->actingAs($admin)->get(route('chamados.show', $chamado))->assertSee('atrasado há 1h 0min');
    }

    public function test_chamado_sem_triagem_nao_tem_prazo(): void
    {
        $this->assertNull(app(SlaService::class)->situacaoPrazo($this->chamadoDe($this->empresaA)));
    }

    public function test_admin_configura_prazos_e_vazio_remove(): void
    {
        $prazos = array_fill_keys(TriagemService::PRIORIDADES, 10);
        $prazos['Baixa'] = '';

        $this->actingAs($this->admin())
            ->put(route('admin.prazos-sla.update'), ['prazos' => $prazos])
            ->assertRedirect(route('admin.prazos-sla.edit'));

        $this->assertSame(10, PrazoSla::where('prioridade', 'Crítica')->value('horas'));
        $this->assertNull(PrazoSla::where('prioridade', 'Baixa')->first());
    }

    // --- Usuários, edição de chamado e listagem ---

    public function test_usuario_desativado_nao_entra_e_sessao_aberta_cai(): void
    {
        $cliente = $this->clienteDe($this->empresaA);
        $cliente->update(['password' => 'senha-forte-123']);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.usuarios.ativo', $cliente))->assertRedirect(route('admin.usuarios.index'));
        $this->assertFalse($cliente->fresh()->ativo);

        $this->actingAs($cliente->fresh())->get('/chamados')->assertRedirect(route('login'));

        auth()->logout();
        $this->post('/login', ['email' => $cliente->email, 'password' => 'senha-forte-123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.usuarios.ativo', $cliente));
        $this->assertTrue($cliente->fresh()->ativo);
    }

    public function test_admin_nao_desativa_a_si_mesmo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.usuarios.ativo', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->ativo);
    }

    public function test_admin_edita_chamado_e_cliente_nao(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $melhoria = TipoChamado::where('descricao', 'Melhoria / Nova Funcionalidade')->firstOrFail();
        $dados = [
            'tipo_chamado_id' => $melhoria->id,
            'descricao' => 'Na verdade é uma melhoria no fechamento de caixa.',
            'criticidade_cliente' => 'Média',
        ];

        $this->actingAs($this->clienteDe($this->empresaA))->put(route('chamados.update', $chamado), $dados)->assertForbidden();

        $this->actingAs($this->admin())->get(route('chamados.edit', $chamado))->assertOk();
        $this->actingAs($this->admin())->put(route('chamados.update', $chamado), $dados)->assertRedirect(route('chamados.show', $chamado));

        $chamado->refresh();
        $this->assertSame($melhoria->id, $chamado->tipo_chamado_id);
        $this->assertStringContainsString('Chamado editado', $chamado->historicosInternos()->first()->texto);
    }

    public function test_listagem_busca_filtra_e_pagina(): void
    {
        $alvo = $this->chamadoDe($this->empresaA);
        $alvo->update(['descricao' => 'Impressora fiscal não imprime o cupom.']);
        $this->chamadoDe($this->empresaB);
        app(SlaService::class)->mudarStatus($this->chamadoDe($this->empresaB), 'CANCELADO');
        $admin = $this->admin();

        $resposta = $this->actingAs($admin)->get('/chamados?busca=impressora');
        $this->assertSame([$alvo->id], $resposta->viewData('chamados')->pluck('id')->all());

        $resposta = $this->actingAs($admin)->get('/chamados?busca=' . urlencode('#' . $alvo->id));
        $this->assertSame([$alvo->id], $resposta->viewData('chamados')->pluck('id')->all());

        $cancelado = \App\Models\Status::where('descricao', 'CANCELADO')->value('id');
        $this->assertCount(1, $this->actingAs($admin)->get("/chamados?status={$cancelado}")->viewData('chamados'));
        $this->assertCount(2, $this->actingAs($admin)->get("/chamados?empresa={$this->empresaB->id}")->viewData('chamados'));

        // O cliente não escapa da própria empresa pelo filtro.
        $resposta = $this->actingAs($this->clienteDe($this->empresaA))->get("/chamados?empresa={$this->empresaB->id}");
        $this->assertSame([$alvo->id], $resposta->viewData('chamados')->pluck('id')->all());

        foreach (range(1, 16) as $i) {
            $this->chamadoDe($this->empresaA);
        }
        $this->assertCount(15, $this->actingAs($admin)->get('/chamados')->viewData('chamados'));
        $this->assertCount(4, $this->actingAs($admin)->get('/chamados?page=2')->viewData('chamados'));
    }

    // --- API do bot de triagem ---

    public function test_api_do_bot_exige_token(): void
    {
        config(['services.bot_triagem.token' => null]);
        $this->getJson('/api/bot/tipos-chamado')->assertStatus(503);

        config(['services.bot_triagem.token' => 'segredo-do-bot']);
        $this->getJson('/api/bot/tipos-chamado')->assertUnauthorized();
        $this->withToken('errado')->getJson('/api/bot/tipos-chamado')->assertUnauthorized();
        $this->withToken('segredo-do-bot')->getJson('/api/bot/tipos-chamado')->assertOk()->assertJsonFragment(['descricao' => 'Defeito / Bug']);
    }

    public function test_bot_cria_chamado_com_a_conversa_no_historico_interno(): void
    {
        config(['services.bot_triagem.token' => 'segredo-do-bot']);
        $cliente = $this->clienteDe($this->empresaA);

        $resposta = $this->withToken('segredo-do-bot')->postJson('/api/bot/chamados', [
            'email' => $cliente->email,
            'tipo_chamado_id' => $this->bug->id,
            'descricao' => 'Cliente relata que o caixa trava ao emitir NFC-e.',
            'criticidade_cliente' => 'Alta',
            'conversa' => [
                ['autor' => 'cliente', 'texto' => 'O caixa travou de novo'],
                ['autor' => 'bot', 'texto' => 'Entendi, vou abrir um chamado.'],
            ],
        ]);

        $resposta->assertCreated()->assertJsonStructure(['id', 'url']);

        $chamado = Chamado::findOrFail($resposta->json('id'));
        $this->assertSame($this->empresaA->id, $chamado->cliente_id);
        $this->assertSame('ABERTO', $chamado->status->descricao);
        $this->assertStringContainsString('[bot] Entendi, vou abrir um chamado.', $chamado->historicosInternos()->first()->texto);
    }

    public function test_bot_nao_abre_chamado_para_email_desconhecido_ou_inativo(): void
    {
        config(['services.bot_triagem.token' => 'segredo-do-bot']);
        $inativo = $this->clienteDe($this->empresaA);
        $inativo->update(['ativo' => false]);

        foreach (['ninguem@exemplo.com', $inativo->email] as $email) {
            $this->withToken('segredo-do-bot')->postJson('/api/bot/chamados', [
                'email' => $email,
                'tipo_chamado_id' => $this->bug->id,
                'descricao' => 'Cliente relata que o caixa trava ao emitir NFC-e.',
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $this->assertDatabaseCount('chamados', 0);
    }

    public function test_telas_novas_do_admin_abrem(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.tipos-chamado.index'))->assertOk()->assertSee('Defeito / Bug');
        $this->get(route('admin.tipos-chamado.create'))->assertOk();
        $this->get(route('admin.tipos-chamado.edit', $this->bug))->assertOk();
        $this->get(route('admin.prazos-sla.edit'))->assertOk()->assertSee('Crítica');
        $this->get(route('admin.empresas.index'))->assertOk()->assertSee('ver histórico');
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Ativo');
    }
}
