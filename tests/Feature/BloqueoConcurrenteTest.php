<?php

namespace Tests\Feature;

use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\User;
use App\Services\CarteraService;
use App\Services\RemisionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Dos operaciones del mismo comprador al mismo tiempo (doble clic o dos vendedores)
 * no pueden leer el mismo saldo. Solo se puede comprobar con MySQL: SQLite no deja
 * escribir a dos a la vez. En GitHub Actions corre aparte, con MySQL:
 * `php artisan test --group=concurrencia`.
 */
#[Group('concurrencia')]
class BloqueoConcurrenteTest extends TestCase
{
    use DatabaseMigrations;

    private Comprador $comprador;

    protected function setUp(): void
    {
        if (getenv('DB_CONNECTION') !== 'mysql') {
            $this->markTestSkipped('Solo se puede comprobar con MySQL.');
        }

        parent::setUp();

        $this->comprador = Comprador::factory()->create();
        config(['database.connections.otra_venta' => config('database.connections.mysql')]);
    }

    protected function tearDown(): void
    {
        if (getenv('DB_CONNECTION') === 'mysql') {
            DB::connection('otra_venta')->rollBack();
            DB::purge('otra_venta');
        }

        parent::tearDown();
    }

    public function test_advance_sale_waits_while_another_sale_of_the_same_buyer_is_in_progress(): void
    {
        MovimientoCartera::factory()->for($this->comprador)->create(['valor' => 1_000_000]);
        $material = Material::factory()->create(['precio_m3' => 100_000]);
        $this->otraVentaEnCurso();

        $this->esperaElBloqueo(fn () => app(RemisionService::class)->emitir(User::factory()->create(), [
            'comprador_id' => $this->comprador->id,
            'conductor' => 'Carlos Piñeros',
            'placa' => 'FBI713',
            'maquina' => '938 G',
            'destino' => 'N/A',
            'forma_pago' => FormaPago::Anticipo,
            'lineas' => [['material_id' => $material->id, 'cantidad_m3' => 10]],
        ]));

        $this->assertSame(0, $this->comprador->remisiones()->count());
        $this->assertSame(1_000_000, $this->comprador->saldoAnticipo());
    }

    public function test_payment_waits_while_another_payment_of_the_same_buyer_is_in_progress(): void
    {
        MovimientoCartera::factory()->for($this->comprador)->deuda(500_000)->create();
        $usuario = User::factory()->admin()->create();
        $this->otraVentaEnCurso();

        $this->esperaElBloqueo(fn () => app(CarteraService::class)->registrarAbono($this->comprador, 500_000, FormaPago::Efectivo, $usuario));

        $this->assertSame(500_000, $this->comprador->saldoCredito());
    }

    /**
     * Desde otra conexión abre una transacción que tiene apartado al comprador.
     */
    private function otraVentaEnCurso(): void
    {
        $otra = DB::connection('otra_venta');
        $otra->beginTransaction();
        $otra->table('compradores')->where('id', $this->comprador->id)->lockForUpdate()->first();
    }

    /**
     * La operación debe quedarse esperando el bloqueo (aquí se corta a 1 segundo).
     */
    private function esperaElBloqueo(callable $operacion): void
    {
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $operacion();
            $this->fail('La operación no esperó a que terminara la otra del mismo comprador.');
        } catch (QueryException $e) {
            $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
        }
    }
}
