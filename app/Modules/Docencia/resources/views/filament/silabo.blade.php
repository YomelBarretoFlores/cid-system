@use('App\Shared\Ui\Formato')
@php($contenido = $silabo->contenido)
<div class="flex flex-col gap-6 text-sm">
    @unless ($silabo->estaPublicado())
        <x-filament::callout color="warning" icon="heroicon-o-pencil-square" heading="Borrador: los estudiantes todavía no lo ven." />
    @endunless

    <section aria-labelledby="silabo-identificacion" class="flex flex-col gap-3">
        <h3 id="silabo-identificacion" class="text-base font-semibold">I. Identificación</h3>
        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($silabo->identificacion as $etiqueta => $valor)
                <div><dt class="text-apagado">{{ $etiqueta }}</dt><dd>{{ $valor }}</dd></div>
            @endforeach
            @if ($contenido->publicadoEn)
                <div><dt class="text-apagado">Publicado el</dt><dd>{{ $contenido->publicadoEn->timezone('America/Lima')->format('d/m/Y H:i') }}</dd></div>
            @endif
        </dl>
    </section>

    <section aria-labelledby="silabo-sumilla" class="flex flex-col gap-3">
        <h3 id="silabo-sumilla" class="text-base font-semibold">II. Sumilla, competencias y capacidades</h3>
        <dl class="flex flex-col gap-3">
            @foreach (['Sumilla' => $contenido->sumilla, 'Competencias' => $contenido->competencias, 'Capacidades' => $contenido->capacidades] as $etiqueta => $texto)
                <div><dt class="text-apagado">{{ $etiqueta }}</dt><dd class="whitespace-pre-line">{{ $texto ?? '—' }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section aria-labelledby="silabo-semanas" class="flex flex-col gap-3">
        <h3 id="silabo-semanas" class="text-base font-semibold">III. Programación semanal</h3>
        <div class="overflow-x-auto">
            <table class="tabla-reporte min-w-[880px]">
                <thead>
                    <tr><th scope="col">Semana</th><th scope="col">Fechas</th><th scope="col">Unidad</th><th scope="col">Saber</th><th scope="col">Saber hacer</th><th scope="col">Saber ser</th><th scope="col">Actividades</th><th scope="col">Recursos</th></tr>
                </thead>
                <tbody>
                    @foreach ($contenido->semanas as $semana)
                        <tr wire:key="silabo-semana-{{ $semana->numero }}" class="align-top">
                            <td>{{ $semana->numero }}</td>
                            <td class="whitespace-nowrap">{{ Formato::rango($semana->desde, $semana->hasta) }}</td>
                            <td>{{ $semana->unidad ?? '—' }}</td>
                            <td>{{ $semana->saber ?? '—' }}</td>
                            <td>{{ $semana->saberHacer ?? '—' }}</td>
                            <td>{{ $semana->saberSer ?? '—' }}</td>
                            <td>{{ $semana->actividades ?? '—' }}</td>
                            <td>{{ $semana->recursos ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section aria-labelledby="silabo-evaluacion" class="flex flex-col gap-3">
        <h3 id="silabo-evaluacion" class="text-base font-semibold">IV. Evaluación</h3>
        <div class="overflow-x-auto">
            <table class="tabla-reporte min-w-[640px]">
                <thead>
                    <tr><th scope="col">Componente</th><th scope="col" class="text-right">Peso</th><th scope="col">Indicadores</th><th scope="col">Instrumento</th><th scope="col">Semana</th></tr>
                </thead>
                <tbody>
                    @forelse ($contenido->evaluaciones as $evaluacion)
                        <tr wire:key="silabo-evaluacion-{{ $evaluacion->componenteCodigo }}" class="align-top">
                            <td>{{ $evaluacion->componente }}</td>
                            <td class="text-right">{{ Formato::nota($evaluacion->peso) }} %</td>
                            <td>{{ $evaluacion->indicadores ?? '—' }}</td>
                            <td>{{ $evaluacion->instrumento ?? '—' }}</td>
                            <td>{{ $evaluacion->semana ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">El grupo no tiene un sistema de evaluación vigente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section aria-labelledby="silabo-metodologia" class="flex flex-col gap-3">
        <h3 id="silabo-metodologia" class="text-base font-semibold">V. Metodología y bibliografía</h3>
        <dl class="flex flex-col gap-3">
            <div><dt class="text-apagado">Metodología</dt><dd class="whitespace-pre-line">{{ $contenido->metodologia ?? '—' }}</dd></div>
            <div><dt class="text-apagado">Bibliografía</dt><dd class="whitespace-pre-line">{{ $contenido->bibliografia ?? '—' }}</dd></div>
        </dl>
    </section>
</div>
