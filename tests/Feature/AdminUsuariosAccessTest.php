<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsuariosAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/admin/usuarios')->assertRedirect('/login');
    }

    public function test_usuario_sem_papel_recebe_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_cliente_recebe_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cliente');

        $this->actingAs($user)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_admin_acessa_a_listagem(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin/usuarios')->assertOk();
    }

    public function test_admin_cria_usuario_com_papel_cliente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $cliente = Cliente::create(['nome_empresa' => 'Empresa X']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Novo Cliente',
            'email' => 'novo@empresa.com',
            'password' => 'password123',
            'cliente_id' => $cliente->id,
        ])->assertRedirect(route('admin.usuarios.index'));

        $novo = User::where('email', 'novo@empresa.com')->first();

        $this->assertNotNull($novo);
        $this->assertTrue($novo->hasRole('cliente'));
        $this->assertEquals($cliente->id, $novo->cliente_id);
    }

    public function test_login_e_bloqueado_apos_cinco_tentativas_erradas(): void
    {
        $user = User::factory()->create(['email' => 'bloqueio@empresa.com', 'password' => 'senha-certa-123']);
        User::factory()->create(['email' => 'outro@empresa.com', 'password' => 'senha-certa-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada'])
                ->assertSessionHasErrors('email');
        }

        // Na 6ª tentativa, mesmo com a senha certa, o login segue bloqueado.
        $this->post('/login', ['email' => $user->email, 'password' => 'senha-certa-123'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
        $this->assertGuest();

        // Outro IP com o mesmo e-mail não é afetado.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post('/login', ['email' => $user->email, 'password' => 'senha-certa-123'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        // Outro e-mail no mesmo IP também não é afetado.
        $this->post('/login', ['email' => 'outro@empresa.com', 'password' => 'senha-certa-123'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_login_com_sucesso_zera_o_contador_de_tentativas(): void
    {
        $user = User::factory()->create(['email' => 'zera@empresa.com', 'password' => 'senha-certa-123']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-certa-123'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        auth()->logout();

        // Contador zerado: mais 4 erros ainda não bloqueiam.
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'senha-certa-123'])
            ->assertSessionHasNoErrors();
    }

    public function test_cadastro_requer_campos_obrigatorios(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post('/admin/usuarios', [])->assertSessionHasErrors(['name', 'email', 'password', 'cliente_id']);
    }
}
