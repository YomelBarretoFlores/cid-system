@props(['laterales' => '1%'])
{{-- Letterhead of the enrollment documents, with the measurements of the UNASAM SGA record: UNASAM logo 150×39 pt, institution, CILN emblem 36 pt high with its name, like the SGA office logo. Sizes go in pt because DOMPDF reads HTML widths as 96 dpi pixels. Equal side columns center the institution on the page, as the SGA record does; narrow pages shrink them to the logos. --}}
<table style="width: 100%; border-collapse: collapse; border-bottom: 0.6pt solid #dee2e6;">
    <tr>
        <td style="width: {{ $laterales }}; vertical-align: top; padding: 0 12pt 14pt 0;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png'))) }}" alt="" style="width: 150pt; height: 39pt;"></td>
        <td style="text-align: center; vertical-align: top; padding: 0 0 14pt; line-height: 1.1; font-size: 8.4pt; color: #212529; white-space: nowrap;">
            <span style="font-size: 10.8pt;">Universidad Nacional Santiago Antúnez de Mayolo</span><br>
            <span>Centro de Idiomas y Lenguas Nativas</span>
        </td>
        <td style="width: {{ $laterales }}; vertical-align: top; padding: 0 0 14pt 12pt;">
            <table style="border-collapse: collapse; margin-left: auto;">
                <tr>
                    <td style="padding: 0 4pt 0 0; vertical-align: middle;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png'))) }}" alt="" style="width: 49pt; height: 36pt;"></td>
                    <td style="padding: 0; vertical-align: middle; white-space: nowrap; line-height: 1.15; font-size: 6.6pt; color: #1d1b16;">Centro de Idiomas<br>y Lenguas Nativas<br><span style="font-size: 5.4pt; letter-spacing: 0.6pt; color: #8d5406;">UNASAM</span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
