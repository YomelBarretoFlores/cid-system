@props(['titulo'])
<div role="group" aria-label="{{ $titulo }}" class="flex flex-col gap-0.5">
    <span aria-hidden="true" class="px-2.5 pb-1 text-[13.5px] font-semibold text-tinta lg:in-data-colapsado:sr-only">{{ $titulo }}</span>
    {{ $slot }}
</div>
