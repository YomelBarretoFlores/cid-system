<x-pdf.reporte
    titulo="Sílabo"
    :periodo="$silabo->periodo"
    :persona="$silabo->curso"
    :codigo="$silabo->grupo"
    :datos="['Docente' => $silabo->identificacion['Docente'] ?? 'Por asignar', 'Estado' => $silabo->estaPublicado() ? 'Publicado' : 'Borrador']"
    :emitido="now()->timezone('America/Lima')->format('Y-m-d H:i:s')"
    horizontal
>
    @unless ($silabo->estaPublicado())
        <p style="font-weight: bold; color: #6c757d; margin: -6pt 0 8pt;">BORRADOR · aún no publicado para los estudiantes</p>
    @endunless

    @include('docencia::documentos.silabo')

    <table style="width: 100%; margin-top: 46pt;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="text-align: center; border-top: 0.6pt solid #212529; padding-top: 4pt; font-size: 7.6pt;">
                {{ $silabo->identificacion['Docente'] ?? 'Por asignar' }}<br>Docente del curso
            </td>
        </tr>
    </table>
</x-pdf.reporte>
