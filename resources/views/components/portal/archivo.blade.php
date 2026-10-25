@props(['nombre', 'etiqueta', 'ayuda' => null, 'acepta' => null])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    <input id="{{ $id }}" type="file" wire:model="{{ $nombre }}"
        @if ($acepta) accept="{{ $acepta }}" @endif
        @if ($conError) aria-invalid="true" @endif
        @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
        {{ $attributes->class('control py-2 pl-2 text-sm file:mr-3 file:rounded-pildora file:border-0 file:bg-acento-claro file:px-4 file:py-2 file:font-medium file:text-oro-900 hover:file:bg-oro-100') }}>
    <p wire:loading wire:target="{{ $nombre }}" class="text-[13px] text-terciario" role="status">Cargando el archivo…</p>
</x-portal.control>
