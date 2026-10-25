@props(['nombre', 'etiqueta', 'ayuda' => null, 'maximo' => 500, 'filas' => 3])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect(["{$id}-contador", $ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda" x-data="{ largo: 0 }" x-init="largo = $refs.texto.value.length">
    <textarea id="{{ $id }}" x-ref="texto" wire:model="{{ $nombre }}" x-on:input="largo = $event.target.value.length" rows="{{ $filas }}" maxlength="{{ $maximo }}"
        @if ($conError) aria-invalid="true" @endif
        aria-describedby="{{ $descripciones }}"
        {{ $attributes->class('control resize-y') }}></textarea>
    <span id="{{ $id }}-contador" class="self-end text-[12.5px] text-apagado"><span x-text="largo">0</span> de {{ $maximo }} caracteres</span>
</x-portal.control>
