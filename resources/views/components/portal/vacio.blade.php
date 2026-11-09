@props(['titulo', 'icono' => 'heroicon-o-inbox'])
<div {{ $attributes->class('flex flex-col items-center gap-2 rounded-interna bg-tarjeta-interna px-6 py-10 text-center shadow-[inset_0_0_0_1px_var(--color-borde-interno)]') }}>
    <span class="mb-1 flex size-12 items-center justify-center rounded-full bg-acento-claro">
        <x-dynamic-component :component="$icono" aria-hidden="true" class="size-6 text-apagado" />
    </span>
    <p class="font-semibold">{{ $titulo }}</p>
    @if ($slot->isNotEmpty())
        <div class="max-w-md text-pretty text-[14.5px] text-terciario">{{ $slot }}</div>
    @endif
</div>
