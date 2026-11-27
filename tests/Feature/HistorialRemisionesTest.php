<?php

namespace Tests\Feature;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use App\Services\RemisionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HistorialRemisionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_defaults_to_last_15_days(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $admin = User::factory()->admin()->create();
        Remision::factory()->create(['numero' => 11, 'emitida_en' => '2026-11-06 09:00:00']);
        Remision::factory()->create(['numero' => 12, 'emitida_en' => '2026-11-05 09:00:00']);

        $response = $this->actingAs($admin)->get(route('remisiones.index'));

        $response->assertOk();
        $response->assertSee('000011');
        $response->assertDontSee('000012');
    }

    public function test_seller_only_sees_own_remisiones_and_vendor_filter_is_ignored(): void
    {
        $vendedor = User::factory()->create();
        $otro = User::factory()->create();
        Remision::factory()->for($vendedor, 'vendedor')->create(['numero' => 21]);
        Remision::factory()->for($otro, 'vendedor')->create(['numero' => 22]);

        $response = $this->actingAs($vendedor)->get(route('remisiones.index', ['vendedor' => $otro->id]));

        $response->assertSee('000021');
        $response->assertDontSee('000022');
    }

    public function test_filters_by_material_payment_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        $arena = Material::factory()->create();
        $buscada = Remision::factory()->for(Comprador::factory()->create(['nombre' => 'Ferretería El Maestro']))->create(['numero' => 31, 'forma_pago' => FormaPago::Credito]);
        RemisionLinea::factory()->for($buscada)->for($arena)->create();
        $otraForma = Remision::factory()->create(['numero' => 32, 'forma_pago' => FormaPago::Efectivo]);
        RemisionLinea::factory()->for($otraForma)->for($arena)->create();
        Remision::factory()->create(['numero' => 33, 'forma_pago' => FormaPago::Credito]);

        $response = $this->actingAs($admin)->get(route('remisiones.index', ['material' => $arena->id, 'pago' => 'credito', 'q' => 'maestro']));

        $response->assertSee('000031');
        $response->assertDontSee('000032');
        $response->assertDontSee('000033');
    }

    public function test_totals_exclude_donations_and_cancelled(): void
    {
        $admin = User::factory()->admin()->create();
        Remision::factory()->create(['total' => 500_000]);
        Remision::factory()->create(['total' => 300_000, 'forma_pago' => FormaPago::Donacion]);
        Remision::factory()->create(['total' => 900_000, 'estado' => EstadoRemision::Anulada]);

        $response = $this->actingAs($admin)->get(route('remisiones.index'));

        $response->assertSee('$ 500.000');
        $response->assertDontSee('$ 1.700.000');
    }

    public function test_exports_filtered_list_to_excel(): void
    {
        $admin = User::factory()->admin()->create();
        Remision::factory()->create();

        $response = $this->actingAs($admin)->get(route('remisiones.excel', ['rango' => '7']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('attachment; filename=remisiones-', $response->headers->get('content-disposition'));
    }

    public function test_exports_filtered_list_to_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        Remision::factory()->create();

        $response = $this->actingAs($admin)->get(route('remisiones.listado-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_detail_has_back_link_keeping_list_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $remision = Remision::factory()->create(['numero' => 41]);
        $listado = route('remisiones.index', ['q' => 'maestro']);

        $response = $this->actingAs($admin)->from($listado)->get(route('remisiones.show', $remision));

        $response->assertSee('Volver a remisiones');
        $response->assertSee(e($listado), false);
        $response->assertDontSee('CÓDIGO:');
        $response->assertDontSee('VISTA PREVIA');
    }

    public function test_admin_cancels_remision_with_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->create(['stock_m3' => 50]);
        $remision = app(RemisionService::class)->emitir(User::factory()->create(), [
            'comprador_id' => Comprador::factory()->create()->id,
            'conductor' => 'X', 'placa' => 'ABC123', 'maquina' => '938', 'destino' => 'N/A',
            'forma_pago' => FormaPago::Efectivo,
            'lineas' => [['material_id' => $material->id, 'cantidad_m3' => 10]],
        ]);

        $response = $this->actingAs($admin)->post(route('remisiones.anular', $remision), ['motivo' => 'Cantidad mal digitada']);

        $response->assertRedirect(route('remisiones.show', $remision));
        $this->assertSame(EstadoRemision::Anulada, $remision->fresh()->estado);
        $this->assertSame('50.00', $material->fresh()->stock_m3);
    }

    public function test_cancel_requires_reason(): void
    {
        $remision = Remision::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('remisiones.anular', $remision), ['motivo' => '']);

        $response->assertSessionHasErrors('motivo', errorBag: 'anular');
        $this->assertSame(EstadoRemision::Emitida, $remision->fresh()->estado);
    }

    public function test_seller_cannot_cancel(): void
    {
        $vendedor = User::factory()->create();
        $remision = Remision::factory()->for($vendedor, 'vendedor')->create();

        $this->actingAs($vendedor)->post(route('remisiones.anular', $remision), ['motivo' => 'Quiero anularla'])->assertForbidden();

        $this->assertSame(EstadoRemision::Emitida, $remision->fresh()->estado);
    }
}
