@props(['documento', 'titulo', 'institucion', 'firmas' => [], 'qr', 'verificacion', 'emitidoEl', 'denominacion' => null])
{{-- Per-level grade record in the real CILN format, shared by the grade record and the postgraduate accreditation record (which adds the degree mention). --}}
@use('App\Modules\Academico\Enums\NivelCurricular')
@use('App\Shared\Ui\Formato')
@php
    $detalle = $documento->detalle;
    $emitido = $documento->emitido_en->setTimezone('America/Lima');
    $fecha = $emitido->day.' de '.Formato::mes($emitido->month).' de '.$emitido->year;
@endphp
<x-pdf.documento :titulo="trim($titulo.' '.$documento->numeroOficial())" membrete="institucional" :institucion="$institucion" :denominacion="$denominacion">
    <p><strong>EL DIRECTOR DEL {{ mb_strtoupper($institucion->centro) }} DE LA {{ preg_replace('/^(UNIVERSIDAD NACIONAL)\s+(.+)$/u', '$1 "$2"', mb_strtoupper($institucion->universidad)) }} QUIEN SUSCRIBE AL FINAL, HACE CONSTAR LO SIGUIENTE:</strong></p>

    <table class="datos" style="margin-top: 10px;">
        <tr><th>ALUMNO</th><td>{{ mb_strtoupper($documento->estudiante_nombre) }}</td></tr>
        <tr><th>IDIOMA</th><td>{{ mb_strtoupper("{$documento->idioma} ({$detalle['programa']})") }}</td></tr>
        <tr><th>{{ $detalle['tipo_documento'] ?: 'DNI' }}</th><td>{{ $detalle['dni'] }}</td></tr>
        <tr><th>CURRICULO</th><td>{{ $detalle['curriculo'] }}</td></tr>
        @isset($detalle['mencion'])
            <tr><th>MENCIÓN</th><td>{{ mb_strtoupper($detalle['mencion']) }}</td></tr>
        @endisset
    </table>

    <table class="lista" style="margin-top: 12px;">
        <thead>
            <tr><th class="centro">ITEM</th><th>NIVEL</th><th class="centro">CICLO</th><th class="centro">AÑO</th><th>MES</th><th class="centro">NOTA</th><th>FORMA DE APROBACIÓN</th></tr>
        </thead>
        <tbody>
            @foreach ($detalle['filas'] as $fila)
                <tr>
                    <td class="centro">{{ $fila['item'] }}</td>
                    <td>{{ mb_strtoupper($fila['nivel']) }}</td>
                    <td class="centro">{{ $fila['ciclo'] }}</td>
                    <td class="centro">{{ $fila['anio'] ?? '—' }}</td>
                    <td>{{ $fila['mes'] ?? '—' }}</td>
                    <td class="centro"><strong>{{ $fila['nota'] }}</strong></td>
                    <td>{{ $fila['forma'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="nota-al-pie">{{ $documento->texto('escala') }}</p>

    <h2>Programa Curricular</h2>
    <table class="lista" style="width: 50%;">
        <tbody>
            @foreach ($detalle['programa_curricular'] as $nivel => $ciclos)
                <tr><td>{{ mb_strtoupper(NivelCurricular::from($nivel)->etiqueta()) }}</td><td class="centro">{{ $ciclos }} Ciclos</td></tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align: right; margin-top: 14px;">{{ $institucion->ciudad }}, {{ $fecha }}</p>

    <x-pdf.firmas :firmas="$firmas" />

    <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
</x-pdf.documento>
