{{-- Estilos compartidos por los listados y estados de cuenta en PDF (dompdf). --}}
<style>
    @page { margin: 12mm 10mm 14mm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #16203a; }
    .cab { width: 100%; border-collapse: collapse; border-bottom: 2px solid #da7d15; margin-bottom: 8px; }
    .cab td { vertical-align: middle; padding-bottom: 6px; }
    .cab .t { font-size: 15px; font-weight: bold; color: #162342; }
    .cab .s { font-size: 9px; color: #55534b; margin-top: 2px; }
    .cab .e { text-align: right; font-size: 8.5px; color: #55534b; }
    .resumen { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 10px; }
    .resumen td { background: #e6e9f1; border-left: 3px solid #162342; padding: 6px 8px; }
    .resumen small { display: block; font-size: 8px; color: #55534b; }
    .resumen b { font-size: 13px; color: #162342; }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { background: #162342; color: #fff; font-size: 8px; text-align: left; padding: 5px 4px; }
    table.datos td { border-bottom: 1px solid #dbd5c6; padding: 4px; vertical-align: top; }
    table.datos tr:nth-child(even) td { background: #faf8f2; }
    table.datos tfoot td { font-weight: bold; background: #fbeedd; border-top: 1px solid #162342; }
    .der { text-align: right; }
    .centro { text-align: center; }
    .anulada td { color: #9a9488; text-decoration: line-through; }
    .rojo { color: #a8553a; }
    .verde { color: #3f5c3a; }
    h3 { font-size: 11px; color: #162342; margin: 12px 0 4px; }
</style>
