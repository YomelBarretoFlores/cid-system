@use('Carbon\CarbonImmutable')
@use('App\Modules\Certificacion\Contracts\DatosInstitucionales')
@use('App\Shared\Ui\EscudoUnasam')
@use('App\Shared\Ui\Formato')
{{-- Real CILN accreditation of foreign language knowledge: A4 portrait, label : value rows, tables only (DOMPDF, ADR 0005). --}}
@php
    $institucion ??= app(DatosInstitucionales::class)();
    $detalle = $documento->detalle;
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
    $universidad = preg_replace('/^(Universidad Nacional)\s+(.+)$/u', '$1 "$2"', $institucion->universidad);
    $calificativo = $detalle['calificativo'] === null ? '—' : Formato::notaEnLetras("{$detalle['calificativo']}.00")." ({$detalle['calificativo']})";
    $nivelCompleto = $detalle['fecha_nivel'] === null ? null : CarbonImmutable::parse($detalle['fecha_nivel']);
    $emitido = $documento->emitido_en->setTimezone('America/Lima');
    $filas = [
        'Otorgado a' => mb_strtoupper(str_replace(',', '', $documento->estudiante_nombre)),
        'En el Idioma' => $documento->idioma,
        'Nivel' => $documento->nivel?->etiqueta(),
        'Calificativo' => $calificativo,
        'Modalidad' => $detalle['modalidad'],
        'Fecha' => $nivelCompleto === null ? '—' : $nivelCompleto->day.' de '.Formato::mes($nivelCompleto->month).' del '.$nivelCompleto->year,
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Acreditación {{ $documento->codigo }}</title>
        <style>
            @page { size: A4 portrait; margin: 1.6cm 2cm 1.6cm; }
            body { font-family: 'DejaVu Serif', serif; font-size: 11pt; color: #1d1b16; line-height: 1.5; }
            table { width: 100%; border-collapse: collapse; }
            .encabezado td { vertical-align: middle; text-align: center; }
            .universidad { font-size: 13pt; font-weight: bold; }
            .centro { font-family: 'DejaVu Sans', sans-serif; font-size: 12pt; font-weight: bold; letter-spacing: 2px; }
            .denominacion { text-align: center; font-style: italic; font-size: 8.5pt; color: #4a463d; margin: 8px 0 0; }
            h1 { font-size: 15pt; text-align: center; letter-spacing: 1px; margin: 26px 0 4px; }
            .base-legal { text-align: center; font-size: 9pt; font-style: italic; margin: 0 30px 26px; }
            .filas td { padding: 7px 0; vertical-align: top; }
            .filas .etiqueta { width: 32%; font-weight: bold; }
            .filas .separador { width: 4%; }
            p { text-align: justify; margin: 14px 0 0; }
            .lugar { text-align: right; margin-top: 22px; }
            .registro { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; margin-top: 8px; }
        </style>
    </head>
    <body>
        <table class="encabezado">
            <tr>
                <td style="width: 80px;"><img src="{{ $emblema }}" alt="Logotipo del centro" width="70"></td>
                <td>
                    <div class="universidad">{{ $universidad }}</div>
                    <div class="centro">{{ mb_strtoupper($institucion->centro) }}</div>
                </td>
                <td style="width: 80px;"><img src="{{ EscudoUnasam::comoDataUri() }}" alt="Escudo de la universidad" width="62"></td>
            </tr>
        </table>

        @if (filled($denominacion ?? null))
            <p class="denominacion">«{{ $denominacion }}»</p>
        @endif

        <h1>ACREDITACIÓN DE CONOCIMIENTO DE IDIOMA EXTRANJERO</h1>
        <div class="base-legal">{{ $institucion->baseLegalAcreditacion }}</div>

        <table class="filas">
            @foreach ($filas as $etiqueta => $valor)
                <tr><td class="etiqueta">{{ $etiqueta }}</td><td class="separador">:</td><td>{{ $valor }}</td></tr>
            @endforeach
        </table>

        <p>Así consta en el Acta Final correspondiente, disponible para verificación en caso de estimarse necesario.</p>
        <p>{{ $detalle['validez'] }}</p>

        <p class="lugar">{{ $institucion->ciudad }}, {{ $emitido->day }} de {{ Formato::mes($emitido->month) }} de {{ $emitido->year }}.</p>

        <x-pdf.firmas :firmas="$firmas ?? []" :cargos="['Director del Centro de Idiomas']" :espacio="60" />

        @if ($documento->numeroOficial())
            <div class="registro">N° Registro: <strong>{{ $documento->numeroDeRegistro() }}</strong></div>
        @endif

        <x-pdf.verificacion :codigo="$documento->codigo" :qr="$qr" :url="$verificacion" :emitido-el="$emitidoEl" />
    </body>
</html>
