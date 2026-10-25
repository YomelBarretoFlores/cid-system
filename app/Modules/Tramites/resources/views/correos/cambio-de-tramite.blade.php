<x-mail::message>
# Hola, {{ $nombres }}

Tu trámite **{{ $tramite->tipo_nombre }}** ({{ $tramite->codigo }}) cambió de estado.

<x-mail::table>
| | |
|:--|:--|
| Estado | {{ $movimiento->estado->etiqueta() }} |
| Etapa | {{ $movimiento->etapa_nombre }} ({{ $tramite->etapas[$movimiento->etapa]['avance'] ?? 0 }} %) |
| Fecha | {{ $movimiento->created_at->timezone('America/Lima')->format('d/m/Y H:i') }} |
</x-mail::table>

@if ($movimiento->comentario)
{{ $movimiento->comentario }}
@endif

<x-mail::button :url="route('estudiante.tramites')">
Ver mi trámite
</x-mail::button>

Centro de Idiomas · Universidad Nacional Santiago Antúnez de Mayolo
</x-mail::message>
