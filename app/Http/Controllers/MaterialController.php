<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarStockRequest;
use App\Http\Requests\GuardarMaterialRequest;
use App\Models\Auditoria;
use App\Models\Material;
use App\Models\RemisionLinea;
use App\Support\Formato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaterialController extends Controller
{
    /**
     * Catálogo con precio, stock en patio y m³ vendidos en el mes.
     */
    public function index(): View
    {
        $vendidoMes = RemisionLinea::query()
            ->whereHas('remision', fn (Builder $q) => $q->emitidas()->where('emitida_en', '>=', now()->startOfMonth()))
            ->selectRaw('material_id, sum(cantidad_m3) as m3')
            ->groupBy('material_id')
            ->pluck('m3', 'material_id');

        return view('materiales.index', [
            'materiales' => Material::query()->orderByDesc('activo')->orderBy('nombre')->get(),
            'vendidoMes' => $vendidoMes,
        ]);
    }

    public function store(GuardarMaterialRequest $request): RedirectResponse
    {
        $material = Material::query()->create($request->validated());

        Auditoria::registrar('material.creado', $material, $material->only(['nombre', 'precio_m3', 'stock_m3']));

        return to_route('materiales.index')->with('ok', 'Material "'.$material->nombre.'" creado.');
    }

    public function update(GuardarMaterialRequest $request, Material $material): RedirectResponse
    {
        $material->fill($request->validated());
        $cambios = collect($material->getDirty())->map(fn ($nuevo, $campo) => ['antes' => $material->getOriginal($campo), 'despues' => $nuevo]);
        $material->save();

        if ($cambios->isNotEmpty()) {
            Auditoria::registrar('material.editado', $material, $cambios->all());
        }

        $mensaje = $cambios->has('precio_m3')
            ? 'Precio de '.$material->nombre.' actualizado a '.Formato::dinero($material->precio_m3).' por m³. Las remisiones ya emitidas conservan su precio.'
            : 'Material "'.$material->nombre.'" actualizado.';

        return to_route('materiales.index')->with('ok', $mensaje);
    }

    /**
     * Entrada de material al patio o corrección por conteo físico.
     */
    public function ajustarStock(AjustarStockRequest $request, Material $material): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($material, $datos): void {
            $material = Material::query()->lockForUpdate()->findOrFail($material->id);
            $antes = (float) $material->stock_m3;
            $despues = $datos['tipo'] === 'entrada' ? $antes + (float) $datos['cantidad_m3'] : (float) $datos['cantidad_m3'];

            $material->update(['stock_m3' => $despues]);

            Auditoria::registrar('material.stock', $material, [
                'tipo' => $datos['tipo'],
                'cantidad' => (float) $datos['cantidad_m3'],
                'antes' => $antes,
                'despues' => $despues,
                'nota' => $datos['nota'] ?? null,
            ]);
        });

        return to_route('materiales.index')->with('ok', 'Stock de '.$material->nombre.' actualizado: '.Formato::m3($material->fresh()->stock_m3).' m³ en patio.');
    }
}
