@use('App\Modules\Certificacion\Contracts\DatosInstitucionales')
@use('App\Modules\Certificacion\Support\TextoResaltado')
@use('App\Shared\Ui\EscudoUnasam')
{{-- A4 landscape diploma from the design proposal (docs/diseno) with the wording of the real CILN certificate; tables only (DOMPDF, ADR 0005). --}}
@php
    $escudo = EscudoUnasam::comoDataUri();
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
    $institucion ??= app(DatosInstitucionales::class)();
    $universidad = preg_replace('/^(UNIVERSIDAD NACIONAL)\s+(.+)$/u', '$1 "$2"', mb_strtoupper($institucion->universidad));
    $numero = $documento->numeroOficial();
    $firmas ??= [];
    $foto ??= null;
    $lugarYFecha ??= $institucion->ciudad.', '.$emitidoEl;
    $texto = TextoResaltado::html($documento->texto('certificado'), [
        'nivel' => mb_strtoupper((string) $documento->nivel?->etiqueta()),
        'idioma' => mb_strtoupper((string) $documento->idioma),
        'horas' => (int) $documento->horas_lectivas.' horas académicas',
    ]);
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
            .interior { position: relative; border: 1px solid #d9b25f; height: 17.4cm; padding: 12px 40px 8px; }
            .esquina { position: absolute; width: 9px; height: 9px; background: #d99a2b; }
            .marca-de-agua { position: absolute; top: 5cm; left: 50%; margin-left: -4.5cm; width: 9cm; opacity: 0.06; }
            table { width: 100%; border-collapse: collapse; }
            .sans { font-family: 'DejaVu Sans', sans-serif; }
            .encabezado p { margin: 0; text-align: center; }
            .universidad { font-size: 12pt; font-weight: bold; letter-spacing: 1px; }
            .vicerrectoria { font-size: 10pt; font-style: italic; }
            .centro { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; font-weight: bold; letter-spacing: 3px; margin-top: 2px; }
            .resolucion { font-size: 7.5pt; color: #4a463d; }
            .denominacion { text-align: center; font-style: italic; font-size: 8pt; color: #4a463d; margin: 4px 0 0; }
            .titulo { font-size: 36pt; letter-spacing: 9px; text-align: center; margin: 6px 0 8px; }
            .otorga { font-size: 11pt; text-align: center; margin: 0; }
            .nombre { font-size: 22pt; text-align: center; margin: 4px auto 0; padding-bottom: 4px; border-bottom: 1px solid #4a463d; width: 62%; }
            .documento { font-size: 8.5pt; text-align: center; letter-spacing: 1px; color: #4a463d; margin: 4px 0 10px; }
            .texto { font-size: 11.5pt; text-align: center; line-height: 1.6; margin: 0 40px; }
            .lugar { font-size: 10pt; text-align: right; margin: 10px 30px 0 0; }
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
                <table class="encabezado">
                    <tr>
                        <td style="width: 3cm; vertical-align: middle;"><img src="{{ $escudo }}" alt="Escudo de la UNASAM" style="width: 66px;"></td>
                        <td style="vertical-align: middle;">
                            <p class="universidad">{{ $universidad }}</p>
                            <p class="vicerrectoria">Vicerrectoría Académica</p>
                            <p class="centro">{{ mb_strtoupper($institucion->centro) }}</p>
                            <p class="resolucion">{{ $institucion->resolucionCreacion }}</p>
                        </td>
                        <td style="width: 3cm; text-align: right; vertical-align: top;">
                            @if ($foto)
                                <img alt="Fotografía" src="{{ $foto }}" style="width: 2.4cm; height: 3cm; border: 1px solid #4a463d;">
                            @else
                                <img src="{{ $emblema }}" alt="CILN" style="width: 66px;">
                            @endif
                        </td>
                    </tr>
                </table>

                @if (filled($denominacion ?? null))
                    <p class="denominacion">«{{ $denominacion }}»</p>
                @endif
                <p class="titulo">CERTIFICADO</p>

                <p class="otorga">Otorgado a:</p>
                <p class="nombre">{{ $documento->estudiante_nombre }}</p>
                <p class="documento sans">{{ $documento->estudiante_documento }}</p>

                <p class="texto">{{ $texto }}</p>

                <p class="lugar">{{ $lugarYFecha }}.</p>

                <x-pdf.firmas :firmas="$firmas" :cargos="['Vicerrector Académico', 'Director del Centro de Idiomas']" :espacio="90" />

                <table class="pie sans" style="margin-top: 10px;">
                    <tr>
                        <td style="width: 62px;"><img src="{{ $qr }}" alt="" width="56" height="56"></td>
                        <td>
                            @if ($numero)N° Registro: <strong style="color: #1d1b16;">{{ $documento->numeroDeRegistro() }}</strong><br>@endif
                            Código único: <strong style="color: #1d1b16;">{{ $documento->codigo }}</strong><br>
                            Verifique en {{ $verificacion }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
