@props(['accion' => null, 'variante' => 'primario', 'href' => null])
@php
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-pildora px-6 py-3 text-[15px] font-medium whitespace-nowrap transition-colors focus-visible:shadow-foco focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40',
        'bg-tinta text-white hover:bg-tinta-hover' => $variante === 'primario',
        'border border-borde bg-tarjeta text-tinta hover:bg-hover-suave' => $variante === 'secundario',
        'bg-transparent px-0 py-0 text-enlace hover:text-enlace-hover' => $variante === 'enlace',
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($clases) }}
        @if ($accion) wire:loading.attr="disabled" wire:target="{{ $accion }}" @endif>
        {{ $slot }}
    </button>
@endif
