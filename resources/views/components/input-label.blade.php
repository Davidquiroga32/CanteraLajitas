@props(['value'])

<label {{ $attributes->merge(['class' => 'lbl block']) }}>
    {{ $value ?? $slot }}
</label>
