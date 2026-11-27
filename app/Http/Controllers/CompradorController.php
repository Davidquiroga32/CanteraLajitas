<?php

namespace App\Http\Controllers;

use App\Enums\FormaPago;
use App\Http\Requests\GuardarCompradorRequest;
use App\Models\Auditoria;
use App\Models\Comprador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompradorController extends Controller
{
    /**
     * Directorio de compradores. Con ?ver={id} se abre el panel con su historial.
     * El vendedor ve las compras que él registró; el administrador, todas.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $busqueda = trim((string) $request->query('q', ''));
        $remisionesVisibles = fn (Builder $q) => $q->emitidas()->visiblesPara($usuario);

        $compradores = Comprador::query()
            ->buscar($busqueda)
            ->conSaldos()
            ->withCount(['remisiones as compras' => $remisionesVisibles])
            ->withSum(['remisiones as total_comprado' => fn (Builder $q) => $remisionesVisibles($q)->where('forma_pago', '!=', FormaPago::Donacion)], 'total')
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        $seleccionado = null;

        if ($request->filled('ver')) {
            $seleccionado = Comprador::query()
                ->conSaldos()
                ->withCount(['remisiones as compras' => $remisionesVisibles])
                ->withSum(['remisiones as total_comprado' => fn (Builder $q) => $remisionesVisibles($q)->where('forma_pago', '!=', FormaPago::Donacion)], 'total')
                ->find($request->integer('ver'));

            $seleccionado?->setRelation('historial', $seleccionado->remisiones()
                ->emitidas()
                ->visiblesPara($usuario)
                ->with('lineas.material')
                ->latest('emitida_en')
                ->limit(15)
                ->get());
        }

        return view('compradores.index', [
            'compradores' => $compradores,
            'busqueda' => $busqueda,
            'seleccionado' => $seleccionado,
        ]);
    }

    public function store(GuardarCompradorRequest $request): RedirectResponse
    {
        $comprador = Comprador::query()->create($request->datosComprador());

        Auditoria::registrar('comprador.creado', $comprador, $comprador->only(['nombre', 'documento']));

        return to_route('compradores.index', ['ver' => $comprador->id])->with('ok', 'Comprador "'.$comprador->nombre.'" guardado.');
    }

    public function update(GuardarCompradorRequest $request, Comprador $comprador): RedirectResponse
    {
        $comprador->fill($request->datosComprador());
        $cambios = collect($comprador->getDirty())->map(fn ($nuevo, $campo) => ['antes' => $comprador->getOriginal($campo), 'despues' => $nuevo]);
        $comprador->save();

        if ($cambios->isNotEmpty()) {
            Auditoria::registrar('comprador.editado', $comprador, $cambios->all());
        }

        return to_route('compradores.index', ['ver' => $comprador->id])->with('ok', 'Datos de "'.$comprador->nombre.'" actualizados.');
    }
}
