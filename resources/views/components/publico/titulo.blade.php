@props(['resaltado' => '', 'despues' => '', 'etiqueta' => null, 'nivel' => 'h2', 'centrado' => false])
<div {{ $attributes->class(['flex flex-col gap-2.5', 'items-center text-center' => $centrado]) }}>
    @if ($etiqueta)
        <span class="text-[12.5px] font-semibold tracking-[0.14em] text-real-600 uppercase">{{ $etiqueta }}</span>
    @endif
    <{{ $nivel }} class="font-serif text-[clamp(28px,3.2vw,40px)] leading-[1.12] font-semibold text-balance text-marino-900">{{ $slot }} {{ $resaltado }}{{ $despues }}</{{ $nivel }}>
    <span aria-hidden="true" class="h-[3px] w-14 bg-real-600"></span>
</div>
