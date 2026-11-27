<?php

namespace Tests\Feature\Services;

use App\Enums\CuentaCartera;
use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Comprador;
use App\Models\Configuracion;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\User;
use App\Services\RemisionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemisionServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function datos(Comprador $comprador, Material $material, FormaPago $formaPago = FormaPago::Efectivo, float $cantidad = 10): array
    {
        return [
            'comprador_id' => $comprador->id,
            'conductor' => 'Carlos Piñeros',
            'placa' => 'fbi 713',
            'maquina' => '938 G',
            'destino' => 'N/A',
            'forma_pago' => $formaPago,
            'lineas' => [['material_id' => $material->id, 'cantidad_m3' => $cantidad]],
        ];
    }

    public function test_emits_remision_with_catalog_prices_and_logged_in_seller(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create();
        $arena = Material::factory()->create(['precio_m3' => 85_000]);
        $rajon = Material::factory()->create(['precio_m3' => 120_000]);
        $datos = [...$this->datos($comprador, $arena), 'lineas' => [
            ['material_id' => $arena->id, 'cantidad_m3' => 6],
            ['material_id' => $rajon->id, 'cantidad_m3' => 2.5],
        ]];

        $remision = app(RemisionService::class)->emitir($vendedor, $datos);

        $this->assertSame(810_000, $remision->total);
        $this->assertTrue($remision->vendedor->is($vendedor));
        $this->assertSame('FBI 713', $remision->placa);
        $this->assertSame(EstadoRemision::Emitida, $remision->estado);
        $this->assertDatabaseHas('remision_lineas', ['remision_id' => $remision->id, 'material_id' => $arena->id, 'precio_m3' => 85_000, 'subtotal' => 510_000]);
        $this->assertDatabaseHas('remision_lineas', ['remision_id' => $remision->id, 'material_id' => $rajon->id, 'precio_m3' => 120_000, 'subtotal' => 300_000]);
    }

    public function test_numbering_starts_at_one_and_increments(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create();
        $material = Material::factory()->create();
        $servicio = app(RemisionService::class);

        $primera = $servicio->emitir($vendedor, $this->datos($comprador, $material));
        $segunda = $servicio->emitir($vendedor, $this->datos($comprador, $material));

        $this->assertSame('000001', $primera->numeroFormateado());
        $this->assertSame('000002', $segunda->numeroFormateado());
    }

    public function test_numbering_starts_at_configured_initial_number(): void
    {
        Configuracion::guardar('remision_numero_inicial', '500');

        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), Material::factory()->create()));

        $this->assertSame(500, $remision->numero);
    }

    public function test_price_change_does_not_alter_issued_remisiones(): void
    {
        $material = Material::factory()->create(['precio_m3' => 50_000]);
        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), $material));

        $material->update(['precio_m3' => 70_000]);

        $this->assertSame(500_000, $remision->fresh()->total);
        $this->assertSame(50_000, $remision->lineas()->first()->precio_m3);
    }

    public function test_decrements_material_stock(): void
    {
        $material = Material::factory()->create(['stock_m3' => 100]);

        app(RemisionService::class)->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), $material, cantidad: 12.5));

        $this->assertSame('87.50', $material->fresh()->stock_m3);
    }

    public function test_creates_new_buyer_when_document_is_not_registered(): void
    {
        $datos = $this->datos(Comprador::factory()->make(), Material::factory()->create());
        unset($datos['comprador_id']);
        $datos['comprador'] = ['nombre' => 'Obras del Llano', 'tipo_documento' => 'NIT', 'documento' => '901.555.000-1'];

        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $datos);

        $this->assertSame('Obras del Llano', $remision->comprador->nombre);
        $this->assertSame(TipoDocumento::NIT, $remision->comprador->tipo_documento);
    }

    public function test_reuses_existing_buyer_with_same_document(): void
    {
        $existente = Comprador::factory()->create(['documento' => '17.345.220']);
        $datos = $this->datos($existente, Material::factory()->create());
        unset($datos['comprador_id']);
        $datos['comprador'] = ['nombre' => 'Otro nombre', 'tipo_documento' => 'CC', 'documento' => '17.345.220'];

        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $datos);

        $this->assertTrue($remision->comprador->is($existente));
        $this->assertSame(1, Comprador::query()->count());
    }

    public function test_credit_sale_adds_to_buyer_debt(): void
    {
        $comprador = Comprador::factory()->create();
        $material = Material::factory()->create(['precio_m3' => 100_000]);

        app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Credito));

        $this->assertSame(1_000_000, $comprador->saldoCredito());
    }

    public function test_advance_payment_sale_discounts_from_buyer_balance(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 3_000_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000]);

        app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Anticipo));

        $this->assertSame(2_000_000, $comprador->saldoAnticipo());
    }

    public function test_difference_not_covered_by_advance_becomes_debt_when_left_on_credit(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 300_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000]);

        app(RemisionService::class)->emitir(User::factory()->create(), [...$this->datos($comprador, $material, FormaPago::Anticipo), 'pago_diferencia' => FormaPago::Credito]);

        $this->assertSame(0, $comprador->saldoAnticipo());
        $this->assertSame(700_000, $comprador->saldoCredito());
    }

    public function test_buyer_taxes_are_discounted_from_total(): void
    {
        $comprador = Comprador::factory()->create(['retefuente' => true, 'reteica' => true]);
        $material = Material::factory()->create(['precio_m3' => 100_000]);

        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Credito));

        $this->assertSame(1_000_000, $remision->subtotal);
        $this->assertSame(25_000, $remision->retefuente);
        $this->assertSame(10_000, $remision->reteica);
        $this->assertSame(965_000, $remision->total);
        $this->assertSame(965_000, $comprador->saldoCredito());
    }

    public function test_donations_have_no_taxes(): void
    {
        $comprador = Comprador::factory()->create(['retefuente' => true]);
        $material = Material::factory()->create(['precio_m3' => 100_000]);

        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Donacion));

        $this->assertSame(0, $remision->retefuente);
        $this->assertSame(1_000_000, $remision->total);
    }

    public function test_rejects_advance_payment_when_buyer_has_no_advance(): void
    {
        $comprador = Comprador::factory()->create();
        $material = Material::factory()->create(['stock_m3' => 50]);

        try {
            app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Anticipo));
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('forma_pago', $e->errors());
        }

        $this->assertSame(0, Remision::query()->count());
        $this->assertSame('50.00', $material->fresh()->stock_m3);
    }

    /**
     * @return array<string, array{int|float, float}>
     */
    public static function pedidosSinStock(): array
    {
        return [
            'sin existencias' => [0, 1],
            'pide más de lo que hay' => [5, 5.5],
        ];
    }

    #[DataProvider('pedidosSinStock')]
    public function test_rejects_sale_above_available_stock(int|float $stock, float $cantidad): void
    {
        $material = Material::factory()->create(['stock_m3' => $stock]);

        try {
            app(RemisionService::class)->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), $material, cantidad: $cantidad));
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('lineas', $e->errors());
        }

        $this->assertSame(0, Remision::query()->count());
    }

    public function test_rejects_sale_when_lines_of_same_material_exceed_stock(): void
    {
        $material = Material::factory()->create(['stock_m3' => 10]);
        $datos = [...$this->datos(Comprador::factory()->create(), $material), 'lineas' => [
            ['material_id' => $material->id, 'cantidad_m3' => 6],
            ['material_id' => $material->id, 'cantidad_m3' => 6],
        ]];

        $this->expectException(ValidationException::class);

        app(RemisionService::class)->emitir(User::factory()->create(), $datos);
    }

    public function test_out_of_stock_materials_are_not_offered_for_sale(): void
    {
        $conStock = Material::factory()->create(['stock_m3' => 3]);
        Material::factory()->create(['stock_m3' => 0]);
        Material::factory()->create(['stock_m3' => 50, 'activo' => false]);

        $disponibles = Material::query()->disponiblesParaVenta()->get();

        $this->assertSame([$conStock->id], $disponibles->modelKeys());
    }

    /**
     * @return array<string, array{FormaPago}>
     */
    public static function formasSinCartera(): array
    {
        return [
            'efectivo' => [FormaPago::Efectivo],
            'transferencia' => [FormaPago::Transferencia],
            'donacion' => [FormaPago::Donacion],
        ];
    }

    #[DataProvider('formasSinCartera')]
    public function test_paid_or_donated_sale_does_not_touch_cartera(FormaPago $formaPago): void
    {
        $comprador = Comprador::factory()->create();

        app(RemisionService::class)->emitir(User::factory()->create(), $this->datos($comprador, Material::factory()->create(), $formaPago));

        $this->assertSame(0, MovimientoCartera::query()->count());
    }

    public function test_cancelling_restores_stock_and_reverses_credit(): void
    {
        $admin = User::factory()->admin()->create();
        $comprador = Comprador::factory()->create();
        $material = Material::factory()->create(['precio_m3' => 100_000, 'stock_m3' => 100]);
        $servicio = app(RemisionService::class);
        $remision = $servicio->emitir(User::factory()->create(), $this->datos($comprador, $material, FormaPago::Credito));

        $anulada = $servicio->anular($remision, $admin, 'Error en la cantidad');

        $this->assertSame(EstadoRemision::Anulada, $anulada->estado);
        $this->assertTrue($anulada->anuladaPor->is($admin));
        $this->assertSame('100.00', $material->fresh()->stock_m3);
        $this->assertSame(0, $comprador->saldoCredito());
        $this->assertDatabaseHas('movimientos_cartera', [
            'remision_id' => $remision->id,
            'cuenta' => CuentaCartera::Credito->value,
            'tipo' => TipoMovimiento::ReversoAnulacion->value,
            'valor' => -1_000_000,
        ]);
    }

    public function test_cancelling_twice_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->create(['stock_m3' => 100]);
        $servicio = app(RemisionService::class);
        $remision = $servicio->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), $material));
        $servicio->anular($remision, $admin, 'Primera');

        $this->expectException(ValidationException::class);

        $servicio->anular($remision, $admin, 'Segunda');
    }

    public function test_emitting_records_an_audit_entry(): void
    {
        $remision = app(RemisionService::class)->emitir(User::factory()->create(), $this->datos(Comprador::factory()->create(), Material::factory()->create()));

        $this->assertDatabaseHas('auditoria', [
            'accion' => 'remision.emitida',
            'auditable_type' => $remision->getMorphClass(),
            'auditable_id' => $remision->id,
        ]);
    }
}
