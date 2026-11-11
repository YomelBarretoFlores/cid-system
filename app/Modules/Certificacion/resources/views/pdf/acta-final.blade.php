@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('App\Shared\Ui\Formato')
@php
    $conteo = fn (EstadoFinal $estado): int => count(array_filter($acta->filas, fn ($fila): bool => $fila->estadoFinal === $estado));
@endphp
<x-pdf.documento titulo="ACTA FINAL DE EVALUACIÓN" :codigo="$codigo" :numero="'Acta N.º '.$codigo" :denominacion="$denominacion ?? null">
    <table style="width: 100%; margin: -12px 0 8px;">
        <tr>
            <td style="text-align: right;">
                <span class="sans" style="border: 1px solid #24663e; color: #24663e; padding: 3px 8px; font-size: 7.5pt; font-weight: bold; letter-spacing: 1px;">CERRADA · {{ $acta->validadaEn?->setTimezone('America/Lima')->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <th style="width: 9%;">Idioma</th><td>{{ $acta->idioma }}</td>
            <th style="width: 11%;">Nivel / ciclo</th><td>{{ $acta->nivel->etiqueta() }} · {{ Formato::romano($acta->cicloNumero) }}</td>
            <th style="width: 8%;">Grupo</th><td style="white-space: nowrap;">{{ $acta->grupoCodigo }}</td>
            <th style="width: 9%;">Periodo</th><td>{{ $acta->periodo }}</td>
        </tr>
        <tr>
            <th>Docente</th><td colspan="5">{{ $acta->docente }} ({{ $acta->docenteCodigo() }})</td>
            <th>Evaluación</th><td>{{ $acta->esquema }}</td>
        </tr>
    </table>

    <table class="lista" style="margin-top: 10px;">
        <thead>
            <tr>
                <th class="centro">N.°</th>
                <th>Documento</th>
                <th>Apellidos y nombres</th>
                @foreach ($acta->componentes as $componente)
                    <th class="centro">{{ $componente->nombre }} ({{ Formato::nota($componente->peso) }} %)</th>
                @endforeach
                <th class="centro">Promedio</th>
                <th>En letras</th>
                <th>Condición</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($acta->filas as $fila)
                <tr>
                    <td class="centro">{{ $loop->iteration }}</td>
                    <td>{{ $fila->documento }}</td>
                    <td style="text-transform: uppercase;">{{ $fila->estudiante }}</td>
                    @foreach ($acta->componentes as $componente)
                        <td class="centro">{{ Formato::nota($fila->notas[$componente->id] ?? null) }}</td>
                    @endforeach
                    <td class="centro"><strong>{{ $fila->promedio ?? '—' }}</strong></td>
                    <td>{{ Formato::notaEnLetras($fila->promedio) }}</td>
                    <td @if ($fila->estadoFinal !== EstadoFinal::Aprobado) style="font-weight: bold;" @endif>{{ $fila->estadoFinal->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 8px;">
        <tr>
            <td style="vertical-align: top;">
                <table class="lista" style="width: auto;">
                    <tr>
                        <th>Matriculados</th><td class="centro">{{ count($acta->filas) }}</td>
                        <th>Aprobados</th><td class="centro">{{ $conteo(EstadoFinal::Aprobado) }}</td>
                        <th>Desaprobados</th><td class="centro">{{ $conteo(EstadoFinal::Desaprobado) }}</td>
                        <th>NSP</th><td class="centro">{{ $conteo(EstadoFinal::Nsp) }}</td>
                    </tr>
                </table>
            </td>
            <td class="nota-al-pie" style="text-align: right; vertical-align: top; width: 45%;">
                Escala 0 a {{ Formato::nota($acta->notaMaxima) }}; aprobatoria {{ Formato::nota($acta->notaMinimaAprobatoria) }}. NSP: sin ninguna nota registrada.
                Validada por el docente el {{ $acta->validadaEn ? Formato::fechaCorta($acta->validadaEn) : '—' }} y cerrada.
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 4px; page-break-inside: avoid;">
        <tr>
            <td style="width: 90px; vertical-align: bottom;">
                @isset($qr)
                    <img src="{{ $qr }}" alt="" width="74" height="74">
                @endisset
            </td>
            <td><x-pdf.firmas :firmas="$firmas ?? []" sello :espacio="18" /></td>
        </tr>
    </table>
</x-pdf.documento>
