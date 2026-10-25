@props(['codigo', 'qr', 'url', 'emitidoEl'])
<table style="width: 100%; margin-top: 26px; border-top: 1px solid #eae9e5; padding-top: 10px;">
    <tr>
        <td style="width: 96px; vertical-align: top;"><img src="{{ $qr }}" alt="" width="86" height="86"></td>
        <td style="vertical-align: top; font-size: 8.5pt; color: #5e5a50;">
            <strong style="color: #1d1b16;">Código único: {{ $codigo }}</strong><br>
            Fecha de emisión: {{ $emitidoEl }}<br>
            Verifica la autenticidad de este documento escaneando el código QR o en<br>
            <span style="color: #8d5406;">{{ $url }}</span>
        </td>
    </tr>
</table>
