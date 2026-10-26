{{-- Letterhead of the enrollment documents, after the UNASAM SGA record: UNASAM logo, institution, CILN emblem. --}}
<table style="width: 100%; border-bottom: 1px solid #eae9e5; padding-bottom: 8px;">
    <tr>
        <td style="width: 22%; vertical-align: middle;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png'))) }}" alt="" width="150"></td>
        <td style="text-align: center; vertical-align: middle; font-size: 10pt; color: #1d1b16; white-space: nowrap;">
            Universidad Nacional Santiago Antúnez de Mayolo<br>
            <span style="font-size: 8pt; color: #5e5a50;">Centro de Idiomas y Lenguas Nativas</span>
        </td>
        <td style="width: 22%; text-align: right; vertical-align: middle;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png'))) }}" alt="" width="46"></td>
    </tr>
</table>
