<?php

namespace Database\Seeders;

use App\Enums\FormaPago;
use App\Enums\Rol;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\User;
use App\Services\CarteraService;
use App\Services\RemisionService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Remisiones, anticipos y abonos de los últimos 82 días (solo desarrollo).
 * Usa los servicios reales, así que stock y cartera quedan consistentes.
 * Es determinista: siempre genera los mismos datos.
 */
class DemoVentasSeeder extends Seeder
{
    private const DIAS = 82;

    private const MAQUINAS = ['938 G', '950 H', 'Retro 320', 'Cargador 924'];

    private const DESTINOS = ['Obra Vía Puerto López km 8', 'Barrio La Esperanza', 'Vereda Apiay', 'Acacías centro', 'N/A'];

    private const CONDUCTORES = ['Carlos Piñeros', 'Luis Ángel Rey', 'Óscar Barrera', 'Javier Mora', 'Édgar Lozano', 'Wilson Torres'];

    public function __construct(
        private RemisionService $remisiones,
        private CarteraService $cartera,
    ) {}

    public function run(): void
    {
        mt_srand(918);

        $admin = User::query()->where('role', Rol::Admin)->firstOrFail();
        $vendedores = User::query()->where('role', Rol::Vendedor)->get()->values();
        $compradores = Comprador::query()->orderBy('id')->get()->values();

        $conAnticipo = $compradores->filter(fn (Comprador $c): bool => in_array($c->documento, ['901.220.664-9', '900.412.118-3'], true))->values();
        $comunidad = $compradores->firstWhere('documento', '822.007.551-0');

        $inicio = now()->subDays(self::DIAS)->setTime(8, 0);
        $this->cartera->registrarAnticipo($conAnticipo[0], 100_000_000, FormaPago::Transferencia, $admin, 'Anticipo contrato vía', $inicio);
        $this->cartera->registrarAnticipo($conAnticipo[1], 30_000_000, FormaPago::Transferencia, $admin, 'Anticipo obra', $inicio);

        for ($offset = self::DIAS; $offset >= 0; $offset--) {
            $dia = now()->subDays($offset);
            $esDomingo = $dia->isSunday();
            $cantidad = $esDomingo ? mt_rand(0, 1) : ($offset === 0 ? 2 : mt_rand(3, 7));

            $horas = collect(range(1, $cantidad))->map(fn (): array => [mt_rand(6, 15), mt_rand(0, 59)])->sort()->values();

            foreach ($horas as [$hora, $minuto]) {
                $comprador = $compradores[min($compradores->count() - 1, (int) floor((mt_rand() / mt_getrandmax()) ** 1.4 * $compradores->count()))];
                $formaPago = $this->formaPagoPara($comprador, $conAnticipo, $comunidad);

                $datos = [
                    'comprador_id' => $comprador->id,
                    'conductor' => self::CONDUCTORES[mt_rand(0, count(self::CONDUCTORES) - 1)],
                    'placa' => $this->placa(),
                    'maquina' => self::MAQUINAS[mt_rand(0, count(self::MAQUINAS) - 1)],
                    'destino' => self::DESTINOS[mt_rand(0, count(self::DESTINOS) - 1)],
                    'forma_pago' => $formaPago,
                    'observaciones' => null,
                    'lineas' => $this->lineas(Material::query()->where('stock_m3', '>=', 20)->orderBy('id')->get()),
                ];

                $this->remisiones->emitir($vendedores[mt_rand(0, $vendedores->count() - 1)], $datos, $dia->copy()->setTime($hora, $minuto));
            }

            if ($offset % 10 === 0) {
                $this->registrarAbonos($compradores, $admin, $dia->copy()->setTime(17, 0));
            }
        }
    }

    /**
     * @param  Collection<int, Comprador>  $conAnticipo
     */
    private function formaPagoPara(Comprador $comprador, $conAnticipo, ?Comprador $comunidad): FormaPago
    {
        if ($comunidad && $comprador->is($comunidad)) {
            return FormaPago::Donacion;
        }

        if ($conAnticipo->contains($comprador) && $comprador->saldoAnticipo() > 0) {
            return FormaPago::Anticipo;
        }

        $dado = mt_rand(1, 100);

        return match (true) {
            $dado <= 45 => FormaPago::Efectivo,
            $dado <= 75 => FormaPago::Transferencia,
            default => FormaPago::Credito,
        };
    }

    /**
     * @param  Collection<int, Material>  $materiales
     * @return list<array{material_id: int, cantidad_m3: int}>
     */
    private function lineas($materiales): array
    {
        $numero = mt_rand(1, 100) <= 30 ? mt_rand(1, 3) : mt_rand(1, 2);

        return $materiales->shuffle(mt_rand())->take($numero)->map(fn (Material $m): array => [
            'material_id' => $m->id,
            'cantidad_m3' => [6, 7, 8, 10, 12, 14, 16, 20][mt_rand(0, 7)],
        ])->values()->all();
    }

    /**
     * Algunos deudores abonan entre el 40 % y el 100 % de lo que deben.
     *
     * @param  Collection<int, Comprador>  $compradores
     */
    private function registrarAbonos($compradores, User $admin, CarbonInterface $fecha): void
    {
        foreach ($compradores as $comprador) {
            $deuda = $comprador->saldoCredito();

            if ($deuda > 0 && mt_rand(1, 100) <= 60) {
                $abono = (int) (round($deuda * mt_rand(40, 100) / 100 / 1000) * 1000);
                $abono = max(1000, min($abono, $deuda));
                $medio = mt_rand(0, 1) ? FormaPago::Efectivo : FormaPago::Transferencia;
                $this->cartera->registrarAbono($comprador, $abono, $medio, $admin, 'Abono', $fecha);
            }
        }
    }

    private function placa(): string
    {
        $letras = 'ABCDEFGHJKLMNPRSTUVWXYZ';

        return $letras[mt_rand(0, 22)].$letras[mt_rand(0, 22)].$letras[mt_rand(0, 22)].' '.mt_rand(100, 999);
    }
}
