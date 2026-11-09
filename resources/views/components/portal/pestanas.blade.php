@props(['enlaces', 'etiqueta'])
<nav aria-label="{{ $etiqueta }}" {{ $attributes->class('flex max-w-full overflow-x-auto rounded-pildora bg-lienzo p-1') }}>
    @foreach ($enlaces as [$url, $texto, $activo])
        <a href="{{ $url }}" @if ($activo) aria-current="page" @endif @class([
            'rounded-pildora px-3.5 py-2.5 font-medium whitespace-nowrap focus-visible:shadow-foco focus-visible:outline-none sm:px-5',
            'bg-tinta text-invertido' => $activo,
            'text-secundario hover:text-tinta' => ! $activo,
        ])>{{ $texto }}</a>
    @endforeach
</nav>
