<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn pri']) }}>
    {{ $slot }}
</button>
