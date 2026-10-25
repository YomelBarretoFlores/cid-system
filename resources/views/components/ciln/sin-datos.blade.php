<div {{ $attributes->class('flex items-center gap-3 rounded-interna bg-tarjeta-interna px-4 py-5 text-[14.5px] text-apagado shadow-[inset_0_0_0_1px_var(--color-borde-interno)]') }}>
    <x-heroicon-o-inbox aria-hidden="true" class="size-5 shrink-0 text-oro-500" />
    <span>{{ $slot }}</span>
</div>
