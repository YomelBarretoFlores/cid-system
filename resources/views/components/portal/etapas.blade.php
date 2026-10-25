@props(['etapas', 'actual', 'observado' => false, 'finalizado' => false])
{{-- A segmented bar with one segment per stage: done, current (amber when observed) and pending. --}}
@php($total = count($etapas))
<div {{ $attributes->class('flex flex-col gap-2') }}>
    <p class="sr-only">Etapa {{ $actual + 1 }} de {{ $total }}: {{ $etapas[$actual]['nombre'] }}, {{ $etapas[$actual]['avance'] }} % de avance.</p>
    <ol aria-hidden="true" class="grid gap-1.5" style="grid-template-columns: repeat({{ $total }}, minmax(0, 1fr))">
        @foreach ($etapas as $indice => $etapa)
            @php($hecha = $finalizado || $indice < $actual)
            @php($esActual = ! $finalizado && $indice === $actual)
            <li class="flex min-w-0 flex-col gap-1.5">
                <span @class([
                    'h-2 rounded-pildora',
                    'bg-acento' => $hecha,
                    'bg-ambar-texto' => $esActual && $observado,
                    'bg-oro-200' => $esActual && ! $observado,
                    'bg-gris-fondo' => ! $hecha && ! $esActual,
                ])></span>
                <span @class([
                    'hidden truncate text-[12.5px] sm:block',
                    'font-semibold text-tinta' => $esActual,
                    'text-secundario' => $hecha,
                    'text-apagado' => ! $hecha && ! $esActual,
                ]) title="{{ $etapa['nombre'] }}">{{ $etapa['nombre'] }}</span>
            </li>
        @endforeach
    </ol>
</div>
