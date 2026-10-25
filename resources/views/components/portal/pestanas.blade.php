@props(['enlaces', 'etiqueta'])
<nav aria-label="{{ $etiqueta }}" {{ $attributes->class('flex rounded-pildora bg-lienzo p-1') }}>
    @foreach ($enlaces as [$url, $texto, $activo])
        <a href="{{ $url }}" @if ($activo) aria-current="page" @endif @class([
            'rounded-pildora px-5 py-2.5 font-medium focus-visible:shadow-foco focus-visible:outline-none',
            'bg-tinta text-white' => $activo,
            'text-secundario hover:text-tinta' => ! $activo,
        ])>{{ $texto }}</a>
    @endforeach
</nav>
