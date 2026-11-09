@props(['titulo', 'detalle', 'href' => null, 'color' => null, 'icono' => 'heroicon-o-document-text', 'pdf' => false, 'nuevaPestana' => false, 'motivo' => null])
@php($disponible = $href !== null && $motivo === null)
<li>
    @if ($disponible)
        <a href="{{ $href }}" @if ($pdf || $nuevaPestana) target="_blank" rel="noopener" @else wire:navigate @endif
            class="group -mx-2 flex items-center gap-3 rounded-control p-2 hover:bg-hover-suave focus-visible:shadow-foco focus-visible:outline-none">
    @else
        <span class="-mx-2 flex items-center gap-3 p-2 opacity-60" title="{{ $motivo }}">
    @endif
        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-acento-claro">
            <x-dynamic-component :component="$icono" class="size-5 text-acento" />
        </span>
        <span class="flex min-w-0 flex-col">
            <span class="text-[14.5px] leading-snug font-semibold group-hover:text-enlace-hover">{{ $titulo }}@if ($pdf && $disponible)<span class="sr-only"> (PDF, se abre en otra pestaña)</span>@endif</span>
            <span class="text-[13px] text-terciario">{{ $motivo ?? $detalle }}</span>
        </span>
    @if ($disponible)
        </a>
    @else
        </span>
    @endif
</li>
