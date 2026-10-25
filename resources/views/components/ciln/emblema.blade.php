@props(['blanco' => false, 'decorativo' => false])
<img src="{{ asset($blanco ? 'images/ciln/emblema-blanco.webp' : 'images/ciln/emblema.webp') }}"
    alt="{{ $decorativo ? '' : 'Centro de Idiomas UNASAM' }}" width="720" height="529"
    @if ($decorativo) aria-hidden="true" @endif
    {{ $attributes->class('h-auto select-none') }}>
