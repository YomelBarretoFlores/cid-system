@use('App\Modules\Docencia\Data\LineaDeHoras')
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Expediente de horas dictadas</title>
        <style>
            @page { margin: 1.6cm 1.6cm 1.8cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1e293b; }
            .encabezado { width: 100%; border-bottom: 3px solid #1e3a6e; padding-bottom: 6px; margin-bottom: 12px; }
            .sigla { font-size: 14pt; font-weight: bold; color: #1e3a6e; }
            .institucion { font-size: 8pt; color: #475569; }
            h1 { font-size: 13pt; text-align: center; color: #1e3a6e; margin: 0 0 4px; }
            .periodo { text-align: center; color: #475569; margin-bottom: 14px; }
            table.ficha { width: 100%; margin-bottom: 12px; }
            table.ficha th { text-align: left; width: 28%; color: #475569; font-weight: normal; padding: 2px 0; }
            table.datos { width: 100%; border-collapse: collapse; }
            table.datos th { background: #f1f5f9; text-align: left; padding: 4px 6px; border: 1px solid #e2e8f0; }
            table.datos td { padding: 4px 6px; border: 1px solid #e2e8f0; }
            .monto { text-align: right; white-space: nowrap; }
            .total td { font-weight: bold; background: #f8fafc; }
            .aviso { margin-top: 10px; color: #92400e; }
            .nota { margin-top: 14px; font-size: 8pt; color: #475569; }
            .pie { position: fixed; bottom: -1.1cm; left: 0; right: 0; font-size: 7pt; color: #64748b; text-align: center; }
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

        <h1>EXPEDIENTE DE HORAS DICTADAS</h1>
        <p class="periodo">Periodo: {{ $expediente->periodo() }}</p>

        <table class="ficha">
            <tr><th>ID docente</th><td>{{ $expediente->codigo }}</td></tr>
            <tr><th>Nombres del docente</th><td>{{ $expediente->nombre }}</td></tr>
            <tr><th>Categoría</th><td>{{ $expediente->categoria }}</td></tr>
        </table>

        <table class="datos">
            <tr><th>Idioma</th><th>Nivel dictado</th><th class="monto">Total de horas</th><th class="monto">Costo por hora</th><th class="monto">Monto bruto referencial</th><th class="monto">Clases confirmadas</th><th class="monto">Clases no confirmadas</th></tr>
            @forelse ($expediente->lineas as $linea)
                <tr><td>{{ $linea->idioma }}</td><td>{{ $linea->nivel }}</td><td class="monto">{{ $linea->horas() }}</td><td class="monto">{{ $linea->costoCentimos === null ? 'Sin costo vigente' : LineaDeHoras::soles($linea->costoCentimos) }}</td><td class="monto">{{ $linea->montoCentimos === null ? '—' : LineaDeHoras::soles($linea->montoCentimos) }}</td><td class="monto">{{ $linea->clasesConfirmadas }}</td><td class="monto">{{ $linea->clasesNoConfirmadas }}</td></tr>
            @empty
                <tr><td colspan="7">No hay clases registradas en este periodo.</td></tr>
            @endforelse
            <tr class="total"><td colspan="2">Total</td><td class="monto">{{ $expediente->horasTotales() }}</td><td></td><td class="monto">{{ LineaDeHoras::soles($expediente->montoTotalCentimos()) }}</td><td class="monto">{{ $expediente->clasesConfirmadas() }}</td><td class="monto">{{ $expediente->clasesNoConfirmadas() }}</td></tr>
        </table>

        @if ($expediente->tieneHorasSinCosto())
            <p class="aviso">Hay horas sin costo vigente para la categoría del docente; no suman al monto total.</p>
        @endif

        <p class="nota">Monto bruto referencial: total de horas dictadas por el costo por hora vigente de la categoría del docente. No incluye retenciones de ley ni constituye una orden de pago. Clases confirmadas: las que el delegado del curso confirmó como dictadas; no confirmadas: las que observó o dejó sin respuesta en el plazo. Son informativas y no modifican las horas ni el monto.</p>

        <div class="pie">Generado el {{ now('America/Lima')->format('d/m/Y H:i') }} · Sistema de Gestión Académica del CILN</div>
    </body>
</html>
