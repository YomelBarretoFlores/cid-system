@props(['titulo', 'codigo' => null, 'numero' => null, 'denominacion' => null, 'imprimible' => false, 'institucion' => null, 'membrete' => 'ciln'])
{{-- Official letter layout from the design proposal (docs/diseno): UNASAM mark on the left, CILN on the right, a gold double rule and a spaced serif title. The "institucional" letterhead follows the CILN paper format: shield, centred names, emblem. DOMPDF lays out tables and simple CSS only (ADR 0005). --}}
@php
    $institucion ??= app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)();
    $unasam = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png')));
    $institucional = $membrete === 'institucional';
    $emblema = 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png')));
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}</title>
        <style>
            @page { margin: 1.5cm 1.8cm 2cm; }
            body { font-family: 'DejaVu Serif', serif; font-size: 9.5pt; color: #1d1b16; line-height: 1.5; }
            .sans { font-family: 'DejaVu Sans', sans-serif; }
            .encabezado { width: 100%; border-bottom: 3px double #8d5406; padding-bottom: 8px; }
            .encabezado td { vertical-align: middle; }
            .centro-ciln { text-align: right; font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; letter-spacing: 1.5px; color: #4a463d; line-height: 1.35; }
            .centro-ciln strong { display: block; font-size: 11pt; letter-spacing: 2px; color: #8d5406; }
            .denominacion { text-align: center; font-style: italic; font-size: 8.5pt; color: #4a463d; margin: 8px 0 0; }
            h1 { font-size: 14pt; text-align: center; letter-spacing: 3px; margin: 18px 0 2px; }
            .numero { text-align: center; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; letter-spacing: 1px; color: #4a463d; margin: 0 0 16px; }
            h2 { font-size: 10pt; letter-spacing: 1px; text-transform: uppercase; margin: 14px 0 6px; }
            p { text-align: justify; }
            table.datos { width: 100%; border-collapse: collapse; font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; }
            table.datos th { width: 18%; text-align: left; padding: 4px 6px; background: #f6f4ef; border: 1px solid #9c968a; font-size: 7.5pt; text-transform: uppercase; }
            table.datos td { padding: 4px 6px; border: 1px solid #9c968a; }
            table.lista { width: 100%; border-collapse: collapse; font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; }
            table.lista th { background: #f6f4ef; padding: 5px 4px; text-align: left; font-size: 7pt; text-transform: uppercase; border: 1px solid #9c968a; }
            table.lista td { padding: 4px; border: 1px solid #9c968a; }
            table.lista tr.total td { background: #f6f4ef; font-weight: bold; }
            .num { text-align: right; }
            .centro { text-align: center; }
            .nota-al-pie { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5pt; color: #5e5a50; margin-top: 6px; }
            .membrete td { vertical-align: middle; }
            .membrete .nombres { text-align: center; font-family: 'DejaVu Sans', sans-serif; line-height: 1.35; }
            .membrete .universidad { font-size: 11pt; font-weight: bold; letter-spacing: 0.5px; }
            .membrete .lema { font-family: 'DejaVu Serif', serif; font-style: italic; font-size: 8.5pt; color: #4a463d; }
            .membrete .centro-del-membrete { font-size: 12pt; font-weight: bold; letter-spacing: 1px; }
            .pie { position: fixed; bottom: -1.3cm; left: 0; right: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #6e695e; text-align: center; }
            @if ($imprimible)
                body { max-width: 26cm; margin: 1.5cm auto; }
                .pie { position: static; margin-top: 24px; }
                .imprimir { margin-bottom: 14px; padding: 6px 14px; font: inherit; cursor: pointer; }
                @media print { .imprimir { display: none; } body { margin: 0 auto; } }
            @endif
        </style>
    </head>
    <body>
        @if ($imprimible)
            <button type="button" class="imprimir" onclick="window.print()">Imprimir</button>
        @endif

        @if ($institucional)
            <table class="encabezado membrete">
                <tr>
                    <td style="width: 70px;"><img src="{{ \App\Shared\Ui\EscudoUnasam::comoDataUri() }}" alt="Escudo de la universidad" width="58"></td>
                    <td class="nombres">
                        <div class="universidad">{{ mb_strtoupper($institucion->universidad) }}</div>
                        <div class="lema">{{ $institucion->lema }}</div>
                        <div class="centro-del-membrete">{{ mb_strtoupper($institucion->centro) }}</div>
                    </td>
                    <td style="width: 70px; text-align: right;"><img src="{{ $emblema }}" alt="Logotipo del centro" width="58"></td>
                </tr>
            </table>
        @else
            <table class="encabezado">
                <tr>
                    <td><img src="{{ $unasam }}" alt="{{ $institucion->universidad }}" style="width: 190px;"></td>
                    <td class="centro-ciln">{{ $institucion->centro }}<strong>CILN</strong></td>
                    <td style="width: 58px; text-align: right;"><img src="{{ $emblema }}" alt="CILN" width="50"></td>
                </tr>
            </table>
        @endif

        @if (filled($denominacion))
            <p class="denominacion">«{{ $denominacion }}»</p>
        @endif

        <h1>{{ $titulo }}</h1>
        @unless ($institucional)
            <p class="numero">{{ $numero ?? ($codigo ? "Código {$codigo}" : '') }}</p>
        @endunless

        {{ $slot }}

        <div class="pie">
            @if ($institucional)
                {{ $institucion->direccion }} – Telf: {{ $institucion->telefono }}<br>
                Web: {{ $institucion->web }} / Facebook: {{ $institucion->facebook }}
            @else
                {{ $institucion->direccion }} – Telf: {{ $institucion->telefono }} · Web: {{ $institucion->web }} / Facebook: {{ $institucion->facebook }}<br>
                Documento generado por el Sistema de Gestión Académica del CILN{{ $codigo ? " · Código {$codigo}" : '' }}
            @endif
        </div>
    </body>
</html>
