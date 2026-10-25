@props(['titulo', 'codigo', 'orientacion' => 'portrait'])
{{-- DOMPDF lays out tables and simple CSS only (ADR 0005); colors are the hex values of the CILN tokens. --}}
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}</title>
        <style>
            @page { margin: 1.6cm 1.7cm 2cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1d1b16; line-height: 1.4; }
            .encabezado { width: 100%; border-bottom: 2px solid #f2af48; padding-bottom: 8px; margin-bottom: 14px; }
            .encabezado td { vertical-align: middle; }
            .emblema { width: 62px; }
            .institucion { font-size: 8.5pt; color: #5e5a50; }
            .sigla { font-size: 15pt; font-weight: bold; letter-spacing: 1px; }
            .codigo { text-align: right; font-size: 8pt; color: #5e5a50; }
            h1 { font-size: 13pt; text-align: center; letter-spacing: 1px; margin: 0 0 14px; }
            h2 { font-size: 10pt; color: #8d5406; border-bottom: 1px solid #eae9e5; padding-bottom: 3px; margin: 14px 0 6px; }
            table.datos { width: 100%; border-collapse: collapse; }
            table.datos th { width: 22%; text-align: left; padding: 4px 6px; background: #fcf6e9; border: 1px solid #eae9e5; }
            table.datos td { padding: 4px 6px; border: 1px solid #eae9e5; }
            table.lista { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
            table.lista th { background: #1d1b16; color: #ffffff; padding: 5px 4px; text-align: left; font-weight: bold; }
            table.lista td { padding: 4px; border-bottom: 1px solid #eae9e5; }
            table.lista tr:nth-child(even) td { background: #fbfaf7; }
            .num { text-align: right; }
            .pie { position: fixed; bottom: -1.3cm; left: 0; right: 0; font-size: 7.5pt; color: #6e695e; text-align: center; }
        </style>
    </head>
    <body>
        <table class="encabezado">
            <tr>
                <td class="emblema"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png'))) }}" alt="" width="54"></td>
                <td>
                    <div class="institucion">Universidad Nacional Santiago Antúnez de Mayolo</div>
                    <div class="sigla">CILN</div>
                    <div class="institucion">Centro de Idiomas y Lenguas Nativas</div>
                </td>
                <td class="codigo">Código del documento<br>{{ $codigo }}</td>
            </tr>
        </table>

        <h1>{{ $titulo }}</h1>

        {{ $slot }}

        <div class="pie">Documento generado por el Sistema de Gestión Académica del CILN · Código {{ $codigo }}</div>
    </body>
</html>
