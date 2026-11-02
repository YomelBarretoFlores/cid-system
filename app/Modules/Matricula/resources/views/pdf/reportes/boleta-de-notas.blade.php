<x-pdf.documento titulo="BOLETA DE NOTAS" :denominacion="$denominacion ?? null">
    <x-matricula::reportes.estudiante :estudiante="$estudiante" :generado-el="$generadoEl">
        <tr><th>Periodo</th><td>{{ $periodo }}</td></tr>
    </x-matricula::reportes.estudiante>

    <h2>Cursos del periodo</h2>
    <table class="lista">
        <thead><tr><th>Grupo</th><th>Idioma</th><th>Ciclo</th><th class="num">Horas lectivas</th><th class="num">Nota final</th><th>Estado</th></tr></thead>
        <tbody>
            @foreach ($boleta as $fila)
                <tr>
                    <td>{{ $fila->grupoCodigo }}</td>
                    <td>{{ $fila->idioma }}</td>
                    <td>{{ $fila->cicloNombre }} ({{ $fila->cicloNumero }})</td>
                    <td class="num">{{ $fila->horasLectivas }}</td>
                    <td class="num"><strong>{{ $fila->promedio ?? '—' }}</strong></td>
                    <td>{{ $fila->estado() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 10px; font-size: 8.5pt; color: #5e5a50;">Escala vigesimal (0 a 20). NSP: no se presentó. La nota final aparece cuando el acta del grupo se cierra.</p>
</x-pdf.documento>
