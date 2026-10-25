@props(['titulo' => null, 'descripcion' => null])
<section {{ $attributes->class('flex flex-col gap-4 rounded-tarjeta bg-tarjeta p-5 sm:px-7 sm:py-[26px]') }}>
    @if ($titulo || isset($acciones))
        <header class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <div class="flex flex-col gap-1">
                @if ($titulo)
                    <h2 class="text-[21px] font-semibold">{{ $titulo }}</h2>
                @endif
                @if ($descripcion)
                    <p class="text-secundario">{{ $descripcion }}</p>
                @endif
            </div>
            {{ $acciones ?? '' }}
        </header>
    @endif

    {{ $slot }}
</section>
