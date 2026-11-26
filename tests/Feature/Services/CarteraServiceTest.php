<?php

namespace Tests\Feature\Services;

use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\MovimientoCartera;
use App\Models\User;
use App\Services\CarteraService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarteraServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_payment_reduces_debt(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(1_000_000)->create();

        app(CarteraService::class)->registrarAbono($comprador, 400_000, FormaPago::Efectivo, User::factory()->create());

        $this->assertSame(600_000, $comprador->saldoCredito());
    }

    public function test_rejects_payment_larger_than_debt(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(300_000)->create();

        try {
            app(CarteraService::class)->registrarAbono($comprador, 300_001, FormaPago::Efectivo, User::factory()->create());
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('valor', $e->errors());
        }

        $this->assertSame(300_000, $comprador->saldoCredito());
    }

    public function test_advance_increases_balance_in_favor(): void
    {
        $comprador = Comprador::factory()->create();

        app(CarteraService::class)->registrarAnticipo($comprador, 100_000_000, FormaPago::Transferencia, User::factory()->create());

        $this->assertSame(100_000_000, $comprador->saldoAnticipo());
        $this->assertSame(0, $comprador->saldoCredito());
    }

    /**
     * @return array<string, array{FormaPago}>
     */
    public static function mediosNoPermitidos(): array
    {
        return [
            'credito' => [FormaPago::Credito],
            'anticipo' => [FormaPago::Anticipo],
            'donacion' => [FormaPago::Donacion],
        ];
    }

    #[DataProvider('mediosNoPermitidos')]
    public function test_rejects_advance_with_non_money_payment_method(FormaPago $medio): void
    {
        $comprador = Comprador::factory()->create();

        $this->expectException(ValidationException::class);

        app(CarteraService::class)->registrarAnticipo($comprador, 1_000_000, $medio, User::factory()->create());
    }

    public function test_balances_are_loaded_for_listings(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(700_000)->create();
        MovimientoCartera::factory()->for($comprador)->create(['valor' => 2_000_000]);

        $conSaldos = Comprador::query()->conSaldos()->findOrFail($comprador->id);

        $this->assertSame(700_000, (int) $conSaldos->saldo_credito);
        $this->assertSame(2_000_000, (int) $conSaldos->saldo_anticipo);
    }
}
