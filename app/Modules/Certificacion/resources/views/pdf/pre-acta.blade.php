@use('App\Shared\Ui\Formato')
@php
    $columnas = $acta->columnas();
    $notasDeCiclo = array_values(array_filter($acta->componentes, fn ($componente): bool => $componente->ciclo !== null && ! $componente->esIngresada()));
    usort($notasDeCiclo, fn ($una, $otra): int => $una->ciclo <=> $otra->ciclo);
    $notaDelCiclo = $notasDeCiclo[0] ?? null;
    $notaDelSegundo = $notasDeCiclo[1] ?? null;
    $siglaDelSegundo = $notaDelSegundo === null ? ($notaDelCiclo === null ? 'PF' : $siglas[$notaDelCiclo->codigo]).' '.Formato::romano((int) $acta->cicloNumeroHasta) : $siglas[$notaDelSegundo->codigo];
    $enLeyenda = array_values(array_filter([...$columnas, ...$notasDeCiclo]));
    $leyenda = array_chunk($enLeyenda, max(1, (int) ceil(count($enLeyenda) / 3)));
    $totalDeColumnas = count($columnas) + ($acta->esAcelerada() ? 7 : 5);
@endphp
<x-pdf.registro titulo="Registro de evaluación" :subtitulo="$acta->periodo">
    <table class="datos">
        <tr>
            <td>Nombre del docente: {{ mb_strtoupper($acta->docente) }}</td>
            <td style="text-align: center;">Número de alumnos: {{ count($acta->filas) }}</td>
            <td style="text-align: right;">Fecha: {{ $generadaEl }}</td>
        </tr>
    </table>

    <table class="cabecera">
        <tr>
            <th>Idioma</th><th>Modalidad</th><th>Currícula</th><th>Nivel</th><th>Ciclo</th><th>Grupo</th><th>Turno</th>
        </tr>
        <tr>
            <td>{{ $acta->idioma }}</td>
            <td>{{ $grupo?->programa ?? '—' }}</td>
            <td>{{ $grupo?->curriculo ?? '—' }}</td>
            <td>{{ $acta->nivel->etiqueta() }}</td>
            <td>{{ Formato::romano($acta->cicloNumero) }}{{ $acta->cicloNumeroHasta === null ? '' : ' y '.Formato::romano($acta->cicloNumeroHasta) }}</td>
            <td>{{ $acta->grupoCodigo }}</td>
            <td style="text-transform: none;">{{ $grupo === null || $grupo->horarioCompacto() === [] ? '—' : implode(' / ', $grupo->horarioCompacto()) }}</td>
        </tr>
    </table>

    <table class="registro">
        <thead>
            <tr>
                <th class="num">N.º</th>
                <th style="width: 10%;">Documento</th>
                <th>Apellidos y nombres</th>
                @foreach ($columnas as $componente)
                    <th class="num {{ $componente->esIngresada() ? '' : 'calculada' }}">{{ $siglas[$componente->codigo] }}</th>
                @endforeach
                <th class="num calculada">{{ $notaDelCiclo === null ? 'PF' : $siglas[$notaDelCiclo->codigo] }}</th>
                @if ($acta->esAcelerada())
                    <th class="num calculada">{{ $siglaDelSegundo }}</th>
                    <th style="width: 8%;">Estado {{ Formato::romano($acta->cicloNumero) }}</th>
                    <th style="width: 8%;">Estado {{ Formato::romano((int) $acta->cicloNumeroHasta) }}</th>
                @else
                    <th style="width: 9%;">Estado</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($acta->filas as $fila)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td style="text-align: center;">{{ $fila->documento }}</td>
                    <td>{{ mb_strtoupper($fila->estudiante) }}</td>
                    @foreach ($columnas as $componente)
                        <td class="num {{ $componente->esIngresada() ? '' : 'calculada' }}">{{ is_numeric($fila->celda($componente)) ? Formato::nota($fila->celda($componente)) : $fila->celda($componente) }}</td>
                    @endforeach
                    <td class="num calculada"><strong>{{ $fila->promedio === null ? '—' : Formato::nota($fila->promedio) }}</strong></td>
                    @if ($acta->esAcelerada())
                        <td class="num calculada"><strong>{{ $fila->promedioHasta === null ? '—' : Formato::nota($fila->promedioHasta) }}</strong></td>
                    @endif
                    <td style="text-align: center;">{{ $fila->estadoFinal->etiqueta() }}</td>
                    @if ($acta->esAcelerada())
                        <td style="text-align: center;">{{ $fila->estadoFinalHasta?->etiqueta() }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $totalDeColumnas }}" style="text-align: center;">El grupo no tiene estudiantes matriculados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="pie-de-registro">
        <tr>
            <td style="width: 48%;">
                <table class="leyenda">
                    <tr><th colspan="{{ count($leyenda) }}">SISTEMA DE CALIFICACIÓN</th></tr>
                    @for ($i = 0; $i < count($leyenda[0]); $i++)
                        <tr>
                            @foreach ($leyenda as $grupoDeColumnas)
                                <td>@isset($grupoDeColumnas[$i]){{ $siglas[$grupoDeColumnas[$i]->codigo] }} = {{ $grupoDeColumnas[$i]->nombre }}@endisset</td>
                            @endforeach
                        </tr>
                    @endfor
                </table>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 22%;">
                <div class="firma"></div>
                <div class="firma-nombre">Vº Bº DIRECTOR(A) ACADÉMICO</div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 22%;">
                <div class="firma"></div>
                <div class="firma-nombre">FIRMA DEL DOCENTE</div>
            </td>
        </tr>
    </table>

    <p class="nota">
        Documento de trabajo con las notas registradas a la fecha; el documento oficial es el acta final.
        Columnas sombreadas: calculadas por el sistema de evaluación «{{ $acta->esquema }}». Nota máxima {{ Formato::nota($acta->notaMaxima) }}, mínima aprobatoria {{ Formato::nota($acta->notaMinimaAprobatoria) }}; una nota en blanco cuenta como cero.
    </p>
</x-pdf.registro>
