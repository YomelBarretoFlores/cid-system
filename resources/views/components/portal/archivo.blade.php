@props(['nombre', 'etiqueta', 'ayuda' => null, 'acepta' => null, 'formatos' => null])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$formatos ? "{$id}-formatos" : null, $ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    {{-- The real input stays in the tab order (visually hidden); the dashed area is its second label, so a click or Enter opens the file picker. --}}
    <div x-data="{ elegido: '' }" class="relative">
        <input id="{{ $id }}" type="file" wire:model="{{ $nombre }}"
            x-on:change="elegido = $event.target.files[0]?.name ?? ''"
            @if ($acepta) accept="{{ $acepta }}" @endif
            @if ($conError) aria-invalid="true" @endif
            @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
            {{ $attributes->class('peer sr-only') }}>
        <label for="{{ $id }}" @class([
            'flex cursor-pointer flex-col items-center gap-1 rounded-interna border border-dashed px-4 py-4 text-center transition-colors',
            'border-oro-400 bg-acento-claro hover:bg-oro-100' => ! $conError,
            'border-peligro bg-rojo-fondo' => $conError,
            'peer-focus-visible:shadow-foco',
        ])>
            <x-heroicon-o-arrow-up-tray aria-hidden="true" class="size-5 text-oro-700" />
            <span class="font-medium text-tinta" x-text="elegido || 'Elegir archivo'">Elegir archivo</span>
            @if ($formatos)
                <span id="{{ $id }}-formatos" class="text-[13px] text-terciario">{{ $formatos }}</span>
            @endif
        </label>
    </div>
    <p wire:loading wire:target="{{ $nombre }}" class="text-[13px] text-terciario" role="status">Cargando el archivo…</p>
</x-portal.control>
