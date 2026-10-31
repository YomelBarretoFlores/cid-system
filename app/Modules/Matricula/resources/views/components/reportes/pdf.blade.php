@props(['href', 'descripcion'])
<x-portal.boton variante="secundario" :href="$href" target="_blank" rel="noopener" tamano="compacto">
    <x-heroicon-o-document-arrow-down aria-hidden="true" class="size-4" />
    {{ $slot->isEmpty() ? 'PDF' : $slot }}<span class="sr-only"> {{ $descripcion }}</span>
</x-portal.boton>
