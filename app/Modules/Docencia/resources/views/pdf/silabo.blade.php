@use('App\Shared\Ui\Formato')
@php($contenido = $silabo->contenido)
{{-- pre-line keeps the line breaks the teacher typed while the text stays escaped. --}}
<x-pdf.documento titulo="SÍLABO">
    @unless ($silabo->estaPublicado())
        <p style="text-align: center; color: #8d5406; font-weight: bold; margin: -8px 0 10px;">BORRADOR · aún no publicado para los estudiantes</p>
    @endunless

    <h2>I. Identificación</h2>
    <table class="datos">
        @foreach ($silabo->identificacion as $etiqueta => $valor)
            <tr><th>{{ $etiqueta }}</th><td>{{ $valor }}</td></tr>
        @endforeach
        @if ($contenido->publicadoEn)
            <tr><th>Publicado el</th><td>{{ $contenido->publicadoEn->timezone('America/Lima')->format('d/m/Y H:i') }}</td></tr>
        @endif
    </table>

    <h2>II. Sumilla, competencias y capacidades</h2>
    <table class="datos">
        <tr><th>Sumilla</th><td style="white-space: pre-line;">{{ $contenido->sumilla ?? '—' }}</td></tr>
        <tr><th>Competencias</th><td style="white-space: pre-line;">{{ $contenido->competencias ?? '—' }}</td></tr>
        <tr><th>Capacidades</th><td style="white-space: pre-line;">{{ $contenido->capacidades ?? '—' }}</td></tr>
    </table>

    <h2>III. Programación semanal</h2>
    <table class="lista">
        <thead>
            <tr><th>Semana</th><th>Fechas</th><th>Unidad</th><th>Saber</th><th>Saber hacer</th><th>Saber ser</th><th>Actividades</th><th>Recursos</th></tr>
        </thead>
        <tbody>
            @foreach ($contenido->semanas as $semana)
                <tr>
                    <td>{{ $semana->numero }}</td>
                    <td style="white-space: nowrap;">{{ Formato::rango($semana->desde, $semana->hasta) }}</td>
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

    <h2>IV. Evaluación</h2>
    <table class="lista">
        <thead>
            <tr><th>Componente</th><th class="num">Peso (%)</th><th>Indicadores</th><th>Instrumento</th><th>Semana</th></tr>
        </thead>
        <tbody>
            @forelse ($contenido->evaluaciones as $evaluacion)
                <tr>
                    <td>{{ $evaluacion->componente }}</td>
                    <td class="num">{{ Formato::nota($evaluacion->peso) }}</td>
                    <td>{{ $evaluacion->indicadores ?? '—' }}</td>
                    <td>{{ $evaluacion->instrumento ?? '—' }}</td>
                    <td>{{ $evaluacion->semana ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">El grupo no tiene un sistema de evaluación vigente.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>V. Metodología</h2>
    <p style="white-space: pre-line;">{{ $contenido->metodologia ?? '—' }}</p>

    <h2>Bibliografía</h2>
    <p style="white-space: pre-line;">{{ $contenido->bibliografia ?? '—' }}</p>
</x-pdf.documento>
