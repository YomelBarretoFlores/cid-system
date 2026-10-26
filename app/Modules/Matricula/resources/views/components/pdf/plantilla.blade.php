@props(['titulo', 'documento'])
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}</title>
        <style>
            @page { margin: 2cm 2cm 2.2cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #1e293b; line-height: 1.45; }
            .institucion { font-size: 9pt; color: #475569; }
            .codigo { text-align: right; font-size: 7.5pt; color: #475569; margin: 6px 0 14px; }
            h1 { font-size: 14pt; text-align: center; color: #1e3a6e; letter-spacing: 1px; margin: 0 0 18px; }
            h2 { font-size: 10.5pt; color: #1e3a6e; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin: 16px 0 6px; }
            table.datos { width: 100%; border-collapse: collapse; }
            table.datos th { width: 34%; text-align: left; font-weight: bold; padding: 4px 6px; background: #f1f5f9; border: 1px solid #e2e8f0; }
            table.datos td { padding: 4px 6px; border: 1px solid #e2e8f0; }
            .pie { position: fixed; bottom: -1.4cm; left: 0; right: 0; font-size: 8pt; color: #64748b; text-align: center; }
        </style>
    </head>
    <body>
        <x-matricula::pdf.membrete />
        <div class="codigo">Código del documento<br>{{ $documento->codigo }}</div>

        <h1>{{ $titulo }}</h1>

        {{ $slot }}

        <div class="pie">Documento generado por el Sistema de Gestión Académica del CILN · Código {{ $documento->codigo }}</div>
    </body>
</html>
