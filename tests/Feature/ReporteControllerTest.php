<?php

namespace Tests\Feature;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReporteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sales_exclude_donations_and_cancelled_and_compare_with_same_days_of_previous_month(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $material = Material::factory()->create();
        $venta = Remision::factory()->create(['total' => 1_200_000, 'emitida_en' => '2026-11-05 09:00:00']);
        $donacion = Remision::factory()->create(['total' => 300_000, 'forma_pago' => FormaPago::Donacion, 'emitida_en' => '2026-11-06 09:00:00']);
        Remision::factory()->create(['total' => 900_000, 'estado' => EstadoRemision::Anulada, 'emitida_en' => '2026-11-07 09:00:00']);
        Remision::factory()->create(['total' => 1_000_000, 'emitida_en' => '2026-10-10 09:00:00']);
        Remision::factory()->create(['total' => 5_000_000, 'emitida_en' => '2026-10-25 09:00:00']);
        RemisionLinea::factory()->for($venta)->for($material)->create(['cantidad_m3' => 12]);
        RemisionLinea::factory()->for($donacion)->for($material)->create(['cantidad_m3' => 4]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('reportes.index', ['rango' => 'mes']));

        $response->assertOk();
        $reporte = $response->viewData('reporte');
        $this->assertSame(['valor' => 1_200_000, 'remisiones' => 1, 'm3' => 12.0, 'ticket' => 1_200_000], $reporte['ventas']);
        $this->assertSame(['valor' => 300_000, 'remisiones' => 1, 'm3' => 4.0], $reporte['donaciones']);
        $this->assertSame(1, $reporte['anuladas']);
        $this->assertSame(1_000_000, $reporte['anterior']['valor']);
        $this->assertSame(20.0, $reporte['anterior']['variacion']);
    }

    public function test_free_range_compares_with_the_same_number_of_days_before(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 500_000, 'emitida_en' => '2026-11-12 09:00:00']);
        Remision::factory()->create(['total' => 400_000, 'emitida_en' => '2026-11-09 09:00:00']);
        Remision::factory()->create(['total' => 700_000, 'emitida_en' => '2026-11-08 09:00:00']);

        $reporte = $this->actingAs(User::factory()->admin()->create())
            ->get(route('reportes.index', ['desde' => '2026-11-10', 'hasta' => '2026-11-14']))
            ->viewData('reporte');

        $this->assertSame(500_000, $reporte['ventas']['valor']);
        $this->assertSame('2026-11-05', $reporte['anterior']['rango']->desde->toDateString());
        $this->assertSame('2026-11-09', $reporte['anterior']['rango']->hasta->toDateString());
        $this->assertSame(1_100_000, $reporte['anterior']['valor']);
    }

    public function test_short_range_charts_by_day_and_long_range_by_week(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        Remision::factory()->create(['total' => 300_000, 'emitida_en' => '2026-11-03 09:00:00']);
        Remision::factory()->create(['total' => 200_000, 'emitida_en' => '2026-11-08 18:00:00']);
        $admin = User::factory()->admin()->create();

        $porDia = $this->actingAs($admin)->get(route('reportes.index', ['rango' => 'mes']))->viewData('reporte');
        $porSemana = $this->actingAs($admin)->get(route('reportes.index', ['desde' => '2026-09-01', 'hasta' => '2026-11-20']))->viewData('reporte');

        $this->assertSame('dia', $porDia['agrupacion']);
        $this->assertCount(20, $porDia['serie']);
        $this->assertSame('semana', $porSemana['agrupacion']);
        $semana = collect($porSemana['serie'])->first(fn (array $p): bool => $p['desde']->toDateString() === '2026-11-02');
        $this->assertSame('2026-11-08', $semana['hasta']->toDateString());
        $this->assertSame(500_000, $semana['valor']);
        $this->assertSame(500_000, collect($porSemana['serie'])->sum('valor'));
    }

    public function test_breaks_down_sales_by_material_seller_and_payment_method(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $arena = Material::factory()->create(['nombre' => 'Arena de río']);
        $recebo = Material::factory()->create(['nombre' => 'Recebo gris']);
        $ana = User::factory()->create(['name' => 'Ana Vendedora']);
        $luis = User::factory()->create(['name' => 'Luis Vendedor']);
        $contado = Remision::factory()->for($ana, 'vendedor')->create(['total' => 600_000, 'forma_pago' => FormaPago::Efectivo]);
        $credito = Remision::factory()->for($luis, 'vendedor')->create(['total' => 400_000, 'forma_pago' => FormaPago::Credito]);
        $donacion = Remision::factory()->for($luis, 'vendedor')->create(['total' => 999_000, 'forma_pago' => FormaPago::Donacion]);
        RemisionLinea::factory()->for($contado)->for($arena)->create(['cantidad_m3' => 6, 'subtotal' => 600_000]);
        RemisionLinea::factory()->for($credito)->for($recebo)->create(['cantidad_m3' => 8, 'subtotal' => 400_000]);
        RemisionLinea::factory()->for($donacion)->for($recebo)->create(['cantidad_m3' => 20, 'subtotal' => 999_000]);

        $reporte = $this->actingAs(User::factory()->admin()->create())->get(route('reportes.index', ['rango' => 'hoy']))->viewData('reporte');

        $this->assertSame(
            [['Arena de río', 6.0, 600_000, 60.0], ['Recebo gris', 8.0, 400_000, 40.0]],
            $reporte['materiales']->map(fn (array $f): array => [$f['material']->nombre, $f['m3'], $f['valor'], $f['porcentaje']])->all(),
        );
        $this->assertSame(
            [['Ana Vendedora', 1, 6.0, 600_000], ['Luis Vendedor', 1, 8.0, 400_000]],
            $reporte['vendedores']->map(fn (array $f): array => [$f['vendedor']->name, $f['remisiones'], $f['m3'], $f['valor']])->all(),
        );
        $formas = $reporte['formas_pago']->keyBy(fn (array $f): string => $f['forma']->value);
        $this->assertFalse($formas->has(FormaPago::Donacion->value));
        $this->assertSame(600_000, $formas['efectivo']['valor']);
        $this->assertSame(400_000, $formas['credito']['valor']);
        $this->assertSame(0, $formas['transferencia']['remisiones']);
    }

    public function test_client_report_only_counts_that_client_within_the_range(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $comprador = Comprador::factory()->create();
        Remision::factory()->for($comprador)->create(['total' => 800_000, 'emitida_en' => '2026-11-10 09:00:00']);
        Remision::factory()->for($comprador)->create(['total' => 450_000, 'emitida_en' => '2026-10-28 09:00:00']);
        Remision::factory()->create(['total' => 900_000, 'emitida_en' => '2026-11-10 09:00:00']);
        MovimientoCartera::factory()->for($comprador)->deuda(800_000)->create(['registrado_en' => '2026-11-10 09:00:00']);
        MovimientoCartera::factory()->for($comprador)->deuda(-300_000)->create(['tipo' => TipoMovimiento::Abono, 'registrado_en' => '2026-11-15 09:00:00']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('reportes.cliente', [$comprador, 'rango' => 'mes']));

        $response->assertOk()->assertSee($comprador->nombre);
        $reporte = $response->viewData('reporte');
        $this->assertSame(800_000, $reporte['ventas']['valor']);
        $this->assertCount(1, $reporte['remisiones']);
        $this->assertSame(300_000, $reporte['abonos']);
        $this->assertSame(500_000, $reporte['deuda']);
    }

    public function test_client_search_lists_matching_buyers(): void
    {
        Comprador::factory()->create(['nombre' => 'Agregados del Ariari']);
        Comprador::factory()->create(['nombre' => 'Constructora Llano']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reportes.cliente', ['q' => 'Ariari']))
            ->assertOk()
            ->assertSee('Agregados del Ariari')
            ->assertDontSee('Constructora Llano');
    }

    public function test_debtors_report_counts_days_since_last_payment_and_can_sort_by_them(): void
    {
        $this->travelTo('2026-11-20 15:00:00');
        $grande = Comprador::factory()->create(['nombre' => 'Debe Mucho']);
        $atrasado = Comprador::factory()->create(['nombre' => 'Nunca Abona']);
        $alDia = Comprador::factory()->create(['nombre' => 'Ya Pagó']);
        $this->compraACredito($grande, 3_000_000, '2026-11-01 09:00:00');
        MovimientoCartera::factory()->for($grande)->deuda(-500_000)->create(['tipo' => TipoMovimiento::Abono, 'registrado_en' => '2026-11-15 09:00:00']);
        $this->compraACredito($atrasado, 700_000, '2026-09-21 09:00:00');
        $this->compraACredito($alDia, 400_000, '2026-11-02 09:00:00');
        MovimientoCartera::factory()->for($alDia)->deuda(-400_000)->create(['tipo' => TipoMovimiento::Abono]);
        $admin = User::factory()->admin()->create();

        $porDeuda = $this->actingAs($admin)->get(route('reportes.deudores'))->assertOk()->viewData('deudores');
        $porDias = $this->actingAs($admin)->get(route('reportes.deudores', ['orden' => 'dias']))->viewData('deudores');

        $this->assertSame(['Debe Mucho', 'Nunca Abona'], $porDeuda->map(fn (array $d): string => $d['comprador']->nombre)->all());
        $this->assertSame([2_500_000, 5], [$porDeuda[0]['deuda'], $porDeuda[0]['dias_sin_abonar']]);
        $this->assertSame('2026-11-01', $porDeuda[0]['ultima_compra']->toDateString());
        $this->assertNull($porDeuda[1]['ultimo_abono']);
        $this->assertSame(60, $porDeuda[1]['dias_sin_abonar']);
        $this->assertSame(['Nunca Abona', 'Debe Mucho'], $porDias->map(fn (array $d): string => $d['comprador']->nombre)->all());
    }

    public function test_admin_downloads_each_report_in_excel_and_pdf(): void
    {
        $remision = Remision::factory()->create();
        RemisionLinea::factory()->for($remision)->create();
        $this->compraACredito($remision->comprador, 500_000, now()->toDateTimeString());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('reportes.excel', ['rango' => '30']))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('reportes.pdf', ['rango' => '30']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('reportes.cliente-excel', $remision->comprador))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('reportes.cliente-pdf', $remision->comprador))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('reportes.deudores-excel'))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('reportes.deudores-pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_seller_cannot_open_or_download_reports(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create();

        foreach ([
            route('reportes.excel'),
            route('reportes.pdf'),
            route('reportes.cliente', $comprador),
            route('reportes.cliente-excel', $comprador),
            route('reportes.cliente-pdf', $comprador),
            route('reportes.deudores'),
            route('reportes.deudores-excel'),
            route('reportes.deudores-pdf'),
        ] as $url) {
            $this->actingAs($vendedor)->get($url)->assertForbidden();
        }
    }

    private function compraACredito(Comprador $comprador, int $valor, string $fecha): void
    {
        $remision = Remision::factory()->for($comprador)->create(['total' => $valor, 'forma_pago' => FormaPago::Credito, 'emitida_en' => $fecha]);
        MovimientoCartera::factory()->for($comprador)->for($remision)->deuda($valor)->create(['registrado_en' => $fecha]);
    }
}
