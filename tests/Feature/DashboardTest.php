<?php

namespace Tests\Feature;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_for_a_new_installation_without_sales(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Aún no hay remisiones.');
    }

    public function test_seller_only_sees_own_sales(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $vendedor = User::factory()->create();
        $otro = User::factory()->create();
        Remision::factory()->for($vendedor, 'vendedor')->create(['total' => 400_000, 'emitida_en' => '2026-11-20 09:00:00']);
        Remision::factory()->for($otro, 'vendedor')->create(['total' => 900_000, 'emitida_en' => '2026-11-20 10:00:00']);

        $resumen = app(DashboardService::class)->resumen($vendedor);

        $this->assertSame(400_000, $resumen['hoy']['valor']);
        $this->assertSame(1, $resumen['hoy']['remisiones']);
        $this->assertNull($resumen['admin']);
    }

    public function test_admin_sees_all_sales(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 400_000, 'emitida_en' => '2026-11-20 09:00:00']);
        Remision::factory()->create(['total' => 900_000, 'emitida_en' => '2026-11-20 10:00:00']);

        $resumen = app(DashboardService::class)->resumen(User::factory()->admin()->create());

        $this->assertSame(1_300_000, $resumen['hoy']['valor']);
    }

    public function test_donations_count_as_dispatched_material_but_not_as_income(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $material = Material::factory()->create();
        $venta = Remision::factory()->create(['total' => 500_000, 'emitida_en' => '2026-11-20 09:00:00']);
        $donacion = Remision::factory()->create(['total' => 300_000, 'forma_pago' => FormaPago::Donacion, 'emitida_en' => '2026-11-20 10:00:00']);
        RemisionLinea::factory()->for($venta)->for($material)->create(['cantidad_m3' => 10]);
        RemisionLinea::factory()->for($donacion)->for($material)->create(['cantidad_m3' => 6]);

        $resumen = app(DashboardService::class)->resumen(User::factory()->admin()->create());

        $this->assertSame(500_000, $resumen['mes']['valor']);
        $this->assertSame(16.0, $resumen['mes']['m3']);
    }

    public function test_cancelled_remisiones_are_ignored(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 500_000, 'emitida_en' => '2026-11-20 09:00:00', 'estado' => EstadoRemision::Anulada]);

        $resumen = app(DashboardService::class)->resumen(User::factory()->admin()->create());

        $this->assertSame(0, $resumen['hoy']['valor']);
        $this->assertSame(0, $resumen['hoy']['remisiones']);
    }

    public function test_compares_month_with_previous_month_up_to_same_day(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 1_200_000, 'emitida_en' => '2026-11-05 09:00:00']);
        Remision::factory()->create(['total' => 1_000_000, 'emitida_en' => '2026-10-10 09:00:00']);
        Remision::factory()->create(['total' => 5_000_000, 'emitida_en' => '2026-10-25 09:00:00']);

        $resumen = app(DashboardService::class)->resumen(User::factory()->admin()->create());

        $this->assertSame(1_200_000, $resumen['mes']['valor']);
        $this->assertSame(20.0, $resumen['mes']['variacion']);
    }

    public function test_daily_series_covers_30_days_with_empty_days_in_zero(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 700_000, 'emitida_en' => '2026-11-18 09:00:00']);

        $serie = app(DashboardService::class)->resumen(User::factory()->admin()->create())['serie'];

        $this->assertCount(30, $serie);
        $this->assertSame('2026-10-22', $serie[0]['dia']->toDateString());
        $this->assertSame(700_000, $serie[27]['valor']);
        $this->assertSame(0, $serie[28]['valor']);
    }

    public function test_admin_summary_includes_debt_advances_and_low_stock(): void
    {
        $conDeuda = Comprador::factory()->create();
        MovimientoCartera::factory()->for($conDeuda)->deuda(800_000)->create();
        MovimientoCartera::factory()->for(Comprador::factory())->create(['valor' => 2_000_000]);
        $bajo = Material::factory()->create(['stock_m3' => 20, 'stock_minimo_m3' => 100]);
        Material::factory()->create(['stock_m3' => 500, 'stock_minimo_m3' => 100]);

        $admin = app(DashboardService::class)->resumen(User::factory()->admin()->create())['admin'];

        $this->assertSame(800_000, $admin['deuda']);
        $this->assertSame(1, $admin['deudores']);
        $this->assertSame(2_000_000, $admin['anticipos']);
        $this->assertSame([$bajo->id], $admin['stock_bajo']->modelKeys());
    }

    public function test_cartera_block_is_shown_only_to_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Cartera por cobrar');

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cartera por cobrar');
    }
}
