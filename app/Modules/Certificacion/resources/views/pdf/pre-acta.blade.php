@use('App\Shared\Ui\Formato')
<x-pdf.documento titulo="PRE ACTA DE EVALUACIÓN">
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
                <th>N.°</th>
                <th>ID estudiante</th>
                <th>Apellidos y nombres</th>
                @foreach ($acta->componentes as $componente)
                    <th class="num">{{ $componente->nombre }} ({{ Formato::nota($componente->peso) }} %)</th>
                @endforeach
                <th class="num">Promedio a la fecha</th>
                <th>Estado a la fecha</th>
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
        Las notas pueden cambiar hasta que el docente cierre el acta; el documento oficial es el acta final.
    </p>
</x-pdf.documento>
