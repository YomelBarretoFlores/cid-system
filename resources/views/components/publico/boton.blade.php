@props(['href' => null, 'variante' => 'primario', 'tamano' => 'normal'])
@php
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-pildora font-semibold whitespace-nowrap transition-[translate,box-shadow,background-color] duration-200 ease-salida hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-celeste-300 motion-reduce:hover:translate-y-0',
        'bg-real-600 text-white shadow-[0_3px_0_var(--color-marino-900)] hover:bg-real-500' => $variante === 'primario',
        'bg-marino-900 text-white shadow-[0_3px_0_var(--color-marino-950)] hover:bg-marino-800' => $variante === 'oscuro',
        'border-[1.5px] border-marino-900 bg-white text-marino-900 shadow-[0_3px_0_var(--color-marino-900)] hover:bg-celeste-50' => $variante === 'claro',
        'px-6 py-3.5 text-[15.5px]' => $tamano === 'grande',
        'px-5 py-3 text-[15px]' => $tamano === 'normal',
        'px-4 py-2.5 text-[14.5px]' => $tamano === 'compacto',
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($clases) }}>{{ $slot }}</button>
@endif
