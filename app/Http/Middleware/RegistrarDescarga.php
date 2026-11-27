<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja en la auditoría quién descargó cada exportación (Excel o PDF) y con qué filtros:
 * es información de clientes que sale del sistema.
 */
class RegistrarDescarga
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if ($respuesta->isSuccessful()) {
            $modelo = collect($request->route()?->parameters() ?? [])->first(fn ($valor) => $valor instanceof Model);

            Auditoria::registrar('descarga', $modelo, [
                'ruta' => $request->route()?->getName(),
                'filtros' => Arr::where($request->query(), fn ($valor) => is_scalar($valor) && $valor !== ''),
            ]);
        }

        return $respuesta;
    }
}
