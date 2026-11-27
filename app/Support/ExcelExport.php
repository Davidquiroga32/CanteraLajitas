<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Genera un .xlsx sencillo: título, subtítulo, encabezados con el azul de la
 * marca y filas con formato de pesos / m³ según la columna.
 */
class ExcelExport
{
    public const DINERO = 'dinero';

    public const M3 = 'm3';

    public const TEXTO = 'texto';

    /**
     * @param  list<array{titulo: string, tipo?: string, ancho?: float}>  $columnas
     * @param  iterable<list<mixed>>  $filas
     * @param  list<mixed>|null  $totales  Fila final en negrita (opcional).
     */
    public static function descargar(string $archivo, string $titulo, string $subtitulo, array $columnas, iterable $filas, ?array $totales = null): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');

        $opciones = new Options;
        foreach ($columnas as $i => $columna) {
            $opciones->setColumnWidth($columna['ancho'] ?? 16, $i + 1);
        }

        $writer = new Writer($opciones);
        $writer->openToFile($ruta);

        $writer->addRow(Row::fromValuesWithStyle([$titulo], new Style(fontBold: true, fontSize: 14, fontColor: '162342')));
        $writer->addRow(Row::fromValuesWithStyle([$subtitulo], new Style(fontItalic: true, fontColor: '55534B')));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(
            array_column($columnas, 'titulo'),
            new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: '162342'),
        ));

        $estilos = self::estilosPorColumna($columnas, false);

        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValuesWithStyles($fila, $estilos));
        }

        if ($totales !== null) {
            $writer->addRow(Row::fromValuesWithStyles($totales, self::estilosPorColumna($columnas, true)));
        }

        $writer->close();

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @param  list<array{titulo: string, tipo?: string, ancho?: float}>  $columnas
     * @return array<int, Style>
     */
    private static function estilosPorColumna(array $columnas, bool $negrita): array
    {
        return collect($columnas)->map(fn (array $c): Style => new Style(
            fontBold: $negrita,
            backgroundColor: $negrita ? 'FBEEDD' : null,
            format: match ($c['tipo'] ?? self::TEXTO) {
                self::DINERO => '"$" #,##0',
                self::M3 => '#,##0.##',
                default => null,
            },
        ))->all();
    }
}
