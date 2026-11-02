@props(['resaltado', 'despues' => '', 'etiqueta' => null, 'nivel' => 'h2', 'centrado' => false])
<div {{ $attributes->class(['flex flex-col gap-3', 'items-center text-center' => $centrado]) }}>
    @if ($etiqueta)
        <span @class(['flex items-center gap-2 rounded-pildora border border-marino-900/8 bg-white px-3 py-1 text-[13.5px] font-medium text-marino-800', 'self-center' => $centrado, 'self-start' => ! $centrado])>
            <span aria-hidden="true" class="size-1.5 rounded-full bg-celeste-500"></span>{{ $etiqueta }}
        </span>
    @endif
    <{{ $nivel }} class="text-[clamp(32px,4.2vw,56px)] leading-[1.02] font-bold tracking-[-0.04em] text-balance text-marino-900">
        {{ $slot }}
        <span class="resaltado relative inline-block whitespace-nowrap text-celeste-500">{{ $resaltado }}<svg aria-hidden="true" viewBox="0 0 200 14" preserveAspectRatio="none" class="absolute -bottom-[0.14em] left-0 h-[0.2em] w-full overflow-visible text-celeste-300"><path d="M3 9.5C42 4.5 101 2.5 197 6.5" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" pathLength="1" class="trazo-a-mano" /><path d="M22 12.5C71 9 128 8.6 178 10.6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" pathLength="1" class="trazo-a-mano" style="--orden: 1" /></svg></span>{{ $despues }}
    </{{ $nivel }}>
</div>
