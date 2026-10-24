@props(['ruta'])
@php($activo = request()->routeIs($ruta))
<a href="{{ route($ruta) }}"
    @if ($activo) aria-current="page" @endif
    @class([
        'rounded-md px-3 py-2 focus-visible:outline-2 focus-visible:outline-white',
        'bg-ciln-900 font-medium text-white' => $activo,
        'text-ciln-100 hover:bg-ciln-700 hover:text-white' => ! $activo,
    ])>{{ $slot }}</a>
