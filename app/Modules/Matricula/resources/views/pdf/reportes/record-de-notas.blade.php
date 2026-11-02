<x-pdf.documento titulo="RÉCORD DE NOTAS" :denominacion="$denominacion ?? null">
    <x-matricula::reportes.estudiante :estudiante="$estudiante" :generado-el="$generadoEl" />

    @foreach ($records as $record)
        <h2>{{ $record->idioma }}</h2>
        <table class="lista">
            <thead><tr><th>Ciclo</th><th>Periodo</th><th>Grupo</th><th class="num">Nota</th><th>Estado</th><th class="num">Veces cursado</th></tr></thead>
            <tbody>
                @foreach ($record->ciclos as $ciclo)
                    <tr>
                        <td>{{ $ciclo->resultado->cicloNombre }} ({{ $ciclo->resultado->cicloNumero }})</td>
                        <td>{{ $ciclo->resultado->periodo }}</td>
                        <td>{{ $ciclo->resultado->grupoCodigo }}</td>
                        <td class="num"><strong>{{ $ciclo->resultado->promedio ?? '—' }}</strong></td>
                        <td>{{ $ciclo->resultado->estadoFinal->etiqueta() }}</td>
                        <td class="num">{{ $ciclo->veces }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="datos" style="margin-top: 8px;">
            <tr><th>Promedio de ciclos aprobados</th><td><strong>{{ $record->promedio ?? '—' }}</strong></td></tr>
            <tr>
                <th>Certificado de nivel</th>
                <td>
                    @foreach ($record->faltantes as $nivel)
                        {{ $nivel->descripcion() }}@if (! $loop->last)<br>@endif
                    @endforeach
                </td>
            </tr>
        </table>
    @endforeach

    <p style="margin-top: 14px; font-size: 8.5pt; color: #5e5a50;">
        Solo incluye actas cerradas. Cada ciclo muestra su último resultado aprobado o, si no lo aprobó, su último resultado.
    </p>
    <p style="margin-top: 6px; font-weight: bold;">Documento informativo, sin valor oficial; para trámites solicite la constancia de notas.</p>
</x-pdf.documento>
