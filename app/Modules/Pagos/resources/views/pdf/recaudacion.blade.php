@use('App\Modules\Pagos\Data\ReporteDeRecaudacion', 'Reporte')
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Reporte de recaudación</title>
        <style>
            @page { margin: 1.4cm 1.4cm 1.6cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1e293b; }
            .encabezado { width: 100%; border-bottom: 3px solid #1e3a6e; padding-bottom: 6px; margin-bottom: 12px; }
            .sigla { font-size: 14pt; font-weight: bold; color: #1e3a6e; }
            .institucion { font-size: 8pt; color: #475569; }
            h1 { font-size: 13pt; text-align: center; color: #1e3a6e; margin: 0 0 4px; }
            .rango { text-align: center; color: #475569; margin-bottom: 12px; }
            h2 { font-size: 10pt; color: #1e3a6e; margin: 14px 0 6px; }
            table.datos { width: 100%; border-collapse: collapse; }
            table.datos th { background: #f1f5f9; text-align: left; padding: 3px 5px; border: 1px solid #e2e8f0; }
            table.datos td { padding: 3px 5px; border: 1px solid #e2e8f0; }
            .monto { text-align: right; white-space: nowrap; }
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

        <h1>REPORTE DE RECAUDACIÓN</h1>
        <p class="rango">Del {{ $reporte->filtros->desde->format('d/m/Y') }} al {{ $reporte->filtros->hasta->format('d/m/Y') }} · pagos validados · {{ $reporte->operaciones }} operaciones · S/ {{ Reporte::soles($reporte->totalCentimos) }}</p>

        <h2>Por concepto TUPA/TUSNE</h2>
        <table class="datos">
            <tr><th>Código</th><th>Concepto</th><th>Fuente</th><th class="monto">Operaciones</th><th class="monto">Monto recaudado</th></tr>
            @foreach ($reporte->porConcepto as $fila)
                <tr><td>{{ $fila->codigo }}</td><td>{{ $fila->nombre }}</td><td>{{ $fila->fuente }}</td><td class="monto">{{ $fila->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td></tr>
            @endforeach
            <tr class="total"><td colspan="3">Total</td><td class="monto">{{ $reporte->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($reporte->totalCentimos) }}</td></tr>
        </table>

        <h2>Por idioma y nivel</h2>
        <table class="datos">
            <tr><th>Idioma</th><th>Nivel</th><th class="monto">Operaciones</th><th class="monto">Monto recaudado</th></tr>
            @foreach ($reporte->porIdiomaYNivel as $fila)
                <tr><td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td class="monto">{{ $fila->operaciones }}</td><td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td></tr>
            @endforeach
        </table>

        <h2>Detalle por estudiante</h2>
        <table class="datos">
            <tr><th>Fecha</th><th>Documento</th><th>Estudiante</th><th>Concepto</th><th>Idioma</th><th>Nivel</th><th>Periodo</th><th>Medio</th><th>Operación</th><th class="monto">Monto</th></tr>
            @foreach ($reporte->detalle as $fila)
                <tr>
                    <td>{{ $fila->fechaPago->format('d/m/Y') }}</td><td>{{ $fila->documento }}</td><td>{{ $fila->estudiante }}</td><td>{{ $fila->concepto }}</td>
                    <td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td>{{ $fila->periodo }}</td><td>{{ $fila->medioPago }}</td><td>{{ $fila->numeroOperacion }}</td>
                    <td class="monto">S/ {{ Reporte::soles($fila->centimos) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="pie">Generado el {{ now('America/Lima')->format('d/m/Y H:i') }} por el Sistema de Gestión Académica del CILN</div>
    </body>
</html>
