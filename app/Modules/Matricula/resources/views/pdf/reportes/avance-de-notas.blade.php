<x-pdf.documento titulo="AVANCE DE NOTAS" :denominacion="$denominacion ?? null">
    <x-matricula::reportes.estudiante :estudiante="$estudiante" :generado-el="$generadoEl">
        <tr><th>Grupo</th><td>{{ $grupo->codigo }} · {{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} ({{ $grupo->nivel }})</td></tr>
        <tr><th>Periodo</th><td>{{ $grupo->periodo }}</td></tr>
    </x-matricula::reportes.estudiante>

    <h2>{{ $avance->actaCerrada ? 'Notas finales' : 'Notas registradas' }}</h2>
    @if ($avance->disponible)
        <table class="lista">
            <thead><tr><th>Componente</th><th>Grupo</th><th class="num">Nota</th></tr></thead>
            <tbody>
                @foreach ($avance->componentes as $componente)
                    <tr><td>{{ $componente->nombre }}</td><td>{{ $componente->grupo->etiqueta() }}</td><td class="num">{{ $componente->nota ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <table class="datos" style="margin-top: 10px;">
            <tr><th>Promedio de habilidades</th><td>{{ $avance->promedioHabilidades ?? '—' }}</td></tr>
            <tr><th>Promedio de exámenes</th><td>{{ $avance->promedioExamenes ?? '—' }}</td></tr>
            <tr><th>{{ $avance->actaCerrada ? 'Promedio final' : 'Promedio parcial' }}</th><td><strong>{{ $avance->promedio ?? '—' }}</strong></td></tr>
            <tr><th>Estado</th><td>{{ $avance->estadoFinal?->etiqueta() ?? 'Acta abierta' }}</td></tr>
        </table>
        <p style="margin-top: 10px; font-size: 8.5pt; color: #5e5a50;">
            Escala vigesimal (0 a 20); nota mínima aprobatoria {{ $avance->notaMinimaAprobatoria }}.
            La nota final es la media del promedio de habilidades y del promedio de exámenes, redondeada al entero.
            @unless ($avance->actaCerrada)
                El promedio parcial cuenta como cero los componentes sin nota y puede cambiar hasta el cierre del acta.
            @endunless
        </p>
    @else
        <p>{{ $avance->aviso }}</p>
    @endif
</x-pdf.documento>
