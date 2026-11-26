@props(['firmas' => [], 'cargos' => ['Dirección del Centro de Idiomas y Lenguas Nativas'], 'espacio' => 46])
{{-- Signature lines over each signer's name and position, with space for the handwritten signature and seal; without signers, one line per default position. --}}
@php($lineas = $firmas === [] ? array_map(fn (string $cargo): array => ['nombre' => '', 'cargo' => $cargo], $cargos) : $firmas)
<table style="width: 100%; margin-top: {{ $espacio }}px; page-break-inside: avoid; border-collapse: collapse;">
    <tr>
        @foreach ($lineas as $firma)
            <td style="text-align: center; vertical-align: bottom; padding: 0 16px;">
                <div style="border-top: 1px solid #1d1b16; margin: 0 auto; width: 86%; padding-top: 5px; font-size: 9pt; font-weight: bold;">{{ $firma['nombre'] }}</div>
                <div style="font-size: 8pt; color: #4a463d;">{{ $firma['cargo'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
