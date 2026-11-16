@props(['resaltado' => '', 'despues' => '', 'etiqueta' => null, 'nivel' => 'h2', 'centrado' => false, 'claro' => false])
<div {{ $attributes->class(['flex flex-col gap-4', 'items-center text-center' => $centrado]) }}>
    @if ($etiqueta)
        <span @class(['flex items-center gap-2 text-[13.5px] font-medium', 'text-celeste-200' => $claro, 'text-marino-800/80' => ! $claro])>
            <span aria-hidden="true" @class(['size-1.5', 'bg-celeste-300' => $claro, 'bg-real-600' => ! $claro])></span>
            {{ $etiqueta }}
        </span>
    @endif
    <{{ $nivel }} @class(['font-display text-[clamp(30px,3.6vw,46px)] leading-[1.08] font-semibold tracking-[-0.025em] text-balance', 'text-white' => $claro, 'text-marino-900' => ! $claro])>{{ $slot }} {{ $resaltado }}{{ $despues }}</{{ $nivel }}>
</div>
