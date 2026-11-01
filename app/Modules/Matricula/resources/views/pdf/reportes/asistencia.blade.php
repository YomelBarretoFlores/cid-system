<x-pdf.documento titulo="REPORTE DE ASISTENCIA">
    <x-matricula::reportes.estudiante :estudiante="$estudiante" :generado-el="$generadoEl">
        <tr><th>Grupo</th><td>{{ $grupo->codigo }} · {{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }}</td></tr>
        <tr><th>Periodo</th><td>{{ $grupo->periodo }}</td></tr>
    </x-matricula::reportes.estudiante>

    <h2>Resumen</h2>
    <table class="datos">
        <tr><th>Clases programadas</th><td>{{ $asistencia->programadas }}</td></tr>
        <tr><th>Clases dictadas</th><td>{{ $asistencia->dictadas }}</td></tr>
        <tr><th>Presente · Ausente</th><td>{{ $asistencia->presentes }} · {{ $asistencia->faltas }}</td></tr>
        <tr><th>Asistencia sobre dictadas</th><td>{{ $asistencia->porcentajeSobreDictadas() === null ? '—' : $asistencia->porcentajeSobreDictadas().' %' }}</td></tr>
        <tr><th>Asistencia sobre programadas</th><td>{{ $asistencia->porcentajeSobreProgramadas() === null ? '—' : $asistencia->porcentajeSobreProgramadas().' %' }}</td></tr>
    </table>

    <h2>Clases</h2>
    @if ($asistencia->clases === [])
        <p>Aún no hay clases con tu asistencia registrada.</p>
    @else
        <table class="lista">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Tema</th><th>Llamado de inicio</th><th>Llamado de cierre</th><th>Resultado</th><th>Estado de la clase</th></tr></thead>
            <tbody>
                @foreach ($asistencia->clases as $clase)
                    <tr>
                        <td>{{ $clase->fecha->format('d/m/Y') }}</td>
                        <td>{{ $clase->horario }}</td>
                        <td>{{ $clase->tema ?? '—' }}</td>
                        <td>{{ $clase->inicio->etiqueta() }}</td>
                        <td>{{ $clase->cierre?->etiqueta() ?? '—' }}</td>
                        <td><strong>{{ $clase->efectivo->etiqueta() }}</strong></td>
                        <td>{{ $clase->estadoDeLaClase }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <p style="margin-top: 10px; font-size: 8.5pt; color: #5e5a50;">Una ausencia en el llamado de cierre convierte la clase en ausencia.</p>
</x-pdf.documento>
