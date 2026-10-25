@props(['accion' => null, 'variante' => 'primario', 'tamano' => 'normal', 'href' => null])
@php
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-pildora text-[15px] font-medium whitespace-nowrap transition-colors focus-visible:shadow-foco focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40',
        'bg-tinta text-white hover:bg-tinta-hover' => $variante === 'primario',
        'border border-borde bg-tarjeta text-tinta hover:bg-hover-suave' => $variante === 'secundario',
        'text-enlace hover:text-enlace-hover' => $variante === 'enlace',
        'px-6 py-[15px]' => $variante !== 'enlace' && $tamano === 'grande',
        'px-6 py-3' => $variante !== 'enlace' && $tamano === 'normal',
        'px-4 py-2.5' => $variante !== 'enlace' && $tamano === 'compacto',
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
