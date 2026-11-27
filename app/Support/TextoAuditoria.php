<?php

namespace App\Support;

use App\Enums\FormaPago;
use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\Remision;
use App\Models\User;

/**
 * Convierte cada registro de auditoría en una frase en español para la pantalla
 * y las exportaciones: "Emitió la remisión 000365 por $ 1.200.000".
 */
class TextoAuditoria
{
    /**
     * Grupos de acciones para el filtro (prefijo de la acción => texto).
     *
     * @var array<string, string>
     */
    public const CATEGORIAS = [
        'sesion' => 'Ingresos al sistema',
        'remision' => 'Remisiones',
        'cartera' => 'Abonos y anticipos',
        'material' => 'Materiales y stock',
        'comprador' => 'Compradores',
        'usuario' => 'Usuarios y contraseñas',
        'descarga' => 'Descargas de Excel y PDF',
    ];

    /** @var array<string, string> */
    private const DESCARGAS = [
        'remisiones.excel' => 'el listado de remisiones en Excel',
        'remisiones.listado-pdf' => 'el listado de remisiones en PDF',
        'cartera.excel' => 'el listado de cartera en Excel',
        'cartera.pdf' => 'el listado de cartera en PDF',
        'cartera.estado-excel' => 'el estado de cuenta en Excel',
        'cartera.estado-pdf' => 'el estado de cuenta en PDF',
        'reportes.excel' => 'el reporte de ventas en Excel',
        'reportes.pdf' => 'el reporte de ventas en PDF',
        'reportes.cliente-excel' => 'el reporte por cliente en Excel',
        'reportes.cliente-pdf' => 'el reporte por cliente en PDF',
        'reportes.deudores-excel' => 'el reporte de clientes que deben en Excel',
        'reportes.deudores-pdf' => 'el reporte de clientes que deben en PDF',
        'auditoria.excel' => 'la auditoría en Excel',
    ];

    /** @var array<string, string> */
    private const CAMPOS = [
        'nombre' => 'nombre',
        'name' => 'nombre',
        'precio_m3' => 'precio',
        'stock_m3' => 'stock',
        'stock_minimo_m3' => 'stock mínimo',
        'color' => 'color',
        'activo' => 'activo',
        'tipo_documento' => 'tipo de documento',
        'documento' => 'documento',
        'ciudad' => 'municipio',
        'retefuente' => 'ReteFuente',
        'reteica' => 'ReteICA',
        'email' => 'correo',
        'cedula' => 'cédula',
        'cargo' => 'cargo',
        'role' => 'rol',
    ];

    public static function categoria(Auditoria $registro): string
    {
        return self::CATEGORIAS[strtok($registro->accion, '.')] ?? 'Otros';
    }

    public static function describir(Auditoria $registro): string
    {
        $d = $registro->datos ?? [];
        $objeto = self::nombreDe($registro);

        return match ($registro->accion) {
            'sesion.ingreso' => ($d['automatico'] ?? false) ? 'Entró al sistema (sesión recordada en su equipo)' : 'Entró al sistema',
            'sesion.salida' => 'Cerró sesión',
            'sesion.fallido' => ($d['motivo'] ?? '') === 'inactivo'
                ? 'Intentó entrar con un usuario desactivado ('.($d['correo'] ?? '').')'
                : 'Intento de ingreso fallido con el correo '.($d['correo'] ?? ''),
            'sesion.bloqueo' => 'Ingreso bloqueado por muchos intentos con el correo '.($d['correo'] ?? ''),
            'remision.emitida' => 'Emitió la remisión '.self::numero($d['numero'] ?? null).' por '.Formato::dinero($d['total'] ?? 0),
            'remision.anulada' => 'Anuló la remisión '.self::numero($d['numero'] ?? null).'. Motivo: '.($d['motivo'] ?? '—'),
            'cartera.abono' => 'Registró un abono de '.Formato::dinero($d['valor'] ?? 0).self::medio($d).' de '.$objeto,
            'cartera.anticipo' => 'Registró un anticipo de '.Formato::dinero($d['valor'] ?? 0).self::medio($d).' de '.$objeto,
            'material.creado' => 'Creó el material '.$objeto.' a '.Formato::dinero($d['precio_m3'] ?? 0).' por m³',
            'material.editado' => 'Editó el material '.$objeto.': '.self::cambios($d),
            'material.stock' => 'Ajustó el stock de '.$objeto.': '.Formato::m3($d['antes'] ?? 0).' → '.Formato::m3($d['despues'] ?? 0).' m³'.(filled($d['nota'] ?? null) ? ' ('.$d['nota'].')' : ''),
            'comprador.creado' => 'Creó el comprador '.$objeto,
            'comprador.editado' => 'Editó el comprador '.$objeto.': '.self::cambios($d),
            'usuario.creado' => 'Creó el usuario '.$objeto.self::rol($d['role'] ?? null),
            'usuario.editado' => 'Editó el usuario '.$objeto.': '.self::cambios($d),
            'usuario.contrasena' => 'Asignó una contraseña nueva a '.$objeto.' y cerró sus sesiones abiertas',
            'usuario.contrasena_propia' => 'Cambió su propia contraseña',
            'descarga' => 'Descargó '.(self::DESCARGAS[$d['ruta'] ?? ''] ?? 'un archivo').($registro->auditable ? ' de '.$objeto : '').self::filtros($d['filtros'] ?? []),
            default => $registro->accion,
        };
    }

    /**
     * Enlace a lo que se auditó (remisión o cartera del comprador), si existe.
     */
    public static function enlace(Auditoria $registro): ?string
    {
        return match (true) {
            $registro->auditable instanceof Remision => route('remisiones.show', $registro->auditable),
            $registro->auditable instanceof Comprador => route('cartera.show', $registro->auditable),
            $registro->auditable instanceof Material => route('materiales.index'),
            $registro->auditable instanceof User => route('equipo.index'),
            default => null,
        };
    }

    private static function nombreDe(Auditoria $registro): string
    {
        return match (true) {
            $registro->auditable instanceof Remision => $registro->auditable->numeroFormateado(),
            $registro->auditable instanceof Comprador, $registro->auditable instanceof Material => $registro->auditable->nombre,
            $registro->auditable instanceof User => $registro->auditable->name,
            default => '—',
        };
    }

    private static function numero(mixed $numero): string
    {
        return $numero === null ? '—' : str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private static function medio(array $datos): string
    {
        $medio = FormaPago::tryFrom((string) ($datos['medio'] ?? ''));

        return $medio ? ' en '.mb_strtolower($medio->label()) : '';
    }

    private static function rol(mixed $rol): string
    {
        $rol = $rol instanceof Rol ? $rol : Rol::tryFrom((string) $rol);

        return $rol ? ' ('.mb_strtolower($rol->label()).')' : '';
    }

    /**
     * "precio $ 85.000 → $ 90.000; activo Sí → No".
     *
     * @param  array<string, mixed>  $datos
     */
    private static function cambios(array $datos): string
    {
        $partes = [];

        foreach ($datos as $campo => $cambio) {
            if (! is_array($cambio) || ! array_key_exists('despues', $cambio)) {
                continue;
            }

            $partes[] = (self::CAMPOS[$campo] ?? $campo).' '.self::valor($campo, $cambio['antes'] ?? null).' → '.self::valor($campo, $cambio['despues']);
        }

        return $partes ? implode('; ', $partes) : 'sin cambios';
    }

    private static function valor(string $campo, mixed $valor): string
    {
        return match (true) {
            $valor === null || $valor === '' => '(vacío)',
            $campo === 'precio_m3' => Formato::dinero($valor),
            in_array($campo, ['stock_m3', 'stock_minimo_m3'], true) => Formato::m3($valor).' m³',
            in_array($campo, ['activo', 'retefuente', 'reteica'], true) => $valor ? 'Sí' : 'No',
            $campo === 'role' => Rol::tryFrom((string) $valor)?->label() ?? (string) $valor,
            default => is_scalar($valor) ? (string) $valor : json_encode($valor),
        };
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private static function filtros(array $filtros): string
    {
        $partes = [];

        if (isset($filtros['rango'])) {
            $partes[] = 'periodo: '.(RangoFechas::PRESETS[$filtros['rango']] ?? $filtros['rango']);
        } elseif (isset($filtros['desde']) || isset($filtros['hasta'])) {
            $partes[] = 'del '.($filtros['desde'] ?? '…').' al '.($filtros['hasta'] ?? '…');
        }

        if (isset($filtros['ver'])) {
            $partes[] = $filtros['ver'] === 'anticipos' ? 'anticipos' : 'deudores';
        }

        return $partes ? ' ('.implode(', ', $partes).')' : '';
    }
}
