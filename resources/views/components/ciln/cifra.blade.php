@props(['titulo', 'valor', 'detalle' => null, 'marca' => false, 'tono' => 'tinta'])
<div {{ $attributes->class('relative flex flex-col gap-1.5 overflow-hidden rounded-tarjeta bg-tarjeta px-7 py-[26px]') }}>
    @if ($marca)
        <x-ciln.emblema decorativo class="pointer-events-none absolute -right-[50px] -bottom-[50px] w-[190px] opacity-[0.07]" />
    @endif
    <span class="relative text-lg font-semibold text-tinta">{{ $titulo }}</span>
    <span @class([
        'relative font-medium',
        'text-kpi text-tinta' => $tono === 'tinta',
        'text-[clamp(22px,2vw,30px)] leading-tight tracking-[-0.03em] text-oro-800' => $tono === 'oro',
    ])>{{ $valor }}</span>
    @if ($detalle)
        <span @class(['relative', 'text-secundario' => $tono === 'tinta', 'font-medium text-oro-600' => $tono === 'oro'])>{{ $detalle }}</span>
    @endif
</div>
