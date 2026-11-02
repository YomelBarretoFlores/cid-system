@props(['href', 'titulo', 'detalle', 'descripcion'])
<li {{ $attributes }}>
    <a href="{{ $href }}" target="_blank" rel="noopener" class="group flex items-center gap-3 border-t border-separador py-3 focus-visible:shadow-foco focus-visible:outline-none">
        <span class="flex min-w-0 flex-1 flex-col leading-tight">
            <span class="font-medium text-tinta group-hover:underline">{{ $titulo }}</span>
            <span class="text-[13px] text-terciario">{{ $detalle }}</span>
        </span>
        <x-heroicon-o-arrow-down-tray aria-hidden="true" class="size-4 shrink-0 text-oro-600" />
        <span class="sr-only">PDF {{ $descripcion }}, se abre en una pestaña nueva</span>
    </a>
</li>
