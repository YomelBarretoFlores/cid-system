@use('App\Modules\Docencia\Data\LineaDeHoras')
<x-pdf.reporte titulo="Expediente de horas dictadas" :periodo="$expediente->periodo()" :persona="$expediente->nombre" :codigo="$expediente->codigo"
    :datos="['Categoría' => $expediente->categoria]" :emitido="now('America/Lima')->format('Y-m-d H:i:s')">
    <table class="tabla-formal">
        <thead><tr><th>Idioma</th><th>Nivel dictado</th><th class="monto">Total de horas</th><th class="monto">Costo por hora</th><th class="monto">Monto bruto referencial</th><th class="monto">Clases confirmadas</th><th class="monto">Clases no confirmadas</th></tr></thead>
        <tbody>
            @forelse ($expediente->lineas as $linea)
                <tr><td>{{ $linea->idioma }}</td><td>{{ $linea->nivel }}</td><td class="monto">{{ $linea->horas() }}</td><td class="monto">{{ $linea->costoCentimos === null ? 'Sin costo vigente' : LineaDeHoras::soles($linea->costoCentimos) }}</td><td class="monto">{{ $linea->montoCentimos === null ? '—' : LineaDeHoras::soles($linea->montoCentimos) }}</td><td class="monto">{{ $linea->clasesConfirmadas }}</td><td class="monto">{{ $linea->clasesNoConfirmadas }}</td></tr>
            @empty
                <tr><td colspan="7">No hay clases registradas en este periodo.</td></tr>
            @endforelse
            <tr class="total"><td colspan="2">Total</td><td class="monto">{{ $expediente->horasTotales() }}</td><td></td><td class="monto">{{ LineaDeHoras::soles($expediente->montoTotalCentimos()) }}</td><td class="monto">{{ $expediente->clasesConfirmadas() }}</td><td class="monto">{{ $expediente->clasesNoConfirmadas() }}</td></tr>
        </tbody>
    </table>

    @if ($expediente->tieneHorasSinCosto())
        <p class="aviso">Hay horas sin costo vigente para la categoría del docente; no suman al monto total.</p>
    @endif

    <p class="nota">Monto bruto referencial: total de horas dictadas por el costo por hora vigente de la categoría del docente. No incluye retenciones de ley ni constituye una orden de pago. Clases confirmadas: las que el delegado del curso confirmó como dictadas; no confirmadas: las que observó o dejó sin respuesta en el plazo. Son informativas y no modifican las horas ni el monto.</p>
</x-pdf.reporte>
