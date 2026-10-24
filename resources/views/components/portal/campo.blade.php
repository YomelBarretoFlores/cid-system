@props(['nombre', 'etiqueta', 'tipo' => 'text', 'ayuda' => null])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-medium text-slate-800">{{ $etiqueta }}</label>
    <input id="{{ $id }}" type="{{ $tipo }}" wire:model="{{ $nombre }}"
        @if ($conError) aria-invalid="true" @endif
        @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-base text-slate-900 shadow-xs focus:outline-2 focus:outline-offset-0 sm:text-sm',
            'border-red-600 focus:outline-red-600' => $conError,
            'border-slate-300 focus:outline-ciln-600' => ! $conError,
        ]) }}>
    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="text-xs text-slate-600">{{ $ayuda }}</p>
    @endif
    @error($nombre)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
