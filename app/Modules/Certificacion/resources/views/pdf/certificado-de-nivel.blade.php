{{-- A4 landscape, laid out with tables only (DOMPDF, ADR 0005): a framed page like a diploma, in the CILN colors. --}}
@php
    $unasam = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png')));
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
    $numero = $documento->numeroOficial();
    $firmas ??= [];
    $lugarYFecha ??= 'Huaraz, '.$emitidoEl;
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Certificado {{ $documento->codigo }}</title>
        <style>
            @page { size: A4 landscape; margin: 0.9cm; }
            body { font-family: 'DejaVu Serif', serif; color: #1d1b16; margin: 0; }
            .marco { border: 3px double #8d5406; padding: 12px; height: 17.9cm; }
            .interior { border: 1px solid #d9b25f; height: 16.9cm; padding: 16px 34px 10px; }
            table { width: 100%; border-collapse: collapse; }
            .sans { font-family: 'DejaVu Sans', sans-serif; }
            .titulo { font-size: 34pt; letter-spacing: 8px; text-align: center; margin: 10px 0 0; color: #1d1b16; }
            .subtitulo { font-size: 11pt; letter-spacing: 3px; text-align: center; color: #8d5406; margin: 2px 0 18px; text-transform: uppercase; }
            .otorgado { font-size: 11pt; text-align: center; margin: 0; color: #4a463d; font-style: italic; }
            .nombre { font-size: 24pt; text-align: center; margin: 6px 0 2px; font-weight: bold; }
            .documento { font-size: 9.5pt; text-align: center; color: #4a463d; margin: 0 0 14px; }
            .texto { font-size: 11.5pt; text-align: center; line-height: 1.6; margin: 0 40px; }
            .lugar { font-size: 10pt; text-align: center; margin-top: 16px; }
            .pie td { font-size: 7.5pt; color: #4a463d; vertical-align: bottom; }
        </style>
    </head>
    <body>
        <div class="marco">
            <div class="interior">
                <table>
                    <tr>
                        <td style="width: 33%; vertical-align: middle;"><img src="{{ $unasam }}" alt="Universidad Nacional Santiago Antúnez de Mayolo" style="width: 170px;"></td>
                        <td class="sans" style="width: 34%; text-align: center; vertical-align: middle; font-size: 9pt; color: #4a463d;">
                            Universidad Nacional Santiago Antúnez de Mayolo<br>
                            <strong style="font-size: 10.5pt; color: #1d1b16;">Centro de Idiomas y Lenguas Nativas</strong>
                        </td>
                        <td style="width: 33%; text-align: right; vertical-align: middle;"><img src="{{ $emblema }}" alt="CILN" style="width: 62px;"></td>
                    </tr>
                </table>

                <p class="titulo">CERTIFICADO</p>
                <p class="subtitulo sans">de culminación de nivel</p>

                <p class="otorgado">Otorgado a</p>
                <p class="nombre">{{ $documento->estudiante_nombre }}</p>
                <p class="documento sans">{{ $documento->estudiante_documento }}</p>

                <p class="texto">
                    por haber culminado satisfactoriamente el nivel <strong>{{ $documento->nivel?->etiqueta() }}</strong> de <strong>{{ $documento->idioma }}</strong>
                    en el Centro de Idiomas y Lenguas Nativas, con un total de <strong>{{ $documento->horas_lectivas }} horas lectivas</strong>
                    y una nota final de <strong>{{ $documento->nota_final }}</strong>.
                </p>

                <p class="lugar">{{ $lugarYFecha }}</p>

                <div class="sans"><x-pdf.firmas :firmas="$firmas" /></div>

                <table class="pie sans" style="margin-top: 14px;">
                    <tr>
                        <td style="width: 70px;"><img src="{{ $qr }}" alt="" width="64" height="64"></td>
                        <td>
                            @if ($numero)<strong style="color: #1d1b16;">{{ $numero }}</strong> · @endif
                            Código único: <strong style="color: #1d1b16;">{{ $documento->codigo }}</strong><br>
                            Verifique la autenticidad en {{ $verificacion }}
                        </td>
                        <td style="text-align: right;">Fecha de emisión: {{ $emitidoEl }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
