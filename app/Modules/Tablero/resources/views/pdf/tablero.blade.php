@use('App\Modules\Tablero\Support\Cifras')
@use('App\Modules\Tablero\Support\DiasDeClase')
@php($pagos = $tablero->pagosPorValidar)
@php($matriculas = $tablero->matriculas)
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Tablero gerencial</title>
        <style>
            @page { margin: 1.4cm 1.4cm 1.6cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1e293b; }
            .encabezado { width: 100%; border-bottom: 3px solid #1e3a6e; padding-bottom: 6px; margin-bottom: 12px; }
            .sigla { font-size: 14pt; font-weight: bold; color: #1e3a6e; }
            .institucion { font-size: 8pt; color: #475569; }
            h1 { font-size: 13pt; text-align: center; color: #1e3a6e; margin: 0 0 4px; }
            .alcance { text-align: center; color: #475569; margin-bottom: 12px; }
            h2 { font-size: 10pt; color: #1e3a6e; margin: 14px 0 6px; }
            table.datos { width: 100%; border-collapse: collapse; }
            table.datos th { background: #f1f5f9; text-align: left; padding: 3px 5px; border: 1px solid #e2e8f0; }
            table.datos td { padding: 3px 5px; border: 1px solid #e2e8f0; }
            table.datos .numero { text-align: right; white-space: nowrap; }
            .total td { font-weight: bold; background: #f8fafc; }
            .pie { position: fixed; bottom: -1cm; left: 0; right: 0; font-size: 7pt; color: #64748b; text-align: center; }
        </style>
    </head>
    <body>
        <table class="encabezado">
            <tr>
                <td>
                    <div class="institucion">Universidad Nacional Santiago Antúnez de Mayolo</div>
                    <div class="sigla">CILN</div>
                    <div class="institucion">Centro de Idiomas y Lenguas Nativas</div>
                </td>
            </tr>
        </table>

        <h1>TABLERO DE CONTROL GERENCIAL</h1>
        <p class="alcance">{{ $tablero->alcance() }}</p>

        <h2>Indicadores principales</h2>
        <table class="datos">
            <tr><th>Recaudación</th><th>Pagos por validar</th><th>Pendientes de validación del periodo</th><th>Matrículas</th><th>Frente al periodo anterior</th><th>Frente al año anterior</th><th>Deserción</th></tr>
            <tr>
                <td class="numero">{{ Cifras::soles($tablero->recaudacion->totalCentimos) }} ({{ $tablero->recaudacion->operaciones }} pagos)</td>
                <td class="numero">{{ $pagos->porValidar }} · {{ Cifras::soles($pagos->centimosPorValidar) }}</td>
                <td class="numero">{{ Cifras::porcentaje($pagos->tasa()) }} ({{ $pagos->pendientes }} de {{ $pagos->registrados }})</td>
                <td class="numero">{{ $matriculas->total }}</td>
                <td class="numero">{{ Cifras::variacion($matriculas->crecimientoMensual()) }}{{ $matriculas->anterior ? ' · '.$matriculas->anterior->periodo : '' }}</td>
                <td class="numero">{{ Cifras::variacion($matriculas->crecimientoAnual()) }}{{ $matriculas->delAnioAnterior ? ' · '.$matriculas->delAnioAnterior->periodo : '' }}</td>
                <td class="numero">{{ Cifras::porcentaje($tablero->desercion->total->tasa()) }}</td>
            </tr>
        </table>

        <h2>Recaudación por idioma y nivel</h2>
        <table class="datos">
            <tr><th>Idioma</th><th>Nivel</th><th class="numero">Pagos</th><th class="numero">Monto</th></tr>
            @forelse ($tablero->recaudacion->filas as $fila)
                <tr><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td class="numero">{{ $fila->operaciones }}</td><td class="numero">{{ Cifras::soles($fila->centimos) }}</td></tr>
            @empty
                <tr><td colspan="4">No hay pagos validados en este periodo.</td></tr>
            @endforelse
        </table>

        <h2>Rentabilidad por grupo</h2>
        <table class="datos">
            <tr><th>Grupo</th><th>Idioma</th><th>Nivel</th><th class="numero">Matrículas</th><th class="numero">Ingresos</th><th class="numero">Horas dictadas</th><th class="numero">Costo docente</th><th class="numero">Margen</th></tr>
            @forelse ($tablero->rentabilidad->filas as $fila)
                <tr>
                    <td>{{ $fila->codigo }}</td><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td>
                    <td class="numero">{{ $fila->matriculas }}</td>
                    <td class="numero">{{ Cifras::soles($fila->ingresosCentimos) }}</td>
                    <td class="numero">{{ Cifras::horas($fila->horasCentesimas) }}{{ $fila->horasSinCostoCentesimas > 0 ? ' + '.Cifras::horas($fila->horasSinCostoCentesimas).' sin costo' : '' }}</td>
                    <td class="numero">{{ Cifras::soles($fila->costoCentimos) }}</td>
                    <td class="numero">{{ Cifras::soles($fila->margen()) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay grupos en este periodo.</td></tr>
            @endforelse
            <tr class="total"><td colspan="4">Total</td><td class="numero">{{ Cifras::soles($tablero->rentabilidad->ingresosCentimos) }}</td><td></td><td class="numero">{{ Cifras::soles($tablero->rentabilidad->costoCentimos) }}</td><td class="numero">{{ Cifras::soles($tablero->rentabilidad->margen()) }}</td></tr>
        </table>

        <h2>Matrículas por periodo</h2>
        <table class="datos">
            <tr>@foreach ($matriculas->serie as $punto)<th class="numero">{{ $punto->periodo }}</th>@endforeach</tr>
            <tr>@foreach ($matriculas->serie as $punto)<td class="numero">{{ $punto->total }}</td>@endforeach</tr>
        </table>

        <h2>Deserción por nivel y por idioma</h2>
        <table class="datos">
            <tr><th></th><th class="numero">Matrículas</th><th class="numero">Retiros</th><th class="numero">Tasa</th></tr>
            @foreach ([...$tablero->desercion->porNivel, ...$tablero->desercion->porIdioma] as $fila)
                <tr><td>{{ $fila->etiqueta }}</td><td class="numero">{{ $fila->matriculas }}</td><td class="numero">{{ $fila->retiradas }}</td><td class="numero">{{ Cifras::porcentaje($fila->tasa()) }}</td></tr>
            @endforeach
            <tr class="total"><td>Total</td><td class="numero">{{ $tablero->desercion->total->matriculas }}</td><td class="numero">{{ $tablero->desercion->total->retiradas }}</td><td class="numero">{{ Cifras::porcentaje($tablero->desercion->total->tasa()) }}</td></tr>
        </table>

        <h2>Demanda horaria</h2>
        <table class="datos">
            <tr><th>Horario</th><th>Días</th><th class="numero">Grupos</th><th class="numero">Matriculados</th><th class="numero">Vacantes</th><th class="numero">Ocupación</th></tr>
            @forelse ($tablero->demandaHoraria as $franja)
                <tr><td>{{ $franja->horaInicio }} – {{ $franja->horaFin }}</td><td>{{ DiasDeClase::texto($franja->dias) }}</td><td class="numero">{{ $franja->grupos }}</td><td class="numero">{{ $franja->matriculados }}</td><td class="numero">{{ $franja->cupo }}</td><td class="numero">{{ Cifras::porcentaje($franja->ocupacion()) }}</td></tr>
            @empty
                <tr><td colspan="6">No hay horarios registrados en este periodo.</td></tr>
            @endforelse
        </table>

        <div class="pie">CILN · Tablero de control gerencial · generado el {{ now('America/Lima')->format('d/m/Y H:i') }}</div>
    </body>
</html>
