@props(['titulo', 'detalle' => null])
<li {{ $attributes->class('flex flex-col gap-1.5 rounded-control p-3.5 hover:bg-[#fbf8f1]') }}>
    <div class="flex items-center gap-3.5">
        @isset($inicio)
            <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-acento-claro">{{ $inicio }}</span>
        @endisset
        <span class="flex min-w-0 flex-1 flex-col">
            <span class="text-base font-medium">{{ $titulo }}</span>
            @if ($detalle)
                <span class="text-[13.5px] text-terciario">{{ $detalle }}</span>
            @endif
        </span>
        @isset($fin)
            <span class="flex shrink-0 flex-col items-end gap-1">{{ $fin }}</span>
        @endisset
    </div>
    @if ($slot->isNotEmpty())
        <div @class(['flex flex-col gap-1 text-[13.5px]', 'sm:pl-[58px]' => isset($inicio)])>{{ $slot }}</div>
    @endif
</li>
