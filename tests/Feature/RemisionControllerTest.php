<?php

namespace Tests\Feature;

use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\User;
use App\Services\RemisionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemisionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(Comprador $comprador, Material $material, array $cambios = []): array
    {
        return [
            'comprador_id' => $comprador->id,
            'lineas' => [['material_id' => $material->id, 'cantidad_m3' => '6']],
            'conductor' => 'Carlos Piñeros',
            'placa' => 'fbi 713',
            'maquina' => '938 G',
            'destino' => 'N/A',
            'forma_pago' => 'efectivo',
            ...$cambios,
        ];
    }

    public function test_form_offers_only_materials_with_stock(): void
    {
        Material::factory()->create(['nombre' => 'Arena de río', 'stock_m3' => 50]);
        Material::factory()->create(['nombre' => 'Recebo agotado', 'stock_m3' => 0]);
        Material::factory()->create(['nombre' => 'Producto inactivo', 'stock_m3' => 50, 'activo' => false]);

        $response = $this->actingAs(User::factory()->create())->get(route('remisiones.create'));

        $response->assertOk();
        $response->assertSee('Arena de río');
        $response->assertDontSee('Recebo agotado');
        $response->assertDontSee('Producto inactivo');
    }

    public function test_form_preloads_buyer_from_query_string(): void
    {
        $comprador = Comprador::factory()->create(['nombre' => 'Ferretería El Maestro']);

        $response = $this->actingAs(User::factory()->create())->get(route('remisiones.create', ['comprador' => $comprador->id]));

        $response->assertOk();
        $response->assertSee('Ferretería El Maestro');
    }

    public function test_emits_remision_as_logged_in_seller_with_catalog_price(): void
    {
        $vendedor = User::factory()->create();
        $otro = User::factory()->create();
        $comprador = Comprador::factory()->create();
        $material = Material::factory()->create(['precio_m3' => 85_000, 'stock_m3' => 50]);

        $response = $this->actingAs($vendedor)->post(route('remisiones.store'), $this->datos($comprador, $material, [
            'vendedor_id' => $otro->id,
            'lineas' => [['material_id' => $material->id, 'cantidad_m3' => '6', 'precio_m3' => 1]],
        ]));

        $remision = Remision::query()->sole();
        $response->assertRedirect(route('remisiones.show', $remision));
        $response->assertSessionHas('emitida', true);
        $this->assertTrue($remision->vendedor->is($vendedor));
        $this->assertSame(510_000, $remision->total);
        $this->assertSame('FBI 713', $remision->placa);
        $this->assertSame('44.00', $material->fresh()->stock_m3);
    }

    public function test_creates_new_buyer_from_the_form(): void
    {
        $material = Material::factory()->create(['stock_m3' => 50]);
        $datos = $this->datos(Comprador::factory()->make(), $material);
        unset($datos['comprador_id']);
        $datos['comprador'] = ['nombre' => 'Obras del Llano', 'tipo_documento' => 'NIT', 'documento' => '901.555.000-1', 'ciudad' => 'Acacías', 'impuestos' => 'si', 'retefuente' => '1'];

        $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $datos)->assertRedirect();

        $remision = Remision::query()->sole();
        $this->assertSame('Obras del Llano', $remision->comprador->nombre);
        $this->assertTrue($remision->comprador->retefuente);
        $this->assertFalse($remision->comprador->reteica);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function camposObligatorios(): array
    {
        return [
            'conductor' => ['conductor'],
            'placa' => ['placa'],
            'máquina' => ['maquina'],
            'destino' => ['destino'],
            'forma de pago' => ['forma_pago'],
        ];
    }

    #[DataProvider('camposObligatorios')]
    public function test_rejects_remision_with_missing_field(string $campo): void
    {
        $material = Material::factory()->create(['stock_m3' => 50]);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('remisiones.create'))
            ->post(route('remisiones.store'), $this->datos(Comprador::factory()->create(), $material, [$campo => '']));

        $response->assertRedirect(route('remisiones.create'));
        $response->assertSessionHasErrors($campo);
        $this->assertSame(0, Remision::query()->count());
    }

    public function test_new_buyer_requires_name_document_and_city(): void
    {
        $datos = $this->datos(Comprador::factory()->make(), Material::factory()->create(['stock_m3' => 50]));
        unset($datos['comprador_id']);
        $datos['comprador'] = ['nombre' => '', 'tipo_documento' => 'NIT', 'documento' => '', 'ciudad' => ''];

        $response = $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $datos);

        $response->assertSessionHasErrors(['comprador.nombre', 'comprador.documento', 'comprador.ciudad']);
        $this->assertSame(0, Comprador::query()->count());
    }

    public function test_requires_at_least_one_material_line(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $this->datos(
            Comprador::factory()->create(),
            Material::factory()->create(),
            ['lineas' => [['material_id' => '', 'cantidad_m3' => '']]],
        ));

        $response->assertSessionHasErrors('lineas');
    }

    public function test_rejects_quantity_above_stock_with_clear_message(): void
    {
        $material = Material::factory()->create(['nombre' => 'Gravilla de río', 'stock_m3' => 5]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('remisiones.store'), $this->datos(Comprador::factory()->create(), $material, ['lineas' => [['material_id' => $material->id, 'cantidad_m3' => '8']]]));

        $response->assertSessionHasErrors('lineas');
        $this->assertStringContainsString('Gravilla de río', session('errors')->first('lineas'));
        $this->assertSame(0, Remision::query()->count());
    }

    public function test_print_shows_taxes_discounted_from_total(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create(['retefuente' => true, 'reteica' => true]);
        $material = Material::factory()->create(['precio_m3' => 100_000, 'stock_m3' => 50]);
        $remision = app(RemisionService::class)->emitir($vendedor, $this->datos($comprador, $material, ['lineas' => [['material_id' => $material->id, 'cantidad_m3' => 10]]]));

        $response = $this->actingAs($vendedor)->get(route('remisiones.imprimir', $remision));

        $response->assertSeeInOrder(['SUBTOTAL', '$ 1.000.000', 'RETENCIÓN EN LA FUENTE 2,5%', '$ 25.000', 'ICA 10 × 1.000 (1%)', '$ 10.000', 'TOTAL A PAGAR', '$ 965.000']);
    }

    public function test_advance_payment_difference_paid_in_cash_does_not_become_debt(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 200_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000, 'stock_m3' => 50]);

        $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $this->datos($comprador, $material, ['forma_pago' => FormaPago::Anticipo->value, 'pago_diferencia' => 'efectivo']));

        $remision = Remision::query()->sole();
        $this->assertSame(0, $comprador->saldoAnticipo());
        $this->assertSame(0, $comprador->saldoCredito());
        $this->assertSame(FormaPago::Efectivo, $remision->diferencia_forma_pago);
        $this->assertSame(400_000, $remision->diferencia_valor);
    }

    public function test_advance_payment_difference_on_credit_becomes_debt(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 200_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000, 'stock_m3' => 50]);

        $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $this->datos($comprador, $material, ['forma_pago' => FormaPago::Anticipo->value, 'pago_diferencia' => 'credito']));

        $this->assertSame(0, $comprador->saldoAnticipo());
        $this->assertSame(400_000, $comprador->saldoCredito());
    }

    public function test_advance_payment_short_requires_choosing_how_to_pay_difference(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 200_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000, 'stock_m3' => 50]);

        $response = $this->actingAs(User::factory()->create())->post(route('remisiones.store'), $this->datos($comprador, $material, ['forma_pago' => FormaPago::Anticipo->value]));

        $response->assertSessionHasErrors('pago_diferencia');
        $this->assertSame(0, Remision::query()->count());
        $this->assertSame(200_000, $comprador->saldoAnticipo());
    }

    public function test_seller_cannot_see_another_sellers_remision(): void
    {
        $remision = Remision::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('remisiones.show', $remision))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('remisiones.imprimir', $remision))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('remisiones.pdf', $remision))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('remisiones.show', $remision))->assertOk();
    }

    public function test_print_page_has_three_copies_with_legal_header_and_signatures(): void
    {
        $vendedor = User::factory()->create(['name' => 'Sebastián Cárdenas', 'cedula' => '1.121.845.330']);
        $comprador = Comprador::factory()->create(['nombre' => 'Consorcio Vial Orinoquía', 'documento' => '901.220.664-9']);
        $remision = app(RemisionService::class)->emitir($vendedor, $this->datos($comprador, Material::factory()->create(['stock_m3' => 50])));

        $response = $this->actingAs($vendedor)->get(route('remisiones.imprimir', $remision));

        $response->assertOk();
        $response->assertSee('PLACA IGU-10411');
        $response->assertSee('000001');
        $response->assertDontSee('C.C. 1.121.845.330');
        $response->assertSeeInOrder(['QUIEN DESPACHA', 'Sebastián Cárdenas', 'CLIENTE', 'Carlos Piñeros']);
        $response->assertSee('901.220.664-9');
        $this->assertSame(1, substr_count($response->getContent(), 'COPIA VENDEDOR'));
        $this->assertSame(2, substr_count($response->getContent(), 'COPIA CLIENTE'));
    }

    public function test_downloads_pdf(): void
    {
        $vendedor = User::factory()->create();
        $remision = app(RemisionService::class)->emitir($vendedor, $this->datos(Comprador::factory()->create(), Material::factory()->create(['stock_m3' => 50])));

        $response = $this->actingAs($vendedor)->get(route('remisiones.pdf', $remision));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('remision-000001.pdf');
    }

    public function test_buyer_search_returns_matches_with_balances(): void
    {
        $comprador = Comprador::factory()->create(['nombre' => 'Prefabricados del Meta']);
        MovimientoCartera::factory()->for($comprador)->deuda(300_000)->create();
        Comprador::factory()->create(['nombre' => 'Otra empresa']);

        $response = $this->actingAs(User::factory()->create())->getJson(route('compradores.buscar', ['q' => 'prefab']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.nombre', 'Prefabricados del Meta');
        $response->assertJsonPath('0.deuda', 300_000);
    }
}
