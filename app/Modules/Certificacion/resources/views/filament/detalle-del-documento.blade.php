@use('App\Modules\Academico\Enums\NivelCurricular')
@use('App\Modules\Certificacion\Enums\EstadoFinal')
@php
    $documento = $getRecord();
    $filas = $documento->detalle['notas'] ?? $documento->detalle['ciclos'] ?? null;
@endphp
@if ($filas !== null)
    <div class="overflow-x-auto">
        <table class="tabla-reporte">
            <thead>
                <tr><th scope="col">Ciclo</th><th scope="col">Nivel</th><th scope="col">Periodo</th><th scope="col" class="text-right">Horas lectivas</th><th scope="col" class="text-right">Nota final</th><th scope="col">Estado final</th></tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>{{ $fila['ciclo_nombre'] }} ({{ $fila['ciclo_numero'] }})</td>
                        <td>{{ NivelCurricular::from($fila['nivel'])->etiqueta() }}</td>
                        <td>{{ $fila['periodo'] ?? '—' }}</td>
                        <td class="text-right">{{ $fila['horas_lectivas'] }}</td>
                        <td class="text-right font-semibold">{{ $fila['promedio'] ?? '—' }}</td>
                        <td>{{ EstadoFinal::from($fila['estado_final'])->etiqueta() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <dl class="grid gap-3 text-sm sm:grid-cols-3">
        <div><dt class="text-gray-500">Grupo</dt><dd>{{ $documento->detalle['grupo'] }}</dd></div>
        <div><dt class="text-gray-500">Ciclo</dt><dd>{{ $documento->detalle['ciclo_numero'] }}</dd></div>
        <div><dt class="text-gray-500">Periodo</dt><dd>{{ $documento->detalle['periodo'] }}</dd></div>
        <div><dt class="text-gray-500">Modalidad</dt><dd>{{ $documento->detalle['modalidad'] }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-gray-500">Horario</dt><dd>{{ implode(' · ', $documento->detalle['horario']) ?: 'Por confirmar' }}</dd></div>
    </dl>
@endif
