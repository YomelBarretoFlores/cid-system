@use('App\Shared\Ui\Formato')
@php($contenido = $silabo->contenido)
{{-- One body for the PDF, the student page and the coordination view, so the three read the same. The classes are plain CSS in the PDF and Tailwind utilities on the page. --}}
<h2 class="titulo-formal">I. Datos generales</h2>
<table class="tabla-formal">
    <tbody>
        @foreach ($silabo->identificacion as $etiqueta => $valor)
            <tr><td class="etiqueta">1.{{ $loop->iteration }}. {{ $etiqueta }}</td><td>{{ $valor }}</td></tr>
        @endforeach
        @if ($contenido->publicadoEn)
            <tr><td class="etiqueta">1.{{ count($silabo->identificacion) + 1 }}. Publicado el</td><td>{{ $contenido->publicadoEn->timezone('America/Lima')->format('d/m/Y H:i') }}</td></tr>
        @endif
    </tbody>
</table>

@foreach (['II. Sumilla' => $contenido->sumilla, 'III. Competencia' => $contenido->competencias, 'IV. Capacidades' => $contenido->capacidades] as $titulo => $texto)
    <h2 class="titulo-formal">{{ $titulo }}</h2>
    <p class="texto whitespace-pre-line">{{ $texto ?? '—' }}</p>
@endforeach

<h2 class="titulo-formal">V. Programación de contenidos</h2>
<div class="overflow-x-auto">
    <table class="tabla-formal min-w-[760px]">
        <thead>
            <tr><th class="centro">Sem.</th><th>Fechas</th><th>Unidad</th><th>Saber</th><th>Saber hacer</th><th>Saber ser</th><th>Actividades</th><th>Recursos</th></tr>
        </thead>
        <tbody>
            @foreach ($contenido->semanas as $semana)
                <tr>
                    <td class="centro">{{ $semana->numero }}</td>
                    <td class="whitespace-nowrap" style="white-space: nowrap;">{{ Formato::rango($semana->desde, $semana->hasta) }}</td>
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

<h2 class="titulo-formal">VI. Estrategias metodológicas</h2>
<p class="texto whitespace-pre-line">{{ $contenido->metodologia ?? '—' }}</p>

<h2 class="titulo-formal">VII. Evaluación</h2>
<div class="overflow-x-auto">
    <table class="tabla-formal min-w-[640px]">
        <thead>
            <tr><th>Componente</th><th class="centro">Peso (%)</th><th>Indicadores</th><th>Instrumento</th><th class="centro">Semana</th></tr>
        </thead>
        <tbody>
            @forelse ($contenido->evaluaciones as $evaluacion)
                <tr>
                    <td>{{ $evaluacion->componente }}</td>
                    <td class="centro">{{ Formato::nota($evaluacion->peso) }}</td>
                    <td>{{ $evaluacion->indicadores ?? '—' }}</td>
                    <td>{{ $evaluacion->instrumento ?? '—' }}</td>
                    <td class="centro">{{ $evaluacion->semana ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">El grupo no tiene un sistema de evaluación vigente.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="titulo-formal">VIII. Referencias bibliográficas</h2>
<div>
    @forelse (array_values(array_filter(array_map(trim(...), explode("\n", (string) $contenido->bibliografia)))) as $referencia)
        <p class="texto mb-1">{{ $loop->iteration }}. {{ $referencia }}</p>
    @empty
        <p class="texto">—</p>
    @endforelse
</div>
