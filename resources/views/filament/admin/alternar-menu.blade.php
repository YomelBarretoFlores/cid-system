<button type="button" x-data x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
    x-bind:aria-expanded="$store.sidebar.isOpen" x-bind:title="$store.sidebar.isOpen ? null : 'Expandir el menú'"
    class="mb-3 hidden items-center gap-3 rounded-xl px-2.5 py-1.5 text-[14.5px] text-apagado hover:text-tinta focus-visible:shadow-foco focus-visible:outline-none lg:flex"
    x-bind:class="! $store.sidebar.isOpen && 'justify-center px-0 w-full'">
    <x-filament::icon icon="heroicon-o-chevron-double-left" class="size-[19px] transition-transform" x-bind:class="! $store.sidebar.isOpen && 'rotate-180'" />
    <span x-show="$store.sidebar.isOpen">Contraer el menú</span>
    <span class="sr-only" x-show="! $store.sidebar.isOpen">Expandir el menú</span>
</button>
