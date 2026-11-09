@props(['titulo', 'antetitulo' => null, 'saludo' => false, 'volver' => null, 'migas' => null])
@php
    // SGA-style trail: Alumno › student name › page, unless the page names its own steps.
    $nombre = mb_strtoupper((string) auth()->user()?->name);
    $rastro = $migas ?? [['Alumno', null], [$nombre, route('estudiante.inicio')], [$titulo, null]];
@endphp
<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-6 gap-y-3 px-1 pt-1 pb-1') }}>
    <div class="flex min-w-0 flex-col gap-1">
        <nav aria-label="Ruta de navegación">
            <ol class="flex flex-wrap items-center gap-x-1.5 text-[13px] text-apagado">
                @foreach ($rastro as $indice => [$texto, $enlace])
                    <li class="flex items-center gap-1.5">
                        @if ($indice > 0)
                            <x-heroicon-o-chevron-right aria-hidden="true" class="size-3.5" />
                        @endif
                        @if ($enlace)
                            <a href="{{ $enlace }}" wire:navigate class="font-semibold text-secundario hover:text-enlace-hover">{{ $texto }}</a>
                        @else
                            <span @if ($loop->last) aria-current="page" @else class="font-semibold text-secundario" @endif>{{ $texto }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
        @if ($antetitulo)
            <span class="text-apagado">{{ $antetitulo }}</span>
        @endif
        <h1 class="text-[clamp(24px,2.4vw,30px)] leading-tight font-semibold tracking-[-0.01em]">{{ $titulo }}</h1>
        @if ($slot->isNotEmpty())
            <div class="max-w-[760px] text-pretty text-secundario">{{ $slot }}</div>
        @endif
    </div>

    @isset($acciones)
        <div class="flex flex-wrap items-end gap-2">{{ $acciones }}</div>
    @endisset
</header>
