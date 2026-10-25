@php($atajo = \Filament\Support\Enums\Platform::detect() === \Filament\Support\Enums\Platform::Mac ? '⌘ B' : 'Ctrl B')
<button type="button"
    x-data="{
        alternar() { $store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open() },
        esAtajo(evento) { return (evento.ctrlKey || evento.metaKey) && ! evento.shiftKey && ! evento.altKey && evento.key.toLowerCase() === 'b' && ! evento.target.isContentEditable },
    }"
    x-on:click="alternar()"
    x-on:keydown.window="if (esAtajo($event)) { $event.preventDefault(); alternar() }"
    data-atajo="mod+b"
    x-bind:aria-expanded="$store.sidebar.isOpen" x-bind:title="$store.sidebar.isOpen ? 'Contraer el menú (Ctrl o ⌘ + B)' : 'Expandir el menú (Ctrl o ⌘ + B)'"
    class="mb-3 hidden items-center gap-3 rounded-xl px-2.5 py-1.5 text-[14.5px] text-apagado hover:text-tinta focus-visible:shadow-foco focus-visible:outline-none lg:flex"
    x-bind:class="! $store.sidebar.isOpen && 'justify-center px-0 w-full'">
    <x-filament::icon icon="heroicon-o-chevron-double-left" class="size-[19px] transition-transform" x-bind:class="! $store.sidebar.isOpen && 'rotate-180'" />
    <span x-show="$store.sidebar.isOpen" class="flex flex-1 items-center justify-between gap-2">
        Contraer el menú
        <kbd class="font-sans text-[12px] text-apagado">{{ $atajo }}</kbd>
    </span>
    <span class="sr-only" x-show="! $store.sidebar.isOpen">Expandir el menú</span>
</button>
