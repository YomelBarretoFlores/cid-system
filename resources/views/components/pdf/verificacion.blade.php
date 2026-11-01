@props(['codigo', 'qr', 'url', 'emitidoEl'])
<table style="width: 100%; margin-top: 30px; border-top: 1px solid #9c968a; padding-top: 10px; font-family: 'DejaVu Sans', sans-serif; page-break-inside: avoid;">
    <tr>
        <td style="width: 84px; vertical-align: top;"><img src="{{ $qr }}" alt="" width="74" height="74"></td>
        <td style="vertical-align: top; font-size: 7.5pt; color: #4a463d; line-height: 1.5;">
            <strong style="color: #1d1b16;">Código único: {{ $codigo }}</strong><br>
            Verifique la autenticidad escaneando el código QR o en {{ $url }}<br>
            Documento emitido por el Sistema de Gestión Académica del CILN el {{ $emitidoEl }}.
        </td>
    </tr>
</table>
