@use('App\Modules\Pagos\Data\ReporteDeRecaudacion', 'Reporte')
<x-pdf.reporte titulo="Reporte de recaudación" :periodo="'Del '.$reporte->filtros->desde->format('d/m/Y').' al '.$reporte->filtros->hasta->format('d/m/Y')"
    :datos="['Pagos' => 'Validados', 'Operaciones' => $reporte->operaciones, 'Total recaudado' => 'S/ '.Reporte::soles($reporte->totalCentimos)]"
    :emitido="now('America/Lima')->format('Y-m-d H:i:s')" horizontal>
    <h2>Por concepto TUPA/TUSNE</h2>
    <table class="tabla-formal">
        <thead><tr><th>Código</th><th>Concepto</th><th>Fuente</th><th class="monto">Operaciones</th><th class="monto">Monto recaudado</th></tr></thead>
        <tbody>
            @foreach ($reporte->porConcepto as $fila)
                <tr><td>{{ $fila->codigo }}</td><td>{{ $fila->nombre }}</td><td>{{ $fila->fuente }}</td><td class="monto">{{ $fila->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td></tr>
            @endforeach
            <tr class="total"><td colspan="3">Total</td><td class="monto">{{ $reporte->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($reporte->totalCentimos) }}</td></tr>
        </tbody>
    </table>

    <h2>Por idioma y nivel</h2>
    <table class="tabla-formal">
        <thead><tr><th>Idioma</th><th>Nivel</th><th class="monto">Operaciones</th><th class="monto">Monto recaudado</th></tr></thead>
        <tbody>
            @foreach ($reporte->porIdiomaYNivel as $fila)
                <tr><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td class="monto">{{ $fila->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Detalle por estudiante</h2>
    <table class="tabla-formal">
        <thead><tr><th>Fecha</th><th>Documento</th><th>Estudiante</th><th>Concepto</th><th>Idioma</th><th>Nivel</th><th>Periodo</th><th>Medio</th><th>Operación</th><th class="monto">Monto</th></tr></thead>
        <tbody>
            @foreach ($reporte->detalle as $fila)
                <tr>
                    <td>{{ $fila->fechaPago->format('d/m/Y') }}</td><td>{{ $fila->documento }}</td><td>{{ $fila->estudiante }}</td><td>{{ $fila->concepto }}</td>
                    <td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td>{{ $fila->periodo }}</td><td>{{ $fila->medioPago }}</td><td>{{ $fila->numeroOperacion }}</td>
                    <td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-pdf.reporte>
