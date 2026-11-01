@use('App\Shared\Ui\Formato')
{{-- A4 landscape diploma from the design proposal (docs/diseno): double gold frame with corners, the emblem as a faint watermark; tables only (DOMPDF, ADR 0005). --}}
@php
    $unasam = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png')));
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
    $numero = $documento->numeroOficial();
    $firmas ??= [];
    $lugarYFecha ??= 'Huaraz, '.$emitidoEl;
    $ciclos = array_column($documento->detalle['ciclos'] ?? [], 'ciclo_numero');
    $rango = $ciclos === [] ? null : 'ciclos '.Formato::romano(min($ciclos)).' al '.Formato::romano(max($ciclos));
    $horas = (int) $documento->horas_lectivas;
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Certificado {{ $documento->codigo }}</title>
        <style>
            @page { size: A4 landscape; margin: 0.8cm; }
            body { font-family: 'DejaVu Serif', serif; color: #1d1b16; margin: 0; }
            .marco { position: relative; border: 2px solid #b8862f; padding: 9px; height: 18.1cm; }
            .interior { position: relative; border: 1px solid #d9b25f; height: 17.4cm; padding: 14px 40px 8px; }
            .esquina { position: absolute; width: 9px; height: 9px; background: #d99a2b; }
            .marca-de-agua { position: absolute; top: 4.2cm; left: 50%; margin-left: -4.5cm; width: 9cm; opacity: 0.06; }
            table { width: 100%; border-collapse: collapse; }
            .sans { font-family: 'DejaVu Sans', sans-serif; }
            .denominacion { text-align: center; font-style: italic; font-size: 8.5pt; color: #4a463d; margin: 2px 0 0; }
            .centro-ciln { text-align: center; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; letter-spacing: 3px; margin: 6px 0 0; }
            .titulo { font-size: 38pt; letter-spacing: 9px; text-align: center; margin: 2px 0 0; }
            .subtitulo { font-size: 14pt; font-style: italic; text-align: center; color: #8d5406; margin: 0 0 12px; }
            .otorga { font-size: 10.5pt; text-align: center; margin: 0; }
            .nombre { font-size: 23pt; text-align: center; margin: 6px auto 0; padding-bottom: 4px; border-bottom: 1px solid #4a463d; width: 62%; }
            .documento { font-size: 8.5pt; text-align: center; letter-spacing: 1px; color: #4a463d; margin: 4px 0 12px; }
            .texto { font-size: 11pt; text-align: center; line-height: 1.65; margin: 0 30px; }
            .lugar { font-size: 10pt; text-align: center; margin-top: 10px; }
            .pie td { font-size: 7pt; color: #4a463d; vertical-align: bottom; }
        </style>
    </head>
    <body>
        <div class="marco">
            <div class="esquina" style="top: -6px; left: -6px;"></div>
            <div class="esquina" style="top: -6px; right: -6px;"></div>
            <div class="esquina" style="bottom: -6px; left: -6px;"></div>
            <div class="esquina" style="bottom: -6px; right: -6px;"></div>
            <div class="interior">
                <img class="marca-de-agua" src="{{ $emblema }}" alt="">
                <table>
                    <tr>
                        <td style="width: 50%; vertical-align: middle;"><img src="{{ $unasam }}" alt="Universidad Nacional Santiago Antúnez de Mayolo" style="width: 200px;"></td>
                        <td style="width: 50%; text-align: right; vertical-align: middle;"><img src="{{ $emblema }}" alt="CILN" style="width: 66px;"></td>
                    </tr>
                </table>

                @if (filled($denominacion ?? null))
                    <p class="denominacion">«{{ $denominacion }}»</p>
                @endif
                <p class="centro-ciln">CENTRO DE IDIOMAS Y LENGUAS NATIVAS</p>
                <p class="titulo">CERTIFICADO</p>
                <p class="subtitulo">de culminación de nivel</p>

                <p class="otorga">La Dirección del Centro de Idiomas y Lenguas Nativas otorga el presente certificado a</p>
                <p class="nombre">{{ $documento->estudiante_nombre }}</p>
                <p class="documento sans">{{ $documento->estudiante_documento }}</p>

                <p class="texto">
                    por haber culminado satisfactoriamente el nivel <strong style="text-transform: uppercase;">{{ $documento->nivel?->etiqueta() }}</strong>
                    del idioma <strong style="text-transform: uppercase;">{{ $documento->idioma }}</strong>{{ $rango ? ", {$rango}," : ',' }}
                    con un total de <strong>{{ Formato::numeroEnLetras($horas, femenino: true) }} ({{ $horas }}) horas lectivas</strong>
                    y un promedio final de <strong>{{ Formato::notaEnLetras($documento->nota_final) }} ({{ $documento->nota_final }})</strong>.
                </p>

                <p class="lugar">{{ $lugarYFecha }}.</p>

                <table class="pie sans" style="margin-top: 18px;">
                    <tr>
                        <td style="width: 74px;"><img src="{{ $qr }}" alt="" width="66" height="66"></td>
                        <td style="width: 220px;">
                            @if ($numero)<strong style="color: #1d1b16;">{{ $numero }}</strong><br>@endif
                            Código único: <strong style="color: #1d1b16;">{{ $documento->codigo }}</strong><br>
                            Verifique en {{ $verificacion }}
                        </td>
                        <td><x-pdf.firmas :firmas="$firmas" sello /></td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
