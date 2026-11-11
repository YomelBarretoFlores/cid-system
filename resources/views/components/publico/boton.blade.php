@props(['href' => null, 'variante' => 'primario', 'tamano' => 'normal'])
@php
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-[3px] border font-semibold whitespace-nowrap transition-colors duration-200',
        'border-real-600 bg-real-600 text-white hover:border-marino-900 hover:bg-marino-900' => $variante === 'primario',
        'border-marino-900 bg-marino-900 text-white hover:border-marino-800 hover:bg-marino-800' => $variante === 'oscuro',
        'border-marino-900/40 bg-white text-marino-900 hover:border-marino-900 hover:bg-celeste-50' => $variante === 'claro',
        'border-white/60 bg-transparent text-white hover:border-white hover:bg-white/10' => $variante === 'contorno',
        'px-6 py-3 text-[15px]' => $tamano === 'grande',
        'px-5 py-2.5 text-[14.5px]' => $tamano === 'normal',
        'px-3.5 py-2 text-[14px]' => $tamano === 'compacto',
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($clases) }}>{{ $slot }}</button>
@endif
