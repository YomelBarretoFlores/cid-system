@use('Carbon\CarbonImmutable')
@php
    $detalle = $documento->detalle;
    $institucion ??= app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)();
@endphp
<x-pdf.documento titulo="CONSTANCIA DE MATRÍCULA" :codigo="$documento->codigo" :numero="$documento->numeroOficial()" :denominacion="$denominacion ?? null">
    <p><strong>LA DIRECCIÓN DEL {{ mb_strtoupper($institucion->centro) }} DE LA {{ mb_strtoupper($institucion->universidad) }}</strong>, que suscribe:</p>
    <p style="letter-spacing: 1px;"><strong>HACE CONSTAR:</strong></p>
    <p>
        Que, <strong style="text-transform: uppercase;">{{ $documento->estudiante_nombre }}</strong>, identificado(a) con {{ $documento->estudiante_documento }}, se encuentra
        <strong>MATRICULADO(A)</strong> en el idioma <strong style="text-transform: uppercase;">{{ $documento->idioma }}</strong>{{ $documento->nivel ? ', nivel ' : '' }}<strong style="text-transform: uppercase;">{{ $documento->nivel?->etiqueta() }}</strong>,
        en el periodo académico <strong>{{ $detalle['periodo'] }}</strong>, conforme al siguiente detalle:
    </p>

    <table class="datos">
        <tr><th>Grupo</th><td>{{ $detalle['grupo'] }}</td><th>Ciclo y nivel</th><td>Ciclo {{ $detalle['ciclo_numero'] }}{{ $documento->nivel ? ' · '.$documento->nivel->etiqueta() : '' }}</td></tr>
        <tr><th>Periodo académico</th><td>{{ $detalle['periodo'] }}</td><th>Modalidad</th><td>{{ $detalle['modalidad'] }}</td></tr>
        <tr><th>Horario</th><td>{{ implode(' · ', $detalle['horario']) ?: 'Por confirmar' }}</td><th>Matriculado(a) el</th><td>{{ CarbonImmutable::parse($detalle['matriculado_el'])->format('d/m/Y') }}</td></tr>
    </table>

    <p style="margin-top: 14px;">{{ $documento->texto('cierre') }}</p>
    <p style="text-align: right; margin-top: 14px;">{{ $lugarYFecha ?? $institucion->ciudad.', '.$emitidoEl }}.</p>

    <x-pdf.firmas :firmas="$firmas ?? []" />

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
