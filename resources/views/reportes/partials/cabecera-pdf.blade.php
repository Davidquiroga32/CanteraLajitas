{{-- Cabecera de los listados en PDF. Variables: $titulo, $subtitulo. --}}
<table class="cab">
    <tr>
        <td style="width: 64px"><img src="{{ public_path('images/logo-cantera-lajitas.png') }}" alt="" width="56"></td>
        <td>
            <div class="t">{{ $titulo }}</div>
            <div class="s">{{ $subtitulo }}</div>
        </td>
        <td class="e">
            CANTERA LAJITAS S.A.S.<br>
            Generado el {{ now()->format('d/m/Y h:i a') }}<br>
            por {{ auth()->user()?->name }}
        </td>
    </tr>
</table>
