@props(['estudiante', 'generadoEl'])
<table class="datos">
    <tr><th>Estudiante</th><td>{{ $estudiante->nombreCompleto }}</td></tr>
    <tr><th>Documento</th><td>{{ $estudiante->documento }}</td></tr>
    {{ $slot }}
    <tr><th>Generado el</th><td>{{ $generadoEl }}</td></tr>
</table>
