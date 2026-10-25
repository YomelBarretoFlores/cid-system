@props(['id', 'etiqueta', 'nombre', 'ayuda' => null])
<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    <label for="{{ $id }}" class="text-[14.5px] font-medium">{{ $etiqueta }}</label>
    {{ $slot }}
    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="text-[13px] text-terciario">{{ $ayuda }}</p>
    @endif
    @error($nombre)
        <p id="{{ $id }}-error" class="text-[13px] font-medium text-peligro">{{ $message }}</p>
    @enderror
</div>
