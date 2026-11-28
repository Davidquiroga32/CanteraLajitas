<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Auditoria;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_records_login_logout_and_failed_attempts(): void
    {
        $usuario = User::factory()->create(['email' => 'sebastian@canteralajitas.test']);

        $this->post(route('login'), ['email' => 'sebastian@canteralajitas.test', 'password' => 'incorrecta']);
        $this->post(route('login'), ['email' => 'sebastian@canteralajitas.test', 'password' => 'password']);
        $this->post(route('logout'));

        $this->assertSame(
            ['sesion.fallido', 'sesion.ingreso', 'sesion.salida'],
            Auditoria::query()->orderBy('id')->pluck('accion')->all(),
        );
        $this->assertSame([$usuario->id], Auditoria::query()->distinct()->pluck('user_id')->all());
        $this->assertSame('sebastian@canteralajitas.test', Auditoria::query()->where('accion', 'sesion.fallido')->value('datos')['correo']);
    }

    public function test_blocks_a_device_that_tries_many_different_emails(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < LoginRequest::INTENTOS_POR_EQUIPO; $i++) {
            $this->post(route('login'), ['email' => "prueba{$i}@canteralajitas.test", 'password' => 'x']);
        }

        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Espera 15 minuto(s) y vuelve a intentarlo.']);
        $this->assertGuest();
        $this->assertTrue(Auditoria::query()->where('accion', 'sesion.bloqueo')->exists());
    }

    public function test_a_lockout_is_recorded_only_once_while_it_lasts(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            $this->post(route('login'), ['email' => $usuario->email, 'password' => 'incorrecta']);
        }

        $this->assertSame(5, Auditoria::query()->where('accion', 'sesion.fallido')->count());
        $this->assertSame(1, Auditoria::query()->where('accion', 'sesion.bloqueo')->count());
    }

    public function test_login_rejects_bursts_of_requests_from_one_device(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('login'), ['email' => "rafaga{$i}@canteralajitas.test", 'password' => 'x']);
        }

        $this->post(route('login'), ['email' => 'otra@canteralajitas.test', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_records_downloads_with_filters_and_buyer(): void
    {
        $admin = User::factory()->admin()->create();
        $comprador = Comprador::factory()->create(['nombre' => 'Agregados del Ariari']);

        $this->actingAs($admin)->get(route('reportes.cliente-pdf', [$comprador, 'rango' => '30']))->assertOk();
        $this->actingAs($admin)->get(route('reportes.cliente', $comprador))->assertOk();

        $descarga = Auditoria::query()->sole();
        $this->assertSame('descarga', $descarga->accion);
        $this->assertTrue($descarga->auditable->is($comprador));
        $this->assertSame(['ruta' => 'reportes.cliente-pdf', 'filtros' => ['rango' => '30']], $descarga->datos);
    }

    public function test_records_own_password_change(): void
    {
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Auditoria::query()->where('accion', 'usuario.contrasena_propia')->where('user_id', $vendedor->id)->exists());
    }

    public function test_assigning_a_password_or_deactivating_closes_the_user_sessions(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->admin()->create();
        $vendedor = User::factory()->create(['remember_token' => 'token-viejo']);
        $otro = User::factory()->create();
        $this->sesionAbierta($vendedor, 'celular-del-vendedor');
        $this->sesionAbierta($otro, 'celular-de-otro');

        $this->actingAs($admin)->put(route('equipo.contrasena', $vendedor), [
            'password' => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ])->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'celular-del-vendedor']);
        $this->assertDatabaseHas('sessions', ['id' => 'celular-de-otro']);
        $this->assertNotSame('token-viejo', $vendedor->fresh()->remember_token);

        $this->sesionAbierta($otro, 'otra-sesion-de-otro');
        $this->actingAs($admin)->patch(route('equipo.update', $otro), [
            'name' => $otro->name, 'cedula' => $otro->cedula, 'cargo' => $otro->cargo,
            'role' => 'vendedor', 'email' => $otro->email, 'activo' => '0',
        ])->assertRedirect();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $otro->id)->count());
    }

    public function test_admin_sees_readable_history_filtered_by_type(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Rosa Elena']);
        $material = Material::factory()->create(['nombre' => 'Arena de río', 'precio_m3' => 85_000]);

        $this->actingAs($admin)->patch(route('materiales.update', $material), [
            ...$material->only(['nombre', 'stock_minimo_m3', 'color']),
            'precio_m3' => 90_000,
            'activo' => '1',
        ]);
        Auditoria::registrar('sesion.ingreso', $admin, ['automatico' => false], $admin);

        $this->actingAs($admin)->get(route('auditoria.index', ['tipo' => 'material']))
            ->assertOk()
            ->assertSee('Editó el material Arena de río: precio $ 85.000 → $ 90.000')
            ->assertSee('Rosa Elena')
            ->assertDontSee('Entró al sistema');
    }

    public function test_audit_export_is_itself_audited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('auditoria.excel'))->assertOk()->assertDownload();

        $this->assertSame('auditoria.excel', Auditoria::query()->where('accion', 'descarga')->sole()->datos['ruta']);
    }

    private function sesionAbierta(User $usuario, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $usuario->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Celular',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
