@props(['titulo', 'periodo' => null, 'persona' => null, 'codigo' => null, 'datos' => [], 'emitido', 'oficina' => 'Coordinación Académica', 'imprimible' => false, 'horizontal' => false])
{{-- Formal report layout taken from the UNASAM SGA reports: letterhead, plain title with the period, issue time and code on the right, thin grey tables. DOMPDF reads tables and simple CSS only (ADR 0005). --}}
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}{{ $periodo ? " {$periodo}" : '' }}</title>
        <style>
            @page { size: A4 {{ $horizontal ? 'landscape' : 'portrait' }}; margin: 1.3cm 1.4cm 1.5cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.6pt; color: #212529; line-height: 1.35; }
            table { border-collapse: collapse; }
            .membrete { width: 100%; border-bottom: 0.6pt solid #dee2e6; margin-bottom: 16pt; }
            .membrete td { vertical-align: top; padding: 0 0 12pt; }
            .oficina { text-align: right; font-size: 10.5pt; line-height: 1.25; }
            .oficina span { font-size: 7.8pt; color: #6c757d; }
            .cabecera { width: 100%; margin-bottom: 14pt; }
            .cabecera td { vertical-align: bottom; padding: 0; }
            .titulo { font-size: 17pt; font-weight: normal; margin: 0 0 8pt; }
            .apagado { color: #6c757d; }
            .titulo .apagado { font-size: 12pt; }
            .emision { text-align: right; vertical-align: top !important; font-size: 7.8pt; line-height: 1.3; }
            .persona { font-size: 9pt; margin: 0 0 3pt; }
            .persona .apagado { font-size: 7.6pt; }
            .datos td { padding: 0 26pt 0 0; font-size: 7.6pt; }
            h2 { font-size: 14pt; font-weight: normal; margin: 16pt 0 5pt; page-break-after: avoid; }
            table.tabla-formal { width: 100%; border: 0.6pt solid #8f8f8f; }
            table.tabla-formal th, table.tabla-formal td { border: 0.6pt solid #8f8f8f; padding: 3.5pt 4pt; text-align: left; vertical-align: top; font-size: 7pt; }
            table.tabla-formal th { font-weight: bold; text-transform: uppercase; background: #f8f9fa; }
            table.tabla-formal td.etiqueta { font-weight: bold; width: 26%; }
            table.tabla-formal td.centro, table.tabla-formal th.centro { text-align: center; }
            table.tabla-formal td.separador { background: #8e8d93; color: #ffffff; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 0.4pt; }
            .texto { white-space: pre-line; margin: 0 0 4pt; }
            @if ($imprimible)
                body { max-width: {{ $horizontal ? '27cm' : '19cm' }}; margin: 1.2cm auto; }
                .imprimir { margin-bottom: 12pt; padding: 4pt 12pt; font: inherit; cursor: pointer; }
                @media print { .imprimir { display: none; } body { margin: 0 auto; } }
            @endif
        </style>
    </head>
    <body>
        @if ($imprimible)
            <button type="button" class="imprimir" onclick="window.print()">Imprimir</button>
        @endif

        <table class="membrete">
            <tr>
                <td><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png'))) }}" alt="Universidad Nacional Santiago Antúnez de Mayolo" style="width: 150pt; height: 39pt;"></td>
                <td class="oficina">Centro de Idiomas y Lenguas Nativas<br><span>{{ $oficina }}</span></td>
            </tr>
        </table>

        <table class="cabecera">
            <tr>
                <td>
                    <div class="titulo">{{ $titulo }}@if ($periodo) <span class="apagado">{{ $periodo }}</span>@endif</div>
                    @if ($persona)
                        <div class="persona">{{ $persona }}@if ($codigo) <span class="apagado">{{ $codigo }}</span>@endif</div>
                    @endif
                    @if ($datos !== [])
                        <table class="datos">
                            <tr>
                                @foreach ($datos as $etiqueta => $valor)
                                    <td><strong>{{ $etiqueta }}:</strong> {{ $valor }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif
                </td>
                <td class="emision">{{ $emitido }}@if ($codigo)<br>{{ $codigo }}@endif</td>
            </tr>
        </table>

        {{ $slot }}
    </body>
</html>
