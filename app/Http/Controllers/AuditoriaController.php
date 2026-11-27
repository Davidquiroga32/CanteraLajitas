<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use App\Support\ExcelExport;
use App\Support\RangoFechas;
use App\Support\TextoAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Auditoría (solo administrador): quién hizo qué y cuándo. Solo se consulta;
 * nadie puede editar ni borrar registros.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $rango = RangoFechas::desdeRequest($request);

        return view('auditoria.index', [
            'rango' => $rango,
            'usuarioId' => $request->integer('usuario') ?: null,
            'categoria' => $this->categoria($request),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
            'registros' => $this->consulta($request, $rango)->paginate(50)->withQueryString(),
        ]);
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $rango = RangoFechas::desdeRequest($request);

        return ExcelExport::descargar(
            'auditoria-'.$rango->desde->format('Ymd').'-'.$rango->hasta->format('Ymd').'.xlsx',
            'Auditoría · Cantera Lajitas S.A.S.',
            $rango->descripcion(),
            [
                ['titulo' => 'Fecha', 'ancho' => 17],
                ['titulo' => 'Usuario', 'ancho' => 26],
                ['titulo' => 'Tipo', 'ancho' => 22],
                ['titulo' => 'Qué hizo', 'ancho' => 80],
                ['titulo' => 'IP', 'ancho' => 16],
            ],
            $this->consulta($request, $rango)->lazy(500)->map(fn (Auditoria $r): array => [
                $r->created_at->format('d/m/Y H:i:s'),
                $r->user->name ?? 'Sin sesión',
                TextoAuditoria::categoria($r),
                TextoAuditoria::describir($r),
                $r->ip,
            ]),
        );
    }

    /**
     * @return Builder<Auditoria>
     */
    private function consulta(Request $request, RangoFechas $rango): Builder
    {
        $categoria = $this->categoria($request);

        return Auditoria::query()
            ->with(['user', 'auditable'])
            ->whereBetween('created_at', $rango->limites())
            ->when($request->integer('usuario'), fn (Builder $q, int $id) => $q->where('user_id', $id))
            ->when($categoria, fn (Builder $q, string $c) => $q->where(fn (Builder $w) => $w->where('accion', $c)->orWhere('accion', 'like', $c.'.%')))
            ->latest('created_at')
            ->latest('id');
    }

    private function categoria(Request $request): ?string
    {
        $categoria = (string) $request->query('tipo');

        return array_key_exists($categoria, TextoAuditoria::CATEGORIAS) ? $categoria : null;
    }
}
