<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoPorRolTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function rutasSoloAdmin(): array
    {
        return [
            'cartera' => ['cartera.index'],
            'equipo' => ['equipo.index'],
            'reportes' => ['reportes.index'],
        ];
    }

    #[DataProvider('rutasSoloAdmin')]
    public function test_forbids_admin_sections_to_sellers(string $ruta): void
    {
        $vendedor = User::factory()->create();

        $response = $this->actingAs($vendedor)->get(route($ruta));

        $response->assertForbidden();
    }

    #[DataProvider('rutasSoloAdmin')]
    public function test_admin_can_open_admin_sections(string $ruta): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route($ruta));

        $response->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('remisiones.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_seller_menu_hides_admin_sections(): void
    {
        $vendedor = User::factory()->create();

        $response = $this->actingAs($vendedor)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('remisiones.create'));
        $response->assertDontSee(route('cartera.index'));
        $response->assertDontSee(route('equipo.index'));
        $response->assertDontSee(route('reportes.index'));
    }

    public function test_admin_menu_shows_admin_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('cartera.index'));
        $response->assertSee(route('equipo.index'));
        $response->assertSee(route('reportes.index'));
    }
}
