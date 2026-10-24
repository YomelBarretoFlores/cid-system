@php($grupo = $documento->grupo)
<x-matricula::pdf.plantilla titulo="FICHA DE MATRÍCULA" :documento="$documento">
    <h2>Estudiante</h2>
    <table class="datos">
        <tr><th>Apellidos y nombres</th><td>{{ $documento->estudiante->nombreCompleto }}</td></tr>
        <tr><th>Documento de identidad</th><td>{{ $documento->estudiante->documento }}</td></tr>
    </table>

    <h2>Inscripción</h2>
    <table class="datos">
        <tr><th>Grupo</th><td>{{ $grupo->codigo }}</td></tr>
        <tr><th>Idioma y ciclo</th><td>{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} ({{ $grupo->nivel }})</td></tr>
        <tr><th>Modalidad</th><td>{{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</td></tr>
        <tr><th>Periodo</th><td>{{ $grupo->periodo }}</td></tr>
        <tr><th>Horario</th><td>{{ $grupo->horarios === [] ? 'Por confirmar' : implode(' · ', $grupo->horarios) }}</td></tr>
        <tr><th>{{ $grupo->aula !== null || $grupo->enlaceVirtual === null ? 'Aula' : 'Clase virtual' }}</th><td>{{ $grupo->aula ?? $grupo->enlaceVirtual ?? 'Por asignar' }}</td></tr>
        <tr><th>Fecha y hora de matrícula</th><td>{{ $documento->matriculadoEn->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Estado de la matrícula</th><td>{{ $documento->estado->etiqueta() }}</td></tr>
    </table>

    <h2>Validación académica</h2>
    <table class="datos">
        <tr><th>Condición</th><td>{{ $documento->condicion->etiqueta() }}</td></tr>
    </table>

    <h2>Validación de pago</h2>
    <table class="datos">
        <tr><th>Estado</th><td>{{ $documento->estadoDelPago() }}</td></tr>
        @if ($documento->pago !== null)
            <tr><th>Concepto</th><td>{{ $documento->pago->concepto }}</td></tr>
            <tr><th>Número de operación</th><td>{{ $documento->pago->numeroOperacion }} ({{ $documento->pago->medioPago }})</td></tr>
            <tr><th>Monto y fecha de pago</th><td>S/ {{ $documento->pago->monto }} · {{ $documento->pago->fechaPago->format('d/m/Y') }}</td></tr>
        @endif
    </table>
</x-matricula::pdf.plantilla>
