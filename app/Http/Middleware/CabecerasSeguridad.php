<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instrucciones de seguridad para el navegador en cada respuesta: que ninguna
 * otra página muestre el sistema dentro de la suya, que no adivine tipos de
 * archivo, que no envíe la dirección a otros sitios y, con HTTPS, que no vuelva
 * a usar HTTP.
 */
class CabecerasSeguridad
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->add([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Content-Security-Policy' => "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'",
        ]);

        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}
