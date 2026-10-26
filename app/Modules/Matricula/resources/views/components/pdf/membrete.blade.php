@props(['laterales' => '1%'])
{{-- Letterhead of the enrollment documents, with the measurements of the UNASAM SGA record: UNASAM logo, institution, CILN emblem. Equal side columns center the institution on the page, as the SGA record does; narrow pages shrink them to the logos. --}}
<table style="width: 100%; border-collapse: collapse; border-bottom: 0.6pt solid #dee2e6;">
    <tr>
        <td style="width: {{ $laterales }}; vertical-align: top; padding: 0 12pt 14pt 0;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png'))) }}" alt="" width="150" height="40"></td>
        <td style="text-align: center; vertical-align: top; padding: 0 0 14pt; line-height: 1.1; font-size: 8.4pt; color: #212529; white-space: nowrap;">
            <span style="font-size: 10.8pt;">Universidad Nacional Santiago Antúnez de Mayolo</span><br>
            <span>Centro de Idiomas y Lenguas Nativas</span>
        </td>
        <td style="width: {{ $laterales }}; text-align: right; vertical-align: top; padding: 0 0 14pt 12pt;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png'))) }}" alt="" height="36"></td>
    </tr>
</table>
