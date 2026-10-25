@props(['nombre', 'etiqueta', 'opciones', 'ayuda' => null, 'vacio' => null, 'vivo' => false])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    <div class="relative">
        <select id="{{ $id }}" {{ $vivo ? 'wire:model.live' : 'wire:model' }}="{{ $nombre }}"
            @if ($conError) aria-invalid="true" @endif
            @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
            {{ $attributes->class('control appearance-none pr-11') }}>
            @if ($vacio)
                <option value="">{{ $vacio }}</option>
            @endif
            @foreach ($opciones as $valor => $texto)
                <option value="{{ $valor }}">{{ $texto }}</option>
            @endforeach
        </select>
        <x-heroicon-o-chevron-down aria-hidden="true" class="pointer-events-none absolute top-1/2 right-4 size-4 -translate-y-1/2 text-apagado" />
    </div>
</x-portal.control>
