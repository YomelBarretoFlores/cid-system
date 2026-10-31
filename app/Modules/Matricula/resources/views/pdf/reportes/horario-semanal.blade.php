<x-pdf.documento titulo="HORARIO SEMANAL" :imprimible="$imprimible ?? false">
    <x-matricula::reportes.estudiante :estudiante="$estudiante" :generado-el="$generadoEl">
        <tr><th>Periodo</th><td>{{ $periodo }}</td></tr>
    </x-matricula::reportes.estudiante>

    <h2>Semana</h2>
    @if ($horario->filas === [])
        <p>No tienes clases programadas en este periodo.</p>
    @else
        <table class="lista">
            <thead>
                <tr>
                    <th>Hora</th>
                    @foreach ($horario->dias as $dia)
                        <th>{{ $dia }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($horario->filas as $fila)
                    <tr>
                        <td>{{ $fila->horas }}</td>
                        @foreach (array_keys($horario->dias) as $numero)
                            <td>
                                @foreach ($fila->celdas[$numero] ?? [] as $codigo)
                                    {{ $codigo }}@if (! $loop->last)<br>@endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Leyenda</h2>
        <table class="lista">
            <thead><tr><th>Grupo</th><th>Idioma y ciclo</th><th>Docente</th><th>Aula</th><th>Clase virtual</th></tr></thead>
            <tbody>
                @foreach ($horario->grupos as $grupo)
                    <tr>
                        <td>{{ $grupo->codigo }}</td>
                        <td>{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }}</td>
                        <td>{{ $grupo->docente }}</td>
                        <td>{{ $grupo->aula ?? '—' }}</td>
                        <td>{{ $grupo->enlaceVirtual ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-pdf.documento>
