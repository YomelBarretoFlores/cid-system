@props(['titulo', 'descripcion' => null])
<div class="flex flex-col gap-[22px]">
    <header class="flex flex-col gap-2">
        <h1 class="text-[22px] leading-[1.2] font-semibold tracking-[-0.02em]">{{ $titulo }}</h1>
        @if ($descripcion)
            <p class="text-pretty text-secundario">{{ $descripcion }}</p>
        @endif
    </header>

    {{ $slot }}

    @isset($pie)
        <div class="flex flex-wrap justify-center gap-1.5 border-t border-separador pt-4 text-center text-[14.5px] text-secundario">{{ $pie }}</div>
    @endisset
</div>
