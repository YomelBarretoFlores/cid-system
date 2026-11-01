@props(['firmas' => [], 'sello' => false, 'espacio' => 46])
{{-- Signature lines over each signer's name and position, with space for the handwritten signature; the dashed circle marks where the seal goes. --}}
@php($lineas = $firmas === [] ? [['nombre' => '', 'cargo' => 'Dirección del Centro de Idiomas y Lenguas Nativas']] : $firmas)
<table style="width: 100%; margin-top: {{ $espacio }}px; page-break-inside: avoid; border-collapse: collapse;">
    <tr>
        @if ($sello)
            <td style="width: 90px; text-align: center; vertical-align: middle;">
                <div style="width: 66px; height: 40px; padding-top: 26px; border: 1px dashed #9c968a; border-radius: 33px; margin: 0 auto; font-family: 'DejaVu Sans', sans-serif; font-size: 6.5pt; color: #9c968a; text-align: center;">Sello</div>
            </td>
        @endif
        @foreach ($lineas as $firma)
            <td style="text-align: center; vertical-align: bottom; padding: 0 16px;">
                <div style="border-top: 1px solid #1d1b16; margin: 0 auto; width: 86%; padding-top: 5px; font-size: 9pt; font-weight: bold;">{{ $firma['nombre'] }}</div>
                <div style="font-size: 8pt; color: #4a463d;">{{ $firma['cargo'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
