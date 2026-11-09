@props(['titulo', 'antetitulo' => null, 'saludo' => false, 'volver' => null])
<header {{ $attributes->class(['relative flex flex-wrap items-center justify-between gap-4 rounded-tarjeta bg-tarjeta p-5 sm:px-[30px] sm:py-[26px]', 'overflow-hidden' => $saludo]) }}>
    @if ($saludo)
        <x-ciln.emblema decorativo class="pointer-events-none absolute -top-[70px] -right-[60px] w-[320px] opacity-[0.07]" />
    @endif

    <div class="relative flex min-w-0 items-center gap-3.5">
        @if ($volver)
            <a href="{{ $volver }}" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-lienzo text-tinta hover:bg-piedra-200 focus-visible:shadow-foco focus-visible:outline-none">
                <x-heroicon-o-arrow-left aria-hidden="true" class="size-4" /><span class="sr-only">Volver</span>
            </a>
        @endif
        {{ $inicio ?? '' }}
        <div class="flex min-w-0 flex-col gap-1.5">
            @if ($antetitulo)
                <span class="text-apagado">{{ $antetitulo }}</span>
            @endif
            <h1 @class([
                'font-semibold',
                'text-[clamp(30px,3.2vw,42px)] leading-[1.05] tracking-[-0.03em]' => $saludo,
                'text-titulo' => ! $saludo,
            ])>{{ $titulo }}</h1>
            @if ($slot->isNotEmpty())
                <div class="max-w-[680px] text-pretty text-secundario">{{ $slot }}</div>
            @endif
        </div>
    </div>

    @isset($acciones)
        <div class="relative flex flex-wrap gap-2">{{ $acciones }}</div>
    @endisset
</header>
