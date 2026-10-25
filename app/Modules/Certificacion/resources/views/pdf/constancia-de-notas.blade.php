@use('App\Modules\Academico\Enums\NivelCurricular')
@use('App\Modules\Certificacion\Enums\EstadoFinal')
<x-pdf.documento titulo="CONSTANCIA DE NOTAS" :codigo="$documento->codigo">
    <p style="text-align: justify; margin-top: 18px;">
        El Centro de Idiomas y Lenguas Nativas de la Universidad Nacional Santiago Antúnez de Mayolo hace constar que
        <strong>{{ $documento->estudiante_nombre }}</strong>, identificado(a) con {{ $documento->estudiante_documento }}, registra en
        <strong>{{ $documento->idioma }}</strong> las siguientes notas finales, según las actas cerradas:
    </p>

    <table class="lista">
        <thead>
            <tr><th>Ciclo</th><th>Nivel</th><th>Periodo académico</th><th class="num">Horas lectivas</th><th class="num">Nota final</th><th>Estado final</th></tr>
        </thead>
        <tbody>
            @foreach ($documento->detalle['notas'] as $nota)
                <tr>
                    <td>{{ $nota['ciclo_nombre'] }} ({{ $nota['ciclo_numero'] }})</td>
                    <td>{{ NivelCurricular::from($nota['nivel'])->etiqueta() }}</td>
                    <td>{{ $nota['periodo'] }}</td>
                    <td class="num">{{ $nota['horas_lectivas'] }}</td>
                    <td class="num"><strong>{{ $nota['promedio'] ?? '—' }}</strong></td>
                    <td>{{ EstadoFinal::from($nota['estado_final'])->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 10px; font-size: 8.5pt; color: #5e5a50;">Escala vigesimal (0 a 20). NSP: no se presentó.</p>
    <p style="text-align: right; margin-top: 18px;">Huaraz, {{ $emitidoEl }}</p>

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
