@props(['resaltado', 'despues' => '', 'etiqueta' => null, 'nivel' => 'h2', 'centrado' => false])
<div {{ $attributes->class(['flex flex-col gap-3', 'items-center text-center' => $centrado]) }}>
    @if ($etiqueta)
        <span @class(['flex items-center gap-2 rounded-pildora border border-marino-900/8 bg-white px-3 py-1 text-[13.5px] font-medium text-marino-800', 'self-center' => $centrado, 'self-start' => ! $centrado])>
            <span aria-hidden="true" class="size-1.5 rounded-full bg-celeste-500"></span>{{ $etiqueta }}
        </span>
    @endif
    <{{ $nivel }} class="text-[clamp(32px,4.2vw,56px)] leading-[1.02] font-bold tracking-[-0.04em] text-balance text-marino-900">
        {{ $slot }}
        <span class="resaltado relative inline-block whitespace-nowrap text-celeste-500">{{ $resaltado }}<x-publico.subrayado dibujado class="-bottom-[0.14em] h-[0.2em]" /></span>{{ $despues }}
    </{{ $nivel }}>
</div>
