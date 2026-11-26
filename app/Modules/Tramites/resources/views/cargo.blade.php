<x-pdf.reporte
    titulo="Cargo de trámite"
    :persona="$estudiante->nombreCompleto"
    :emitido="now('America/Lima')->format('d/m/Y H:i')"
    oficina="Mesa de partes"
    :imprimible="true"
>
    <table class="tabla-formal">
        <tbody>
            <tr><td class="etiqueta">Código de seguimiento</td><td>{{ $tramite->codigo }}</td></tr>
            <tr><td class="etiqueta">Trámite</td><td>{{ $tramite->tipo_nombre }}</td></tr>
            <tr><td class="etiqueta">Fecha y hora</td><td>{{ $tramite->created_at->timezone('America/Lima')->format('d/m/Y H:i') }}</td></tr>
            <tr><td class="etiqueta">Documento de identidad</td><td>{{ $estudiante->documento }}</td></tr>
            @foreach ($filas as $etiqueta => $valor)
                <tr><td class="etiqueta">{{ $etiqueta }}</td><td class="texto">{{ $valor }}</td></tr>
            @endforeach
            @isset($tramite->datos['comentario'])
                <tr><td class="etiqueta">Comentario</td><td class="texto">{{ $tramite->datos['comentario'] }}</td></tr>
            @endisset
            <tr><td class="etiqueta">Adjuntos</td><td>{{ $adjuntos }}</td></tr>
        </tbody>
    </table>
    <p class="nota">Conserve este cargo. Con el código de seguimiento puede consultar el avance en el portal del estudiante.</p>
</x-pdf.reporte>
