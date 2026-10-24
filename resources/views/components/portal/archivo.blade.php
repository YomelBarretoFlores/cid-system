@props(['nombre', 'etiqueta', 'ayuda' => null, 'acepta' => null])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-medium text-slate-800">{{ $etiqueta }}</label>
    <input id="{{ $id }}" type="file" wire:model="{{ $nombre }}"
        @if ($acepta) accept="{{ $acepta }}" @endif
        @if ($conError) aria-invalid="true" @endif
        @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white text-sm text-slate-900 shadow-xs file:mr-3 file:border-0 file:bg-ciln-50 file:px-3 file:py-2 file:font-medium file:text-ciln-900 hover:file:bg-ciln-100 focus:outline-2 focus:outline-offset-0',
            'border-red-600 focus:outline-red-600' => $conError,
            'border-slate-300 focus:outline-ciln-600' => ! $conError,
        ]) }}>
    <p wire:loading wire:target="{{ $nombre }}" class="text-xs text-slate-600" role="status">Cargando el archivo…</p>
    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="text-xs text-slate-600">{{ $ayuda }}</p>
    @endif
    @error($nombre)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
