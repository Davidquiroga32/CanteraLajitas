<?php

namespace App\Support;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Genera un .xlsx sencillo (una o varias hojas): título, subtítulo, encabezados con el azul de la
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
        return self::descargarHojas($archivo, [[
            'nombre' => 'Hoja1',
            'titulo' => $titulo,
            'subtitulo' => $subtitulo,
            'columnas' => $columnas,
            'filas' => $filas,
            'totales' => $totales,
        ]]);
    }

    /**
     * Un libro con varias hojas, cada una con el mismo formato de `descargar`.
     *
     * @param  list<array{nombre: string, titulo: string, subtitulo: string, columnas: list<array{titulo: string, tipo?: string, ancho?: float}>, filas: iterable<list<mixed>>, totales?: list<mixed>|null}>  $hojas
     */
    public static function descargarHojas(string $archivo, array $hojas): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');

        $writer = new Writer(new Options);
        $writer->openToFile($ruta);

        foreach ($hojas as $i => $hoja) {
            $sheet = $i === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName(mb_substr($hoja['nombre'], 0, 31));

            foreach ($hoja['columnas'] as $c => $columna) {
                $sheet->setColumnWidth($columna['ancho'] ?? 16, $c + 1);
            }

            self::escribirHoja($writer, $hoja);
        }

        $writer->close();

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @param  array{titulo: string, subtitulo: string, columnas: list<array{titulo: string, tipo?: string, ancho?: float}>, filas: iterable<list<mixed>>, totales?: list<mixed>|null}  $hoja
     */
    private static function escribirHoja(Writer $writer, array $hoja): void
    {
        $columnas = $hoja['columnas'];

        $writer->addRow(self::fila([$hoja['titulo']], new Style(fontBold: true, fontSize: 14, fontColor: '162342')));
        $writer->addRow(self::fila([$hoja['subtitulo']], new Style(fontItalic: true, fontColor: '55534B')));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(self::fila(
            array_column($columnas, 'titulo'),
            new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: '162342'),
        ));

        $estilos = self::estilosPorColumna($columnas, false);

        foreach ($hoja['filas'] as $fila) {
            $writer->addRow(self::fila($fila, $estilos));
        }

        if (($hoja['totales'] ?? null) !== null) {
            $writer->addRow(self::fila($hoja['totales'], self::estilosPorColumna($columnas, true)));
        }
    }

    /**
     * Arma una fila escribiendo todo texto como texto: la librería convierte en
     * fórmula cualquier valor que empiece por "=", y un nombre de comprador o un
     * destino escrito así se ejecutaría al abrir el archivo en Excel.
     *
     * @param  list<mixed>  $valores
     * @param  Style|array<int, Style>  $estilos  Un estilo para toda la fila o uno por columna.
     */
    private static function fila(array $valores, Style|array $estilos): Row
    {
        $celdas = [];

        foreach (array_values($valores) as $i => $valor) {
            $estilo = $estilos instanceof Style ? $estilos : ($estilos[$i] ?? null);
            $celdas[] = is_string($valor) && $valor !== ''
                ? new StringCell($valor, $estilo)
                : Cell::fromValue($valor, $estilo);
        }

        return new Row($celdas);
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
