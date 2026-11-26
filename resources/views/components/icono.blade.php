@props(['nombre'])

@php
    $trazos = [
        'inicio' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'mas' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'lista' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
        'usuarios' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 010 7M18 14.5c2.2.6 3.5 2.6 3.5 5.5"/>',
        'capas' => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/><path d="M3 17.5l9 5 9-5" opacity=".5"/>',
        'cartera' => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M16 15h2"/><path d="M6 6l9-3 2 3"/>',
        'carnet' => '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="10" r="2.5"/><path d="M8 17c.5-2 2-3 4-3s3.5 1 4 3"/>',
        'grafico' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'cerrar' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'ojo' => '<path d="M2.06 12.35a1 1 0 0 1 0-.7C3.42 8.36 7.4 5 12 5s8.58 3.36 9.94 6.65a1 1 0 0 1 0 .7C20.58 15.64 16.6 19 12 19s-8.58-3.36-9.94-6.65z"/><circle cx="12" cy="12" r="3"/>',
        'ojo-cerrado' => '<path d="M10.73 5.08A10.4 10.4 0 0 1 12 5c4.6 0 8.58 3.36 9.94 6.65a1 1 0 0 1 0 .7 11.8 11.8 0 0 1-1.67 2.88"/><path d="M14.08 14.16a3 3 0 0 1-4.24-4.24"/><path d="M17.48 17.5A10.8 10.8 0 0 1 12 19c-4.6 0-8.58-3.36-9.94-6.65a1 1 0 0 1 0-.7 11.9 11.9 0 0 1 4.46-5.15"/><path d="M2 2l20 20"/>',
    ];
@endphp

<svg viewBox="0 0 24 24" aria-hidden="true" {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.6', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>{!! $trazos[$nombre] ?? '' !!}</svg>
