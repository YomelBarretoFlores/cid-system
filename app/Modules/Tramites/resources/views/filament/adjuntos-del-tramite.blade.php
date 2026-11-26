@php($tramite = $getRecord()->load('adjuntos'))
<div class="overflow-x-auto">
    <table class="tabla-reporte">
        <thead>
            <tr><th scope="col">Adjunto</th><th scope="col">Tipo</th><th scope="col">Tamaño</th><th scope="col"><span class="sr-only">Descarga</span></th></tr>
        </thead>
        <tbody>
            @foreach ($tramite->adjuntos as $adjunto)
                <tr>
                    <td>{{ $adjunto->nombre }}</td>
                    <td>{{ strtoupper(pathinfo($adjunto->path, PATHINFO_EXTENSION)) }}</td>
                    <td class="whitespace-nowrap">{{ number_format($adjunto->bytes / 1024, 0) }} KB</td>
                    <td><a href="{{ route('panel.tramites.adjuntos', $adjunto->ulid) }}" class="underline" target="_blank" rel="noopener">Descargar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
