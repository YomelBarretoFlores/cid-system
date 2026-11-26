@use('App\Modules\Academico\Enums\NivelCurricular')
@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('App\Shared\Ui\Formato')
@php
    $notas = $documento->detalle['notas'];
    $promedios = array_values(array_filter(array_column($notas, 'promedio'), is_numeric(...)));
    $promedio = $promedios === [] ? null : number_format(array_sum($promedios) / count($promedios), 2, '.', '');
@endphp
<x-pdf.documento titulo="CONSTANCIA DE NOTAS" :codigo="$documento->codigo" :numero="$documento->numeroOficial()" :denominacion="$denominacion ?? null">
    <p><strong>LA DIRECCIÓN DEL CENTRO DE IDIOMAS Y LENGUAS NATIVAS DE LA UNIVERSIDAD NACIONAL SANTIAGO ANTÚNEZ DE MAYOLO</strong>, que suscribe:</p>
    <p style="letter-spacing: 1px;"><strong>HACE CONSTAR:</strong></p>
    <p>
        Que, <strong style="text-transform: uppercase;">{{ $documento->estudiante_nombre }}</strong>, identificado(a) con {{ $documento->estudiante_documento }}, ha cursado en este Centro el idioma
        <strong style="text-transform: uppercase;">{{ $documento->idioma }}</strong>, obteniendo las siguientes calificaciones finales, según las actas de evaluación cerradas que obran en nuestros registros:
    </p>

    <table class="lista">
        <thead>
            <tr><th>Ciclo</th><th>Nivel</th><th>Periodo</th><th class="centro">Horas</th><th class="centro">Nota</th><th>En letras</th><th>Condición</th></tr>
        </thead>
        <tbody>
            @foreach ($notas as $nota)
                <tr>
                    <td>{{ $nota['ciclo_nombre'] }} ({{ $nota['ciclo_numero'] }})</td>
                    <td>{{ NivelCurricular::from($nota['nivel'])->etiqueta() }}</td>
                    <td>{{ $nota['periodo'] ?? '—' }}</td>
                    <td class="centro">{{ $nota['horas_lectivas'] }}</td>
                    <td class="centro"><strong>{{ $nota['promedio'] ?? '—' }}</strong></td>
                    <td>{{ Formato::notaEnLetras($nota['promedio']) }}</td>
                    <td>{{ EstadoFinal::from($nota['estado_final'])->etiqueta() }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3" class="num">Total / promedio</td>
                <td class="centro">{{ array_sum(array_column($notas, 'horas_lectivas')) }}</td>
                <td class="centro">{{ $promedio ?? '—' }}</td>
                <td colspan="2">{{ Formato::notaEnLetras($promedio) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="nota-al-pie">Escala vigesimal de 0 a 20. NSP: no se presentó.</p>
    <p style="margin-top: 14px;">Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.</p>
    <p style="text-align: right; margin-top: 14px;">{{ $lugarYFecha ?? 'Huaraz, '.$emitidoEl }}.</p>

    <x-pdf.firmas :firmas="$firmas ?? []" sello />

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
