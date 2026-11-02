@props(['secciones' => ['idiomas' => 'Idiomas', 'oferta' => 'Horarios', 'costos' => 'Costos', 'preguntas' => 'Preguntas', 'verificar' => 'Verificar']])
<header x-data="cabeceraCiln" x-on:focusin="oculta = false" x-bind:class="{ '-translate-y-[140%]': oculta && ! menu, 'shadow-[0_10px_30px_rgb(10_46_82/0.10)] bg-white/95': desplazada || menu }"
    {{ $attributes->class('sticky top-0 z-30 bg-papel/90 backdrop-blur-md transition-[translate,box-shadow,background-color] duration-500 ease-salida') }}>
    <div class="mx-auto flex max-w-[1200px] items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('portada') }}" class="flex shrink-0 items-center gap-2.5 text-marino-900">
            <x-ciln.emblema class="w-[44px]" />
            <span class="flex flex-col leading-[1.1]">
                <span class="text-[16px] font-bold tracking-[-0.01em]">Centro de Idiomas</span>
                <span class="text-[11.5px] font-medium tracking-[0.14em] text-real-600">UNASAM</span>
            </span>
        </a>
        <nav aria-label="Secciones" class="mx-auto hidden text-[14.5px] whitespace-nowrap xl:flex">
            @foreach ($secciones as $ancla => $seccion)
                <a href="{{ route('portada') }}#{{ $ancla }}" class="rounded-pildora px-3 py-2 font-medium text-marino-900 transition-colors hover:bg-celeste-100">{{ $seccion }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex shrink-0 items-center gap-1.5 xl:ml-0">
            <a href="{{ route('filament.admin.auth.login') }}" class="hidden items-center gap-1.5 rounded-pildora px-3 py-2.5 text-[14.5px] font-medium text-marino-800 transition-colors hover:bg-celeste-100 focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none lg:inline-flex">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4"><rect x="3" y="6.5" width="14" height="10" rx="2.5" /><path d="M7 6.5V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 13 5v1.5M3 11h14" /></svg>
                Personal CILN
            </a>
            <x-publico.boton variante="claro" tamano="compacto" :href="route('ingresar')">Ingresar</x-publico.boton>
            <x-publico.boton variante="oscuro" tamano="compacto" :href="route('estudiante.registro')" class="max-sm:hidden">Crear cuenta</x-publico.boton>
            <button type="button" x-on:click="menu = ! menu" x-bind:aria-expanded="menu" aria-controls="menu-publico"
                class="flex size-10 items-center justify-center rounded-full text-marino-900 transition-colors hover:bg-celeste-100 focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none xl:hidden">
                <span class="sr-only">Menú</span>
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-5"><path x-show="! menu" d="M3.5 6h13M3.5 10h13M3.5 14h13" /><path x-show="menu" x-cloak d="m5 5 10 10M15 5 5 15" /></svg>
            </button>
        </div>
    </div>
    <nav id="menu-publico" x-show="menu" x-cloak x-on:click="menu = false" x-on:keydown.escape.window="menu = false" aria-label="Menú de la portada"
        x-transition:enter="transition duration-300 ease-salida motion-reduce:transition-none" x-transition:enter-start="opacity-0 -translate-y-2"
        class="border-t border-marino-900/8 xl:hidden">
        <div class="mx-auto grid max-w-[1200px] grid-cols-2 gap-1 px-4 py-4 sm:grid-cols-3 sm:px-6 lg:px-8">
            @foreach ($secciones as $ancla => $seccion)
                <a href="{{ route('portada') }}#{{ $ancla }}" class="rounded-2xl px-3 py-2.5 font-medium text-marino-900 transition-colors hover:bg-celeste-100">{{ $seccion }}</a>
            @endforeach
            <a href="{{ route('estudiante.registro') }}" class="rounded-2xl px-3 py-2.5 font-medium text-real-600 transition-colors hover:bg-celeste-100 sm:hidden">Crear cuenta</a>
            <a href="{{ route('filament.admin.auth.login') }}" class="rounded-2xl px-3 py-2.5 font-medium text-marino-800 transition-colors hover:bg-celeste-100 lg:hidden">Personal CILN</a>
        </div>
    </nav>
</header>
