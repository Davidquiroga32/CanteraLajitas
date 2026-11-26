@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'aviso text-sm font-medium']) }} role="status">
        {{ $status }}
    </div>
@endif
