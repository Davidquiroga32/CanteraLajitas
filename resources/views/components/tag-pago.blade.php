@props(['forma'])

@php
    $clase = match ($forma) {
        \App\Enums\FormaPago::Efectivo => 'v',
        \App\Enums\FormaPago::Transferencia => '',
        \App\Enums\FormaPago::Credito => 'c',
        \App\Enums\FormaPago::Anticipo => 'o',
        \App\Enums\FormaPago::Donacion => 'g',
    };
@endphp

<span {{ $attributes->merge(['class' => trim('tag '.$clase)]) }}>{{ $forma->label() }}</span>
