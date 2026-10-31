<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\TipoChamado;
use App\Models\User;
use App\Services\ChamadoService;
use App\Services\SlaService;
use Database\Seeders\EtapaSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TipoChamadoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaEtapaHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $empresaA;

    private Cliente $empresaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, StatusSeeder::class, TipoChamadoSeeder::class, EtapaSeeder::class]);
        $this->empresaA = Cliente::create(['nome_empresa' => 'Empresa A']);
        $this->empresaB = Cliente::create(['nome_empresa' => 'Empresa B']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function clienteDe(?Cliente $empresa): User
    {
        $user = User::factory()->create(['cliente_id' => $empresa?->id]);
        $user->assignRole('cliente');

        return $user;
    }

    private function chamadoDe(Cliente $empresa, string $descricao = 'Erro ao emitir nota fiscal no caixa 2.'): Chamado
    {
        return app(ChamadoService::class)->abrir([
            'cliente_id' => $empresa->id,
            'tipo_chamado_id' => TipoChamado::first()->id,
            'descricao' => $descricao,
            'criticidade_cliente' => 'Alta',
        ]);
    }

    // --- Empresa: o cliente só vê chamados da própria empresa ---

    public function test_cliente_lista_somente_chamados_da_propria_empresa(): void
    {
        $this->chamadoDe($this->empresaA, 'Chamado da empresa A com detalhes.');
        $this->chamadoDe($this->empresaB, 'Chamado da empresa B com detalhes.');

        $resposta = $this->actingAs($this->clienteDe($this->empresaA))->get('/chamados')->assertOk();

        $this->assertSame([$this->empresaA->id], $resposta->viewData('chamados')->pluck('cliente_id')->unique()->values()->all());
    }

    public function test_cliente_nao_abre_detalhe_de_chamado_de_outra_empresa(): void
    {
        $chamadoB = $this->chamadoDe($this->empresaB);

        $this->actingAs($this->clienteDe($this->empresaA))->get(route('chamados.show', $chamadoB))->assertForbidden();
    }

    public function test_admin_ve_chamados_de_todas_as_empresas(): void
    {
        $this->chamadoDe($this->empresaA);
        $this->chamadoDe($this->empresaB);

        $resposta = $this->actingAs($this->admin())->get('/chamados')->assertOk();

        $this->assertCount(2, $resposta->viewData('chamados'));
    }

    public function test_cliente_sem_empresa_nao_ve_nem_abre_chamados(): void
    {
        $this->chamadoDe($this->empresaA);
        $semEmpresa = $this->clienteDe(null);

        $resposta = $this->actingAs($semEmpresa)->get('/chamados')->assertOk();
        $this->assertCount(0, $resposta->viewData('chamados'));

        $this->actingAs($semEmpresa)->get('/chamados/create')->assertForbidden();
    }

    public function test_cliente_abre_chamado_sempre_para_a_propria_empresa(): void
    {
        $this->actingAs($this->clienteDe($this->empresaA))->post('/chamados', [
            'cliente_id' => $this->empresaB->id,
            'tipo_chamado_id' => TipoChamado::first()->id,
            'descricao' => 'Tentando abrir para outra empresa.',
        ])->assertRedirect(route('chamados.show', Chamado::first()));

        $chamado = Chamado::first();
        $this->assertSame($this->empresaA->id, $chamado->cliente_id);
        $this->assertSame(Etapa::ordenadas()->first()->id, $chamado->etapa_id);
    }

    public function test_admin_precisa_escolher_a_empresa_ao_abrir_chamado(): void
    {
        $this->actingAs($this->admin())->post('/chamados', [
            'tipo_chamado_id' => TipoChamado::first()->id,
            'descricao' => 'Chamado aberto pelo Márcio por telefone.',
        ])->assertSessionHasErrors('cliente_id');
    }

    public function test_admin_edita_usuario_e_troca_a_empresa(): void
    {
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($this->admin())->put(route('admin.usuarios.update', $cliente), [
            'name' => $cliente->name,
            'email' => $cliente->email,
            'cliente_id' => $this->empresaB->id,
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame($this->empresaB->id, $cliente->fresh()->cliente_id);
    }

    public function test_usuario_cliente_nao_pode_ficar_sem_empresa(): void
    {
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($this->admin())->put(route('admin.usuarios.update', $cliente), [
            'name' => $cliente->name,
            'email' => $cliente->email,
            'cliente_id' => '',
        ])->assertSessionHasErrors('cliente_id');
    }

    public function test_admin_cadastra_empresa_e_cliente_nao(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.empresas.store'), ['nome_empresa' => 'Padaria do Zé', 'perfil' => 'Varejo'])
            ->assertRedirect(route('admin.empresas.index'));

        $this->assertDatabaseHas('clientes', ['nome_empresa' => 'Padaria do Zé']);

        $this->actingAs($this->clienteDe($this->empresaA))
            ->post(route('admin.empresas.store'), ['nome_empresa' => 'Outra'])
            ->assertForbidden();
    }

    public function test_telas_de_cadastro_do_admin_abrem(): void
    {
        $admin = $this->admin();
        $etapa = Etapa::first();
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($admin);
        $this->get(route('admin.empresas.index'))->assertOk()->assertSee('Empresa A');
        $this->get(route('admin.empresas.create'))->assertOk();
        $this->get(route('admin.empresas.edit', $this->empresaA))->assertOk()->assertSee('Empresa A');
        $this->get(route('admin.etapas.index'))->assertOk()->assertSee('Recebido');
        $this->get(route('admin.etapas.create'))->assertOk();
        $this->get(route('admin.etapas.edit', $etapa))->assertOk()->assertSee($etapa->nome);
        $this->get(route('admin.usuarios.edit', $cliente))->assertOk()->assertSee($cliente->email);
        $this->get(route('chamados.create'))->assertOk()->assertSee('Selecione uma empresa');
    }

    public function test_cliente_ve_a_propria_empresa_no_formulario_de_abertura(): void
    {
        $this->actingAs($this->clienteDe($this->empresaA))
            ->get(route('chamados.create'))
            ->assertOk()
            ->assertSee('Empresa A')
            ->assertDontSee('Selecione uma empresa');
    }

    // --- Etapas visíveis ao cliente ---

    public function test_admin_cadastra_edita_e_exclui_etapa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.etapas.store'), ['nome' => 'Aguardando peça', 'ordem' => 10])
            ->assertRedirect(route('admin.etapas.index'));

        $etapa = Etapa::where('nome', 'Aguardando peça')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.etapas.update', $etapa), ['nome' => 'Aguardando fornecedor', 'ordem' => 3])
            ->assertRedirect(route('admin.etapas.index'));
        $this->assertSame('Aguardando fornecedor', $etapa->fresh()->nome);

        $this->actingAs($admin)->delete(route('admin.etapas.destroy', $etapa))->assertRedirect(route('admin.etapas.index'));
        $this->assertModelMissing($etapa);
    }

    public function test_etapa_em_uso_nao_pode_ser_excluida(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->admin())
            ->delete(route('admin.etapas.destroy', $chamado->etapa_id))
            ->assertSessionHas('error');

        $this->assertNotNull(Etapa::find($chamado->etapa_id));
    }

    public function test_cliente_nao_gerencia_etapas(): void
    {
        $this->actingAs($this->clienteDe($this->empresaA))->get(route('admin.etapas.index'))->assertForbidden();
    }

    public function test_admin_muda_a_etapa_e_fica_registrado_no_historico_interno(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $andamento = Etapa::where('nome', 'Em andamento')->firstOrFail();

        $this->actingAs($this->admin())
            ->patch(route('chamados.etapa.update', $chamado), ['etapa_id' => $andamento->id])
            ->assertRedirect(route('chamados.show', $chamado));

        $chamado->refresh();
        $this->assertSame($andamento->id, $chamado->etapa_id);
        $this->assertStringContainsString('Em andamento', $chamado->historicosInternos->first()->texto);
    }

    public function test_cliente_nao_muda_a_etapa(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $ultima = Etapa::ordenadas()->get()->last();

        $this->actingAs($this->clienteDe($this->empresaA))
            ->patch(route('chamados.etapa.update', $chamado), ['etapa_id' => $ultima->id])
            ->assertForbidden();

        $this->assertNotSame($ultima->id, $chamado->fresh()->etapa_id);
    }

    public function test_progresso_mostra_a_etapa_x_de_y(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $service = app(ChamadoService::class);
        $admin = $this->admin();

        $service->alterarEtapa($chamado, Etapa::where('nome', 'Em andamento')->firstOrFail(), $admin);
        $progresso = $service->progresso($chamado->fresh());
        $this->assertSame(3, $progresso['atual']);
        $this->assertSame(5, $progresso['total']);

        app(SlaService::class)->mudarStatus($chamado, 'CONCLUIDO');
        $this->assertSame(5, $service->progresso($chamado->fresh())['atual']);
    }

    public function test_cliente_ve_andamento_mas_nao_ve_informacoes_internas(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        app(ChamadoService::class)->registrarHistoricoInterno($chamado, $this->admin(), 'Anotação secreta da equipe');

        $this->actingAs($this->clienteDe($this->empresaA))
            ->get(route('chamados.show', $chamado))
            ->assertOk()
            ->assertSee('1/5')
            ->assertSee('Recebido')
            ->assertDontSee('Anotação secreta da equipe')
            ->assertDontSee('Histórico interno')
            ->assertDontSee('Triagem GUT');
    }

    // --- Histórico interno ---

    public function test_admin_registra_anotacao_no_historico_interno(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('chamados.historicos-internos.store', $chamado), ['texto' => 'Reproduzi o erro em homologação.'])
            ->assertRedirect(route('chamados.show', $chamado) . '#historico-interno');

        $registro = $chamado->historicosInternos()->first();
        $this->assertSame('Reproduzi o erro em homologação.', $registro->texto);
        $this->assertSame($admin->id, $registro->user_id);

        $this->actingAs($admin)->get(route('chamados.show', $chamado))->assertSee('Reproduzi o erro em homologação.');
    }

    public function test_cliente_nao_registra_no_historico_interno(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->clienteDe($this->empresaA))
            ->post(route('chamados.historicos-internos.store', $chamado), ['texto' => 'Tentativa'])
            ->assertForbidden();

        $this->assertCount(0, $chamado->historicosInternos);
    }

    // --- Cancelamento ---

    public function test_cliente_cancela_ordem_da_propria_empresa(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->clienteDe($this->empresaA))
            ->post(route('chamados.cancelar', $chamado), ['motivo' => 'Resolvemos internamente.'])
            ->assertRedirect(route('chamados.show', $chamado));

        $chamado->refresh();
        $this->assertSame('CANCELADO', $chamado->status->descricao);
        $this->assertNotNull($chamado->fechado_em);
        $this->assertStringContainsString('Resolvemos internamente.', $chamado->historicosInternos->first()->texto);
        $this->assertSame('CANCELADO', $chamado->historicosSla->last()->status->descricao);
    }

    public function test_admin_cancela_ordem(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);

        $this->actingAs($this->admin())->post(route('chamados.cancelar', $chamado))->assertRedirect();

        $this->assertSame('CANCELADO', $chamado->fresh()->status->descricao);
    }

    public function test_cliente_nao_cancela_ordem_de_outra_empresa(): void
    {
        $chamadoB = $this->chamadoDe($this->empresaB);

        $this->actingAs($this->clienteDe($this->empresaA))
            ->post(route('chamados.cancelar', $chamadoB))
            ->assertForbidden();

        $this->assertSame('ABERTO', $chamadoB->fresh()->status->descricao);
    }

    public function test_ordem_encerrada_nao_pode_ser_cancelada_de_novo(): void
    {
        $chamado = $this->chamadoDe($this->empresaA);
        $cliente = $this->clienteDe($this->empresaA);

        $this->actingAs($cliente)->post(route('chamados.cancelar', $chamado));
        $this->actingAs($cliente)->post(route('chamados.cancelar', $chamado))->assertForbidden();

        $this->assertCount(1, $chamado->fresh()->historicosInternos);
    }
}
