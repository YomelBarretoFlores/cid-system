<x-pdf.documento titulo="CERTIFICADO DE CULMINACIÓN DE NIVEL" :codigo="$documento->codigo">
    <p style="text-align: center; margin-top: 22px; font-size: 10.5pt;">El Centro de Idiomas y Lenguas Nativas de la Universidad Nacional Santiago Antúnez de Mayolo certifica que</p>
    <p style="text-align: center; font-size: 16pt; font-weight: bold; margin: 10px 0 4px;">{{ $documento->estudiante_nombre }}</p>
    <p style="text-align: center; color: #5e5a50; margin: 0 0 14px;">{{ $documento->estudiante_documento }}</p>
    <p style="text-align: center; font-size: 10.5pt;">
        ha culminado satisfactoriamente el nivel <strong>{{ $documento->nivel?->etiqueta() }}</strong> de <strong>{{ $documento->idioma }}</strong>,
        con un total de <strong>{{ $documento->horas_lectivas }} horas lectivas</strong> y una nota final de <strong>{{ $documento->nota_final }}</strong>.
    </p>

    <h2>Ciclos del nivel</h2>
    <table class="lista">
        <thead><tr><th>Ciclo</th><th>Periodo académico</th><th class="num">Horas lectivas</th><th class="num">Nota final</th></tr></thead>
        <tbody>
            @foreach ($documento->detalle['ciclos'] as $ciclo)
                <tr>
                    <td>{{ $ciclo['ciclo_nombre'] }} ({{ $ciclo['ciclo_numero'] }})</td>
                    <td>{{ $ciclo['periodo'] }}</td>
                    <td class="num">{{ $ciclo['horas_lectivas'] }}</td>
                    <td class="num">{{ $ciclo['promedio'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align: right; margin-top: 22px;">Huaraz, {{ $emitidoEl }}</p>

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
