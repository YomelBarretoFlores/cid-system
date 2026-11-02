@use('App\Modules\Tablero\Support\Cifras')
@use('App\Modules\Tablero\Support\DiasDeClase')
@php($pagos = $tablero->pagosPorValidar)
@php($matriculas = $tablero->matriculas)
<x-pdf.reporte titulo="Tablero de control gerencial" :periodo="$tablero->alcance()" horizontal>
    <h2>Indicadores principales</h2>
    <table class="tabla-formal">
        <tr><th>Recaudación</th><th>Pagos por validar (todos los periodos)</th><th>Pendientes de validación del periodo (todos los idiomas y niveles)</th><th>Matrículas</th><th>Frente al periodo anterior</th><th>Frente al año anterior</th><th>Deserción</th></tr>
        <tr>
            <td class="monto">{{ Cifras::soles($tablero->recaudacion->totalCentimos) }} ({{ $tablero->recaudacion->operaciones }} pagos)</td>
            <td class="monto">{{ $pagos->porValidar }} · {{ Cifras::soles($pagos->centimosPorValidar) }}</td>
            <td class="monto">{{ Cifras::porcentaje($pagos->tasa()) }} ({{ $pagos->pendientes }} de {{ $pagos->registrados }})</td>
            <td class="monto">{{ $matriculas->total }}</td>
            <td class="monto">{{ Cifras::variacion($matriculas->crecimientoMensual()) }}{{ $matriculas->anterior ? ' · '.$matriculas->anterior->periodo : '' }}</td>
            <td class="monto">{{ Cifras::variacion($matriculas->crecimientoAnual()) }}{{ $matriculas->delAnioAnterior ? ' · '.$matriculas->delAnioAnterior->periodo : '' }}</td>
            <td class="monto">{{ Cifras::porcentaje($tablero->desercion->total->tasa()) }}</td>
        </tr>
    </table>

    <h2>Recaudación por idioma y nivel</h2>
    <table class="tabla-formal">
        <tr><th>Idioma</th><th>Nivel</th><th class="monto">Pagos</th><th class="monto">Monto</th></tr>
        @forelse ($tablero->recaudacion->filas as $fila)
            <tr><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td class="monto">{{ $fila->operaciones }}</td><td class="monto">{{ Cifras::soles($fila->centimos) }}</td></tr>
        @empty
            <tr><td colspan="4">No hay pagos validados en este periodo.</td></tr>
        @endforelse
    </table>

    <h2>Rentabilidad por grupo</h2>
    <table class="tabla-formal">
        <tr><th>Grupo</th><th>Idioma</th><th>Nivel</th><th class="monto">Matrículas</th><th class="monto">Ingresos</th><th class="monto">Horas dictadas</th><th class="monto">Costo docente</th><th class="monto">Margen</th></tr>
        @forelse ($tablero->rentabilidad->filas as $fila)
            <tr>
                <td>{{ $fila->codigo }}</td><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td>
                <td class="monto">{{ $fila->matriculas }}</td>
                <td class="monto">{{ Cifras::soles($fila->ingresosCentimos) }}</td>
                <td class="monto">{{ Cifras::horas($fila->horasCentesimas) }}{{ $fila->horasSinCostoCentesimas > 0 ? ' + '.Cifras::horas($fila->horasSinCostoCentesimas).' sin costo' : '' }}</td>
                <td class="monto">{{ Cifras::soles($fila->costoCentimos) }}</td>
                <td class="monto">{{ Cifras::soles($fila->margen()) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No hay grupos en este periodo.</td></tr>
        @endforelse
        <tr class="total"><td colspan="4">Total</td><td class="monto">{{ Cifras::soles($tablero->rentabilidad->ingresosCentimos) }}</td><td></td><td class="monto">{{ Cifras::soles($tablero->rentabilidad->costoCentimos) }}</td><td class="monto">{{ Cifras::soles($tablero->rentabilidad->margen()) }}</td></tr>
    </table>

    <h2>Matrículas por periodo</h2>
    <table class="tabla-formal">
        <tr>@foreach ($matriculas->serie as $punto)<th class="monto">{{ $punto->periodo }}</th>@endforeach</tr>
        <tr>@foreach ($matriculas->serie as $punto)<td class="monto">{{ $punto->total }}</td>@endforeach</tr>
    </table>

    <h2>Deserción por nivel y por idioma</h2>
    <table class="tabla-formal">
        <tr><th></th><th class="monto">Matrículas</th><th class="monto">Retiros</th><th class="monto">Tasa</th></tr>
        @foreach ([...$tablero->desercion->porNivel, ...$tablero->desercion->porIdioma] as $fila)
            <tr><td>{{ $fila->etiqueta }}</td><td class="monto">{{ $fila->matriculas }}</td><td class="monto">{{ $fila->retiradas }}</td><td class="monto">{{ Cifras::porcentaje($fila->tasa()) }}</td></tr>
        @endforeach
        <tr class="total"><td>Total</td><td class="monto">{{ $tablero->desercion->total->matriculas }}</td><td class="monto">{{ $tablero->desercion->total->retiradas }}</td><td class="monto">{{ Cifras::porcentaje($tablero->desercion->total->tasa()) }}</td></tr>
    </table>

    <h2>Demanda horaria</h2>
    <table class="tabla-formal">
        <tr><th>Horario</th><th>Días</th><th class="monto">Grupos</th><th class="monto">Matriculados</th><th class="monto">Vacantes</th><th class="monto">Ocupación</th></tr>
        @forelse ($tablero->demandaHoraria as $franja)
            <tr><td>{{ $franja->horaInicio }} – {{ $franja->horaFin }}</td><td>{{ DiasDeClase::texto($franja->dias) }}</td><td class="monto">{{ $franja->grupos }}</td><td class="monto">{{ $franja->matriculados }}</td><td class="monto">{{ $franja->cupo }}</td><td class="monto">{{ Cifras::porcentaje($franja->ocupacion()) }}</td></tr>
        @empty
            <tr><td colspan="6">No hay horarios registrados en este periodo.</td></tr>
        @endforelse
    </table>
</x-pdf.reporte>
