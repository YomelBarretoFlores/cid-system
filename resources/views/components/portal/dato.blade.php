@props(['etiqueta'])
<div {{ $attributes->class('flex flex-col') }}>
    <dt class="text-[13px] text-apagado">{{ $etiqueta }}</dt>
    <dd>{{ $slot }}</dd>
</div>
