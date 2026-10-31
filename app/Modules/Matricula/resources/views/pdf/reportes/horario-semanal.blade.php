<x-pdf.reporte
    titulo="Horario personalizado"
    :periodo="$periodo"
    :persona="$estudiante->nombreCompleto"
    :codigo="$estudiante->documento"
    :emitido="$generadoEl"
    :imprimible="$imprimible ?? false"
>
    @if ($horario->grupos === [])
        <p>No tiene clases programadas en este periodo.</p>
    @else
        @include('matricula::documentos.horario', ['pdf' => true])
    @endif
</x-pdf.reporte>
