<x-pdf.reporte :titulo="$reporte->titulo" :periodo="$reporte->periodo" :datos="$reporte->alcance" :emitido="$emitido" horizontal>
    @include('tablero::documentos.reporte-general')
</x-pdf.reporte>
