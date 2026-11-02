@use('App\Modules\Academico\Data\SesionDeClase')
@props(['dia', 'numero', 'clases' => [], 'avisos' => []])
<li {{ $attributes->class('flex items-center gap-4 rounded-interna px-3 py-2.5') }}>
    <span class="flex w-9 flex-col items-center leading-tight">
        <span class="text-[11px] tracking-[0.08em] text-apagado uppercase">{{ SesionDeClase::DIAS_CORTOS[$dia] }}</span>
        <span class="text-[18px] font-semibold">{{ $numero }}</span>
    </span>
    <span class="flex flex-col text-[14.5px]">
        @foreach ($clases as $clase)
            <span>{{ $clase->idioma }} · {{ $clase->hora }} · {{ $clase->aula ?? 'Virtual' }}</span>
        @endforeach
        @foreach ($avisos as $aviso)
            <span class="font-medium text-ambar-texto">{{ $aviso }}</span>
        @endforeach
        @if ($clases === [] && $avisos === [])
            <span class="text-apagado">Sin clases</span>
        @endif
    </span>
</li>
