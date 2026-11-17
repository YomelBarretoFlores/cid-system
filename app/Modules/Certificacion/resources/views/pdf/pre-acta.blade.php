@use('App\Modules\Academico\Enums\GrupoDeComponente')
@use('App\Shared\Ui\Formato')
<x-pdf.documento titulo="PRE ACTA DE EVALUACIÓN">
    <div style="position: fixed; top: 7cm; left: 0; right: 0; text-align: center; font-family: 'DejaVu Sans', sans-serif; font-size: 54pt; font-weight: bold; letter-spacing: 6px; color: #a3261c; opacity: 0.07;">SIN VALOR OFICIAL</div>
    <p style="text-align: center; margin: -6px 0 12px; font-size: 9pt; font-weight: bold; color: #a3261c; letter-spacing: 1px;">PRE ACTA — documento de trabajo, sin valor oficial</p>

    <h2>Datos del curso</h2>
    <table class="datos">
        <tr><th>Idioma</th><td>{{ $acta->idioma }}</td><th>Periodo académico</th><td>{{ $acta->periodo }}</td></tr>
        <tr><th>Nivel</th><td>{{ $acta->nivel->etiqueta() }}</td><th>Ciclo</th><td>{{ $acta->cicloNombre }} ({{ $acta->cicloNumero }})</td></tr>
        <tr><th>Grupo</th><td>{{ $acta->grupoCodigo }}</td><th>Sistema de evaluación</th><td>{{ $acta->esquema }}</td></tr>
        <tr><th>Docente</th><td>{{ $acta->docente }}</td><th>Generada el</th><td>{{ $generadaEl }}</td></tr>
    </table>

    <h2>Notas registradas</h2>
    <table class="lista">
        <thead>
            <tr>
                <th rowspan="2">N.°</th>
                <th rowspan="2">ID estudiante</th>
                <th rowspan="2">Apellidos y nombres</th>
                @foreach (GrupoDeComponente::cases() as $grupo)
                    <th class="centro" colspan="{{ count($acta->componentesDe($grupo)) + 1 }}">{{ $grupo->etiqueta() }}</th>
                @endforeach
                <th class="num" rowspan="2">Promedio a la fecha</th>
                <th rowspan="2">Estado a la fecha</th>
            </tr>
            <tr>
                @foreach (GrupoDeComponente::cases() as $grupo)
                    @foreach ($acta->componentesDe($grupo) as $componente)
                        <th class="num">{{ $componente->nombre }}</th>
                    @endforeach
                    <th class="num">Prom. {{ mb_strtolower($grupo->etiqueta()) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($acta->filas as $fila)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $fila->documento }}</td>
                    <td>{{ $fila->estudiante }}</td>
                    @foreach (GrupoDeComponente::cases() as $grupo)
                        @foreach ($acta->componentesDe($grupo) as $componente)
                            <td class="num">{{ Formato::nota($fila->notas[$componente->id] ?? null) }}</td>
                        @endforeach
                        <td class="num">{{ $fila->promedioDe($grupo) ?? '—' }}</td>
                    @endforeach
                    <td class="num"><strong>{{ $fila->promedio ?? '—' }}</strong></td>
                    <td>{{ $fila->estadoFinal->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 14px; font-size: 8.5pt; color: #5e5a50;">
        Nota máxima {{ Formato::nota($acta->notaMaxima) }} · nota mínima aprobatoria {{ Formato::nota($acta->notaMinimaAprobatoria) }}.
        El promedio es la media del promedio de habilidades y del promedio de exámenes, redondeada al entero; una nota en blanco cuenta como cero.
        Las notas pueden cambiar hasta que el docente cierre el acta; el documento oficial es el acta final.
    </p>
</x-pdf.documento>
