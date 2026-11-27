<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EquipoControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function datosNuevo(array $cambios = []): array
    {
        return [
            'name' => 'Gilberto  Pineda',
            'cedula' => '86044519',
            'cargo' => 'Báscula',
            'role' => Rol::Vendedor->value,
            'email' => 'Gilberto@Cantera.test',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
            ...$cambios,
        ];
    }

    public function test_admin_sees_team_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Yuliana Moreno']);

        $response = $this->actingAs($admin)->get(route('equipo.index', ['q' => 'yuliana']));

        $response->assertOk();
        $response->assertSee('Yuliana Moreno');
        $response->assertDontSee($admin->email);
    }

    public function test_search_finds_cedula_typed_without_dots(): void
    {
        $admin = User::factory()->admin()->create(['cedula' => '1.121.845.330']);
        User::factory()->create(['name' => 'Gilberto Pineda', 'cedula' => '86.044.519']);

        $response = $this->actingAs($admin)->get(route('equipo.index', ['q' => '86044']));

        $response->assertSee('Gilberto Pineda');
        $response->assertDontSee($admin->email);
    }

    public function test_admin_creates_user_who_can_log_in(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('equipo.store'), $this->datosNuevo());

        $response->assertRedirect(route('equipo.index'));
        $usuario = User::query()->where('email', 'gilberto@cantera.test')->sole();
        $this->assertSame('Gilberto Pineda', $usuario->name);
        $this->assertSame('86.044.519', $usuario->cedula);
        $this->assertSame(Rol::Vendedor, $usuario->role);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(Hash::check('clave-segura-1', $usuario->password));
        $this->assertDatabaseHas('auditoria', ['accion' => 'usuario.creado', 'user_id' => $admin->id]);
    }

    public function test_rejects_duplicate_cedula_written_differently(): void
    {
        User::factory()->create(['cedula' => '86.044.519']);

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('equipo.store'), $this->datosNuevo());

        $response->assertSessionHasErrors('cedula', errorBag: 'usuario');
    }

    public function test_requires_matching_password_confirmation(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->post(route('equipo.store'), $this->datosNuevo(['password_confirmation' => 'otra-clave-22']));

        $response->assertSessionHasErrors('password', errorBag: 'usuario');
        $this->assertSame(1, User::query()->count());
    }

    public function test_seller_cannot_create_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('equipo.store'), $this->datosNuevo());

        $response->assertForbidden();
    }

    public function test_admin_deactivates_seller_and_edit_does_not_touch_password(): void
    {
        $admin = User::factory()->admin()->create();
        $vendedor = User::factory()->create();
        $claveAnterior = $vendedor->password;

        $this->actingAs($admin)->patch(route('equipo.update', $vendedor), [
            'name' => $vendedor->name,
            'cedula' => $vendedor->cedula,
            'cargo' => 'Retirado',
            'role' => Rol::Vendedor->value,
            'email' => $vendedor->email,
            'activo' => '0',
            'password' => 'no-se-usa-123',
            'password_confirmation' => 'no-se-usa-123',
        ])->assertRedirect(route('equipo.index'));

        $vendedor->refresh();
        $this->assertFalse($vendedor->activo);
        $this->assertSame('Retirado', $vendedor->cargo);
        $this->assertSame($claveAnterior, $vendedor->password);
        $this->assertDatabaseHas('auditoria', ['accion' => 'usuario.editado', 'auditable_id' => $vendedor->id]);
    }

    public function test_admin_cannot_remove_own_admin_role_or_deactivate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('equipo.update', $admin), [
            'name' => $admin->name,
            'cedula' => $admin->cedula,
            'role' => Rol::Vendedor->value,
            'email' => $admin->email,
            'activo' => '0',
        ]);

        $response->assertSessionHasErrors(['role', 'activo'], errorBag: 'usuario');
        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->activo);
    }

    public function test_admin_assigns_new_password(): void
    {
        $admin = User::factory()->admin()->create();
        $vendedor = User::factory()->create();

        $this->actingAs($admin)->put(route('equipo.contrasena', $vendedor), [
            'password' => 'nueva-clave-99',
            'password_confirmation' => 'nueva-clave-99',
        ])->assertRedirect(route('equipo.index'));

        $this->assertTrue(Hash::check('nueva-clave-99', $vendedor->fresh()->password));
        $this->assertDatabaseHas('auditoria', ['accion' => 'usuario.contrasena', 'auditable_id' => $vendedor->id]);

        $this->post('/logout');
        $this->post('/login', ['email' => $vendedor->email, 'password' => 'nueva-clave-99'])->assertRedirect(route('dashboard'));
    }

    public function test_seller_cannot_assign_passwords(): void
    {
        $otro = User::factory()->create();

        $response = $this->actingAs(User::factory()->create())->put(route('equipo.contrasena', $otro), [
            'password' => 'nueva-clave-99',
            'password_confirmation' => 'nueva-clave-99',
        ]);

        $response->assertForbidden();
        $this->assertFalse(Hash::check('nueva-clave-99', $otro->fresh()->password));
    }
}
