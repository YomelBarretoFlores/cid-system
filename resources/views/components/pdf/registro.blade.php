@props(['titulo', 'subtitulo' => null, 'horizontal' => true, 'dependencia' => 'Vicerrectorado Académico', 'imprimible' => false])
{{-- Formal register of the previous system (evaluation register, statistical reports): shield and CILN emblem around the centred names, black and grey tables, the emblem faded behind the page. DOMPDF lays out tables and simple CSS only (ADR 0005). --}}
@php
    $institucion = app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)();
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}{{ $subtitulo ? " {$subtitulo}" : '' }}</title>
        <style>
            @page { size: A4 {{ $horizontal ? 'landscape' : 'portrait' }}; margin: 0.9cm 1.3cm 1.3cm; }
            body { font-family: 'DejaVu Serif', serif; font-size: 8.5pt; color: #111111; line-height: 1.3; }
            table { border-collapse: collapse; }
            .fondo { position: fixed; top: {{ $horizontal ? '3.6cm' : '7.5cm' }}; left: 0; right: 0; text-align: center; }
            .fondo img { width: {{ $horizontal ? '9cm' : '11cm' }}; opacity: 0.06; }
            .membrete { width: 100%; margin-bottom: 4pt; }
            .membrete td { vertical-align: middle; text-align: center; font-family: 'DejaVu Sans', sans-serif; }
            .membrete .universidad { font-size: 11.5pt; font-weight: bold; letter-spacing: 0.3pt; }
            .membrete .dependencia { font-size: 8pt; font-weight: bold; margin-top: 5pt; letter-spacing: 0.4pt; }
            .membrete .centro { font-size: 10.5pt; font-weight: bold; margin-top: 5pt; letter-spacing: 0.6pt; }
            .membrete .titulo { font-size: 10.5pt; font-weight: bold; margin-top: 6pt; letter-spacing: 0.4pt; }
            .filete { border-top: 0.8pt solid #111111; border-bottom: 0.4pt solid #111111; height: 1.6pt; margin: 4pt 0 10pt; }
            .sans { font-family: 'DejaVu Sans', sans-serif; }
            table.datos { width: 100%; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; margin-bottom: 8pt; }
            table.datos td { padding: 1pt 0; }
            table.cabecera { width: 100%; margin-bottom: 10pt; }
            table.cabecera th, table.cabecera td { border: 0.7pt solid #111111; text-align: center; padding: 3pt 4pt; }
            table.cabecera th { font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
            table.cabecera td { font-size: 8pt; text-transform: uppercase; }
            table.registro { width: 100%; }
            table.registro th, table.registro td { border: 0.7pt solid #111111; padding: 2.5pt 3pt; }
            table.registro th { font-size: 8pt; font-weight: bold; text-align: center; }
            table.registro td { font-size: 8.5pt; }
            table.registro td.num, table.registro th.num { text-align: center; width: 4%; }
            table.registro .calculada { background: #d9d9d9; }
            table.registro tr.subtotal td, table.registro tr.total td { font-weight: bold; background: #f0f0f0; }
            h2 { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; margin: 12pt 0 4pt; page-break-after: avoid; }
            table.pie-de-registro { width: 100%; margin-top: 10pt; page-break-inside: avoid; }
            table.pie-de-registro td { vertical-align: bottom; }
            table.leyenda { font-size: 7.5pt; }
            table.leyenda td, table.leyenda th { border: 0.7pt solid #111111; padding: 1.5pt 4pt; text-align: left; }
            .firma { border: 0.7pt solid #111111; height: 40pt; }
            .firma-nombre { border: 0.7pt solid #111111; border-top: none; text-align: center; font-size: 7.5pt; padding: 2pt; text-transform: uppercase; }
            .nota { font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #555555; margin-top: 8pt; }
            .pie { position: fixed; bottom: -0.9cm; left: 0; right: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 6.5pt; color: #666666; text-align: center; }
            @if ($imprimible)
                body { max-width: {{ $horizontal ? '27cm' : '19cm' }}; margin: 1cm auto; }
                .fondo, .pie { position: static; }
                .fondo { display: none; }
                .imprimir { margin-bottom: 10pt; padding: 4pt 12pt; font: inherit; cursor: pointer; }
                @media print { .imprimir { display: none; } body { margin: 0 auto; } }
            @endif
        </style>
    </head>
    <body>
        @if ($imprimible)
            <button type="button" class="imprimir" onclick="window.print()">Imprimir</button>
        @endif

        <div class="fondo"><img src="{{ $emblema }}" alt=""></div>

        <table class="membrete">
            <tr>
                <td style="width: 80px;"><img src="{{ \App\Shared\Ui\EscudoUnasam::comoDataUri() }}" alt="Escudo de la universidad" width="62"></td>
                <td>
                    <div class="universidad">{{ mb_strtoupper($institucion->universidad) }}</div>
                    <div class="dependencia">{{ mb_strtoupper($dependencia) }}</div>
                    <div class="centro">{{ mb_strtoupper($institucion->centro) }}</div>
                    <div class="titulo">{{ mb_strtoupper($titulo) }}{{ $subtitulo ? ' '.mb_strtoupper($subtitulo) : '' }}</div>
                </td>
                <td style="width: 80px;"><img src="{{ $emblema }}" alt="Logotipo del centro" width="62"></td>
            </tr>
        </table>
        <div class="filete"></div>

        {{ $slot }}

        <div class="pie">
            {{ $institucion->centro }} · {{ $institucion->direccion }} · Telf. {{ $institucion->telefono }} · {{ $institucion->web }}
        </div>
    </body>
</html>
