{{-- Estilos del documento de remisión (impresión y PDF). Compatibles con dompdf. --}}
<style>
    .rm { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11.5px; color: #16203a; border: 1.5px solid #162342; border-radius: 8px; padding: 12px 14px 10px; }
    .rm table { width: 100%; border-collapse: collapse; }
    .rm td, .rm th { vertical-align: middle; }
    .rm-cab td { vertical-align: top; }
    .rm-logo { width: 132px; }
    .rm-titulo { text-align: center; padding: 0 8px; }
    .rm-caja { border: 1.5px solid #162342; border-radius: 6px; padding: 5px 6px; }
    .rm-t1 { font-size: 15px; font-weight: bold; letter-spacing: .3px; }
    .rm-t2 { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .rm-legal { font-size: 9.5px; margin-top: 5px; color: #33405f; }
    .rm-control { width: 160px; }
    .rm-numcaja { border: 1.5px solid #162342; border-radius: 6px; padding: 7px 6px 6px; text-align: center; }
    .rm-numcaja .rm-numero { font-size: 24px; margin-top: 2px; }
    .rm-copia { background: #162342; color: #fff; font-weight: bold; text-align: center; font-size: 9.5px; letter-spacing: 1px; padding: 3px; border-radius: 4px; margin-top: 4px; }
    .rm-fila { margin-top: 6px; }
    .rm-fila td { border: 1px solid #162342; padding: 7px 8px; font-size: 12.5px; }
    .rm-num td { border: 0; padding: 6px 2px 2px; }
    .rm-et { font-weight: bold; font-size: 10.5px; letter-spacing: .3px; }
    .rm-hora { color: #55534b; font-size: 10.5px; }
    .rm-der { text-align: right; }
    .rm-numero { font-size: 19px; font-weight: bold; color: #b3261e; letter-spacing: 1px; }
    .rm-anulada { margin-top: 6px; border: 2px solid #b3261e; color: #b3261e; font-weight: bold; text-align: center; padding: 4px; font-size: 12px; }
    .rm-mat { margin-top: 6px; }
    .rm-mat th { background: #162342; color: #fff; font-size: 10px; letter-spacing: .4px; padding: 5px 6px; text-align: right; border: 1px solid #162342; }
    .rm-mat td { border: 1px solid #162342; padding: 6px 7px; text-align: right; font-size: 12.5px; }
    .rm-mat tfoot td { font-weight: bold; }
    .rm-total { font-size: 14.5px; background: #fbeedd; }
    .rm-pago td { font-size: 10.5px; padding: 6px 5px; }
    .rm-check { display: inline-block; width: 12px; height: 12px; line-height: 12px; border: 1px solid #162342; text-align: center; font-weight: bold; margin-left: 2px; }
    .rm-obs td { height: 46px; vertical-align: top; }
    .rm-firmas { margin-top: 30px; }
    .rm-firmas td { width: 46%; text-align: center; vertical-align: top; font-size: 11.5px; }
    .rm-firmas .rm-sep { width: 8%; }
    .rm-linea { border-top: 1px solid #162342; margin-bottom: 3px; }
    .rm-pie { margin-top: 10px; text-align: center; font-style: italic; font-size: 10.5px; color: #33405f; }
</style>
