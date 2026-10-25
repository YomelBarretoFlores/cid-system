@use('App\Shared\Ui\Formato')
<x-pdf.documento titulo="ACTA FINAL DE EVALUACIÓN" :codigo="$codigo">
    <h2>Datos del curso</h2>
    <table class="datos">
        <tr><th>Idioma</th><td>{{ $acta->idioma }}</td><th>Periodo académico</th><td>{{ $acta->periodo }}</td></tr>
        <tr><th>Nivel</th><td>{{ $acta->nivel->etiqueta() }}</td><th>Ciclo</th><td>{{ $acta->cicloNombre }} ({{ $acta->cicloNumero }})</td></tr>
        <tr><th>Grupo</th><td>{{ $acta->grupoCodigo }}</td><th>Sistema de evaluación</th><td>{{ $acta->esquema }}</td></tr>
    </table>

    <h2>Datos del docente</h2>
    <table class="datos">
        <tr><th>ID</th><td>{{ $acta->docenteId }}</td><th>Nombres completos</th><td>{{ $acta->docente }}</td></tr>
    </table>

    <h2>Calificaciones</h2>
    <table class="lista">
        <thead>
            <tr>
                <th>N.°</th>
                <th>ID estudiante</th>
                <th>Apellidos y nombres</th>
                @foreach ($acta->componentes as $componente)
                    <th class="num">{{ $componente->nombre }} ({{ Formato::nota($componente->peso) }} %)</th>
                @endforeach
                <th class="num">Promedio final</th>
                <th>Estado final</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($acta->filas as $fila)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $fila->documento }}</td>
                    <td>{{ $fila->estudiante }}</td>
                    @foreach ($acta->componentes as $componente)
                        <td class="num">{{ Formato::nota($fila->notas[$componente->id] ?? null) }}</td>
                    @endforeach
                    <td class="num"><strong>{{ $fila->promedio ?? '—' }}</strong></td>
                    <td>{{ $fila->estadoFinal->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 14px; font-size: 8.5pt; color: #5e5a50;">
        Nota máxima {{ Formato::nota($acta->notaMaxima) }} · nota mínima aprobatoria {{ Formato::nota($acta->notaMinimaAprobatoria) }}.
        NSP: no se presentó (sin ninguna nota registrada). Acta validada por el docente a cargo el {{ Formato::fechaCorta($acta->validadaEn) }} y cerrada.
    </p>
</x-pdf.documento>
