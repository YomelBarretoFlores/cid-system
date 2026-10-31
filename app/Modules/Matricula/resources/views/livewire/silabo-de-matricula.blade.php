@use('App\Shared\Ui\Formato')
@php($contenido = $silabo->contenido)
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Sílabo" :volver="route('estudiante.matriculas')">
        {{ $silabo->curso }} · Grupo {{ $silabo->grupo }}
        <x-slot:acciones>
            <x-portal.boton variante="secundario" :href="route('estudiante.matriculas.silabo.pdf', ['matricula' => $this->matricula])" target="_blank" rel="noopener" tamano="compacto">
                <x-heroicon-o-document-arrow-down aria-hidden="true" class="size-4" />
                Descargar PDF
            </x-portal.boton>
        </x-slot:acciones>
    </x-portal.encabezado>

    <x-portal.tarjeta titulo="I. Identificación">
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
            @foreach ($silabo->identificacion as $etiqueta => $valor)
                <x-portal.dato :etiqueta="$etiqueta">{{ $valor }}</x-portal.dato>
            @endforeach
        </dl>
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="II. Sumilla, competencias y capacidades">
        <dl class="flex flex-col gap-4">
            @foreach (['Sumilla' => $contenido->sumilla, 'Competencias' => $contenido->competencias, 'Capacidades' => $contenido->capacidades] as $etiqueta => $texto)
                <x-portal.dato :etiqueta="$etiqueta"><span class="whitespace-pre-line">{{ $texto }}</span></x-portal.dato>
            @endforeach
        </dl>
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="III. Programación semanal">
        <ol role="list" class="flex flex-col gap-3">
            @foreach ($contenido->semanas as $semana)
                <li wire:key="semana-{{ $semana->numero }}" class="tarjeta-interna flex flex-col gap-3 p-4">
                    <h3 class="flex flex-wrap items-baseline gap-x-2 font-semibold">
                        Semana {{ $semana->numero }}
                        <span class="text-[13.5px] font-normal text-apagado">{{ Formato::rango($semana->desde, $semana->hasta) }}</span>
                    </h3>
                    <dl class="grid gap-3 sm:grid-cols-2">
                        @foreach (['Unidad' => $semana->unidad, 'Saber' => $semana->saber, 'Saber hacer' => $semana->saberHacer, 'Saber ser' => $semana->saberSer, 'Actividades' => $semana->actividades, 'Recursos' => $semana->recursos] as $etiqueta => $texto)
                            @if ($texto !== null)
                                <x-portal.dato :etiqueta="$etiqueta"><span class="whitespace-pre-line">{{ $texto }}</span></x-portal.dato>
                            @endif
                        @endforeach
                    </dl>
                </li>
            @endforeach
        </ol>
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="IV. Evaluación">
        <ul role="list" class="flex flex-col gap-3">
            @foreach ($contenido->evaluaciones as $evaluacion)
                <li wire:key="evaluacion-{{ $evaluacion->componenteCodigo }}" class="tarjeta-interna flex flex-col gap-3 p-4">
                    <h3 class="flex flex-wrap items-baseline gap-x-2 font-semibold">
                        {{ $evaluacion->componente }}
                        <span class="text-[13.5px] font-normal text-apagado">{{ Formato::nota($evaluacion->peso) }} %</span>
                    </h3>
                    <dl class="grid gap-3 sm:grid-cols-[2fr_2fr_1fr]">
                        <x-portal.dato etiqueta="Indicadores"><span class="whitespace-pre-line">{{ $evaluacion->indicadores }}</span></x-portal.dato>
                        <x-portal.dato etiqueta="Instrumento">{{ $evaluacion->instrumento }}</x-portal.dato>
                        <x-portal.dato etiqueta="Semana">{{ $evaluacion->semana ?? 'Por definir' }}</x-portal.dato>
                    </dl>
                </li>
            @endforeach
        </ul>
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="V. Metodología y bibliografía">
        <dl class="flex flex-col gap-4">
            <x-portal.dato etiqueta="Metodología"><span class="whitespace-pre-line">{{ $contenido->metodologia }}</span></x-portal.dato>
            <x-portal.dato etiqueta="Bibliografía"><span class="whitespace-pre-line">{{ $contenido->bibliografia }}</span></x-portal.dato>
        </dl>
    </x-portal.tarjeta>
</div>
