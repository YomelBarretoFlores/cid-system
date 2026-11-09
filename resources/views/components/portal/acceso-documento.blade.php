@props(['titulo', 'detalle', 'href' => null, 'color' => 'celeste', 'icono' => 'heroicon-o-document-text', 'pdf' => false, 'nuevaPestana' => false, 'motivo' => null])
@php
    $circulo = match ($color) {
        'verde' => 'bg-hoja-verde',
        'lila' => 'bg-hoja-lila',
        'rojo' => 'bg-hoja-rojo',
        'ambar' => 'bg-hoja-ambar',
        default => 'bg-hoja-celeste',
    };
    $disponible = $href !== null && $motivo === null;
@endphp
<li>
    @if ($disponible)
        <a href="{{ $href }}" @if ($pdf || $nuevaPestana) target="_blank" rel="noopener" @else wire:navigate @endif
            class="group flex items-center gap-3.5 rounded-xl p-2 -mx-2 hover:bg-hover-suave focus-visible:shadow-foco focus-visible:outline-none">
    @else
        <span class="flex items-center gap-3.5 p-2 -mx-2 opacity-60" title="{{ $motivo }}">
    @endif
        <span aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $circulo }}">
            <x-dynamic-component :component="$icono" class="size-5 text-marino-700" />
        </span>
        <span class="flex min-w-0 flex-col items-start gap-1">
            <span class="text-[15px] leading-tight font-semibold group-hover:text-enlace-hover">{{ $titulo }}@if ($pdf && $disponible)<span class="sr-only"> (PDF, se abre en otra pestaña)</span>@endif</span>
            <span class="rounded-pildora bg-gris-fondo px-2 py-px text-[12px] text-gris-texto">{{ $motivo ?? $detalle }}</span>
        </span>
    @if ($disponible)
        </a>
    @else
        </span>
    @endif
</li>
