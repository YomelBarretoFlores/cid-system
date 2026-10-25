@props(['ruta', 'icono', 'activo' => null, 'insignia' => null])
@php($esActivo = request()->routeIs($activo ?? $ruta))
<a href="{{ route($ruta) }}"
    @if ($esActivo) aria-current="page" @endif
    @class([
        'flex items-center gap-3.5 rounded-xl p-2.5 text-[15.5px] text-tinta focus-visible:shadow-foco focus-visible:outline-none',
        'bg-acento-claro font-semibold' => $esActivo,
        'hover:bg-hover-suave' => ! $esActivo,
    ])>
    <x-dynamic-component :component="$icono" aria-hidden="true" @class(['size-5 shrink-0', 'text-oro-500' => $esActivo, 'text-apagado' => ! $esActivo]) />
    <span class="flex-1">{{ $slot }}</span>
    @if ($insignia)
        <span class="min-w-[22px] rounded-pildora bg-acento px-[7px] py-px text-center text-xs font-semibold text-oro-950">{{ $insignia }}</span>
    @endif
</a>
