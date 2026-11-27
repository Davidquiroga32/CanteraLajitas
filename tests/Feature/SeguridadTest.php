<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Material;
use App\Models\Remision;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Route as Ruta;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Rutas que un vendedor (o un visitante sin sesión) sí puede usar. Una ruta
     * nueva que no sea solo de admin debe agregarse aquí a propósito, después de
     * revisar que el vendedor solo ve lo suyo.
     *
     * @var list<string>
     */
    private const RUTAS_REVISADAS_PARA_VENDEDOR = [
        'GET /',
        'GET compradores',
        'POST compradores',
        'GET compradores/buscar',
        'GET confirm-password',
        'POST confirm-password',
        'GET forgot-password',
        'GET login',
        'POST login',
        'POST logout',
        'GET materiales',
        'PUT password',
        'GET profile',
        'PATCH profile',
        'GET remisiones',
        'POST remisiones',
        'GET remisiones/exportar/excel',
        'GET remisiones/exportar/pdf',
        'GET remisiones/nueva',
        'GET remisiones/{remision}',
        'GET remisiones/{remision}/imprimir',
        'GET remisiones/{remision}/pdf',
    ];

    public function test_every_route_is_admin_only_or_reviewed_for_sellers(): void
    {
        $abiertas = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (Ruta $ruta): bool => $this->esDelFramework($ruta) || in_array('can:admin', $ruta->gatherMiddleware(), true))
            ->map(fn (Ruta $ruta): string => $this->clave($ruta))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(collect(self::RUTAS_REVISADAS_PARA_VENDEDOR)->sort()->values()->all(), $abiertas);
    }

    public function test_seller_is_forbidden_on_every_admin_route(): void
    {
        $vendedor = User::factory()->create();
        $parametros = [
            'comprador' => Comprador::factory()->create()->id,
            'material' => Material::factory()->create()->id,
            'remision' => Remision::factory()->create()->id,
            'usuario' => User::factory()->create()->id,
        ];

        $rutasAdmin = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (Ruta $ruta): bool => in_array('can:admin', $ruta->gatherMiddleware(), true));

        $this->assertGreaterThanOrEqual(25, $rutasAdmin->count());

        foreach ($rutasAdmin as $ruta) {
            $url = '/'.ltrim(preg_replace_callback('/\{(\w+)\??\}/', fn (array $m): string => (string) ($parametros[$m[1]] ?? ''), $ruta->uri()), '/');

            $this->actingAs($vendedor)
                ->call($ruta->methods()[0], $url)
                ->assertForbidden();
        }
    }

    public function test_password_reset_by_email_and_email_verification_are_disabled(): void
    {
        $this->post('/forgot-password', ['email' => 'alguien@canteralajitas.test'])->assertMethodNotAllowed();
        $this->get('/reset-password/token-cualquiera')->assertNotFound();
        $this->post('/reset-password')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/verify-email')->assertNotFound();
    }

    public function test_responses_carry_security_headers_even_on_errors(): void
    {
        foreach ([$this->get(route('login')), $this->get('/no-existe')] as $respuesta) {
            $respuesta->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("frame-ancestors 'none'", $respuesta->headers->get('Content-Security-Policy'));
        }
    }

    public function test_error_pages_are_in_spanish(): void
    {
        $this->get('/no-existe')->assertNotFound()->assertSee('Esta página no existe');
        $this->actingAs(User::factory()->create())->get(route('cartera.index'))->assertForbidden()->assertSee('No tienes permiso para ver esto');
        $this->assertStringContainsString('La página se venció', view('errors.419')->render());
    }

    private function esDelFramework(Ruta $ruta): bool
    {
        return ! str_starts_with((string) $ruta->getActionName(), 'App\\')
            && ! in_array('web', $ruta->gatherMiddleware(), true);
    }

    private function clave(Ruta $ruta): string
    {
        return collect($ruta->methods())->reject(fn (string $m): bool => $m === 'HEAD')->first().' '.$ruta->uri();
    }
}
