@props(['firmas' => []])
{{-- Signature lines over each signer's name and position; space above for the handwritten signature and the seal. --}}
@php($lineas = $firmas === [] ? [['nombre' => '', 'cargo' => 'Dirección del Centro de Idiomas y Lenguas Nativas']] : $firmas)
<table style="width: 100%; margin-top: 52px; page-break-inside: avoid; border-collapse: collapse;">
    <tr>
        @foreach ($lineas as $firma)
            <td style="width: {{ intdiv(100, count($lineas)) }}%; text-align: center; vertical-align: top; padding: 0 18px;">
                <div style="border-top: 1px solid #1d1b16; margin: 0 auto; width: 82%; padding-top: 5px; font-size: 9pt; font-weight: bold;">{{ $firma['nombre'] }}</div>
                <div style="font-size: 8pt; color: #4a463d;">{{ $firma['cargo'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
