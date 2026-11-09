@props(['ruta', 'icono', 'activo' => null, 'insignia' => null, 'navegar' => true])
@php($esActivo = $navegar && request()->routeIs($activo ?? $ruta))
<a href="{{ route($ruta) }}" {{ $navegar ? 'wire:navigate.hover' : '' }} x-bind:title="colapsado ? @js(trim(strip_tags((string) $slot))) : null"
    @if ($esActivo) aria-current="page" @endif
    @class([
        'relative flex items-center gap-3.5 rounded-xl px-2.5 py-2 text-[15px] text-tinta focus-visible:shadow-foco focus-visible:outline-none lg:in-data-colapsado:justify-center',
        'bg-acento-claro font-semibold' => $esActivo,
        'hover:bg-hover-suave' => ! $esActivo,
    ])>
    <x-dynamic-component :component="$icono" aria-hidden="true" @class(['size-5 shrink-0', 'text-oro-500' => $esActivo, 'text-apagado' => ! $esActivo]) />
    <span class="flex-1 lg:in-data-colapsado:sr-only">{{ $slot }}</span>
    @if ($insignia)
        <span class="min-w-[22px] rounded-pildora lg:in-data-colapsado:absolute lg:in-data-colapsado:-top-0.5 lg:in-data-colapsado:right-0 bg-acento px-[7px] py-px text-center text-xs font-semibold text-oro-950">{{ $insignia }}</span>
    @endif
</a>
