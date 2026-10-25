@props(['titulo', 'antetitulo' => null, 'saludo' => false])
<header {{ $attributes->class('relative flex flex-col gap-1.5 overflow-hidden rounded-tarjeta bg-tarjeta p-5 sm:px-[30px] sm:py-[26px]') }}>
    @if ($saludo)
        <x-ciln.emblema decorativo class="pointer-events-none absolute -top-[70px] -right-[60px] w-[320px] opacity-[0.07]" />
    @endif
    @if ($antetitulo)
        <span class="relative text-apagado">{{ $antetitulo }}</span>
    @endif
    <h1 @class([
        'relative font-semibold',
        'text-[clamp(30px,3.2vw,42px)] leading-[1.05] tracking-[-0.03em]' => $saludo,
        'text-titulo' => ! $saludo,
    ])>{{ $titulo }}</h1>
    @if ($slot->isNotEmpty())
        <div class="relative max-w-[680px] text-pretty text-secundario">{{ $slot }}</div>
    @endif
    @isset($acciones)
        <div class="relative mt-3 flex flex-wrap gap-2">{{ $acciones }}</div>
    @endisset
</header>
