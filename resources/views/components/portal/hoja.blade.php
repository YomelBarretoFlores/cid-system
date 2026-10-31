@props(['titulo', 'periodo' => null])
{{-- A formal document sheet, like the SGA reports: plain title with the period in grey, thin rule, tables with thin borders. --}}
<section {{ $attributes->class('flex min-w-0 flex-col gap-5 border border-piedra-300 bg-tarjeta p-4 text-[13px] text-tinta sm:p-7') }}>
    <header class="flex flex-wrap items-end justify-between gap-3 border-b border-piedra-300 pb-3">
        <h2 class="text-[22px] leading-tight font-normal">
            {{ $titulo }}
            @if ($periodo)
                <span class="text-[16px] text-apagado">{{ $periodo }}</span>
            @endif
        </h2>
        @isset($acciones)
            <div class="flex flex-wrap gap-2">{{ $acciones }}</div>
        @endisset
    </header>

    {{ $slot }}
</section>
