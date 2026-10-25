@props(['nombre', 'etiqueta', 'tipo' => 'text', 'ayuda' => null])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    <input id="{{ $id }}" type="{{ $tipo }}" wire:model="{{ $nombre }}"
        @if ($conError) aria-invalid="true" @endif
        @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
        {{ $attributes->class('control') }}>
</x-portal.control>
