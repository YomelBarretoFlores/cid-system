@props(['idioma', 'texto' => null, 'size' => 20])
<span data-idioma="{{ $idioma }}" {{ $attributes->class('inline-flex min-w-0 items-center gap-2') }}>
    <x-ciln.hoja-bandera :idioma="$idioma" :size="$size" />
    <span class="truncate">{{ $texto ?? $idioma }}</span>
</span>
