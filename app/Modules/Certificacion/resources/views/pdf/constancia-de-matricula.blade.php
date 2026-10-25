@use('Carbon\CarbonImmutable')
@php($detalle = $documento->detalle)
<x-pdf.documento titulo="CONSTANCIA DE MATRÍCULA" :codigo="$documento->codigo">
    <p style="text-align: justify; margin-top: 18px;">
        El Centro de Idiomas y Lenguas Nativas de la Universidad Nacional Santiago Antúnez de Mayolo hace constar que
        <strong>{{ $documento->estudiante_nombre }}</strong>, identificado(a) con {{ $documento->estudiante_documento }}, se encuentra matriculado(a)
        en el curso de <strong>{{ $documento->idioma }}</strong>, con los siguientes datos:
    </p>

    <table class="datos">
        <tr><th>Grupo</th><td>{{ $detalle['grupo'] }}</td></tr>
        <tr><th>Ciclo y nivel</th><td>Ciclo {{ $detalle['ciclo_numero'] }}{{ $documento->nivel ? ' · '.$documento->nivel->etiqueta() : '' }}</td></tr>
        <tr><th>Periodo académico</th><td>{{ $detalle['periodo'] }}</td></tr>
        <tr><th>Modalidad</th><td>{{ $detalle['modalidad'] }}</td></tr>
        <tr><th>Horario</th><td>{{ implode(' · ', $detalle['horario']) ?: 'Por confirmar' }}</td></tr>
        <tr><th>Matriculado(a) el</th><td>{{ CarbonImmutable::parse($detalle['matriculado_el'])->format('d/m/Y') }}</td></tr>
    </table>

    <p style="text-align: justify; margin-top: 16px;">Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.</p>
    <p style="text-align: right; margin-top: 22px;">Huaraz, {{ $emitidoEl }}</p>

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
