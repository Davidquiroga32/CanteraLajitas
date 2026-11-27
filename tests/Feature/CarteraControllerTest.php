<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\MovimientoCartera;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CarteraControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_debtors_with_total(): void
    {
        $debe = Comprador::factory()->create(['nombre' => 'Hugo Salcedo']);
        MovimientoCartera::factory()->for($debe)->deuda(1_500_000)->create();
        Comprador::factory()->create(['nombre' => 'Al Día SAS']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('cartera.index'));

        $response->assertOk();
        $response->assertSee('Hugo Salcedo');
        $response->assertDontSee('Al Día SAS');
        $response->assertSee('$ 1.500.000');
    }

    public function test_lists_buyers_with_advance(): void
    {
        $conAnticipo = Comprador::factory()->create(['nombre' => 'Consorcio Vial']);
        MovimientoCartera::factory()->for($conAnticipo)->create(['valor' => 100_000_000]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('cartera.index', ['ver' => 'anticipos']));

        $response->assertSee('Consorcio Vial');
        $response->assertSee('$ 100.000.000');
    }

    public function test_registers_payment_with_formatted_value(): void
    {
        $admin = User::factory()->admin()->create();
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(1_000_000)->create();

        $response = $this->actingAs($admin)->post(route('cartera.abono', $comprador), [
            'valor' => '400.000',
            'medio_pago' => 'transferencia',
            'fecha' => now()->toDateString(),
            'nota' => 'Consignación',
        ]);

        $response->assertRedirect(route('cartera.show', $comprador));
        $this->assertSame(600_000, $comprador->saldoCredito());
        $this->assertDatabaseHas('auditoria', ['accion' => 'cartera.abono', 'user_id' => $admin->id]);
    }

    public function test_rejects_payment_above_debt_in_its_own_form(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(100_000)->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('cartera.abono', $comprador), [
            'valor' => '200000',
            'medio_pago' => 'efectivo',
            'fecha' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('valor', errorBag: 'abono');
        $this->assertSame(100_000, $comprador->saldoCredito());
    }

    public function test_rejects_future_date(): void
    {
        $comprador = Comprador::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('cartera.anticipo', $comprador), [
            'valor' => '1000000',
            'medio_pago' => 'efectivo',
            'fecha' => now()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('fecha', errorBag: 'anticipo');
        $this->assertSame(0, $comprador->saldoAnticipo());
    }

    public function test_registers_advance(): void
    {
        $comprador = Comprador::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('cartera.anticipo', $comprador), [
            'valor' => '100.000.000',
            'medio_pago' => 'transferencia',
            'fecha' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(100_000_000, $comprador->saldoAnticipo());
    }

    public function test_advance_from_buyers_panel_returns_to_panel(): void
    {
        $comprador = Comprador::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('compradores.index', ['ver' => $comprador->id]))
            ->assertSee('Registrar anticipo')
            ->assertSee('Valor recibido (pesos)');

        $response = $this->actingAs($admin)->post(route('cartera.anticipo', $comprador), [
            'valor' => '5.000.000',
            'medio_pago' => 'efectivo',
            'fecha' => now()->toDateString(),
            '_volver' => 'compradores',
        ]);

        $response->assertRedirect(route('compradores.index', ['ver' => $comprador->id]));
        $this->assertSame(5_000_000, $comprador->saldoAnticipo());
    }

    public function test_seller_cannot_register_advance(): void
    {
        $comprador = Comprador::factory()->create();
        $vendedor = User::factory()->create();

        $this->actingAs($vendedor)->get(route('compradores.index', ['ver' => $comprador->id]))
            ->assertOk()
            ->assertDontSee('Registrar anticipo');

        $this->actingAs($vendedor)->post(route('cartera.anticipo', $comprador), [
            'valor' => '300.000',
            'medio_pago' => 'efectivo',
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, $comprador->saldoAnticipo());
    }

    public function test_seller_cannot_register_debt_payment(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(500_000)->create();

        $response = $this->actingAs(User::factory()->create())->post(route('cartera.abono', $comprador), [
            'valor' => '100000',
            'medio_pago' => 'efectivo',
            'fecha' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertSame(500_000, $comprador->saldoCredito());
    }

    public function test_statement_shows_running_balance(): void
    {
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(1_000_000)->create(['registrado_en' => now()->subDays(3)]);
        MovimientoCartera::factory()->for($comprador)->deuda(-250_000)->create(['tipo' => 'abono', 'registrado_en' => now()->subDays(2)]);
        MovimientoCartera::factory()->for($comprador)->deuda(500_000)->create(['registrado_en' => now()->subDay()]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('cartera.show', $comprador));

        $response->assertOk();
        $response->assertSeeInOrder(['$ 1.250.000', '$ 750.000', '$ 1.000.000']);
    }

    public function test_statement_has_back_button_to_the_page_it_came_from(): void
    {
        $admin = User::factory()->admin()->create();
        $comprador = Comprador::factory()->create();
        $desdeDeudores = route('reportes.deudores', ['orden' => 'dias']);

        $this->actingAs($admin)->from($desdeDeudores)->get(route('cartera.show', $comprador))
            ->assertOk()
            ->assertSee('Volver a clientes que deben')
            ->assertSee('href="'.e($desdeDeudores).'"', false);

        $this->actingAs($admin)->from('https://otro-sitio.example/pagina')->get(route('cartera.show', $comprador))
            ->assertSee('Volver a cartera')
            ->assertSee('href="'.route('cartera.index').'"', false);
    }

    public function test_excel_writes_text_that_looks_like_a_formula_as_plain_text(): void
    {
        $comprador = Comprador::factory()->create(['nombre' => '=1+1', 'ciudad' => '=HYPERLINK("http://malo.example","Ver")']);
        MovimientoCartera::factory()->for($comprador)->deuda(500_000)->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('cartera.excel'));

        $response->assertDownload();
        $zip = new \ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringNotContainsString('<f>', $hoja);
        $this->assertStringContainsString('=1+1', $hoja);
        $this->assertStringContainsString('=HYPERLINK(', html_entity_decode($hoja));
    }

    public function test_statement_exports(): void
    {
        $admin = User::factory()->admin()->create();
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(1_000_000)->create();

        $this->actingAs($admin)->get(route('cartera.estado-excel', $comprador))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('cartera.estado-pdf', $comprador))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('cartera.excel'))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('cartera.pdf', ['ver' => 'anticipos']))->assertOk();
    }

    public function test_cartera_is_admin_only(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create();

        $this->actingAs($vendedor)->get(route('cartera.show', $comprador))->assertForbidden();
        $this->actingAs($vendedor)->post(route('cartera.abono', $comprador), ['valor' => 1])->assertForbidden();
        $this->actingAs($vendedor)->get(route('cartera.estado-pdf', $comprador))->assertForbidden();
    }
}
