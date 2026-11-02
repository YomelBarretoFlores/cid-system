@props(['secciones' => ['idiomas' => 'Idiomas', 'oferta' => 'Horarios', 'costos' => 'Costos', 'fechas' => 'Fechas', 'preguntas' => 'Preguntas', 'verificar' => 'Verificar']])
<header x-data="cabeceraCiln" x-on:focusin="oculta = false" x-bind:class="{ '-translate-y-[140%]': oculta, 'shadow-[0_10px_30px_rgb(10_46_82/0.10)] bg-white/95': desplazada }"
    {{ $attributes->class('sticky top-0 z-30 border-b border-transparent bg-papel/90 backdrop-blur-md transition-[translate,box-shadow,background-color] duration-500 ease-salida') }}>
    <div class="mx-auto flex max-w-[1200px] items-center gap-3 px-4 py-3 sm:px-6">
        <a href="{{ route('portada') }}" class="flex shrink-0 items-center gap-2.5 text-marino-900">
            <x-ciln.emblema class="w-[46px]" />
            <span class="flex flex-col leading-[1.1]">
                <span class="text-[16.5px] font-bold tracking-[-0.01em]">Centro de Idiomas</span>
                <span class="text-[11.5px] font-medium tracking-[0.14em] text-real-600">UNASAM</span>
            </span>
        </a>
        <nav aria-label="Secciones" class="mx-auto hidden text-[14.5px] whitespace-nowrap lg:flex">
            @foreach ($secciones as $ancla => $seccion)
                <a href="{{ route('portada') }}#{{ $ancla }}" class="rounded-pildora px-3 py-2 font-medium text-marino-900 transition-colors hover:bg-celeste-100">{{ $seccion }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex shrink-0 items-center gap-1.5 lg:ml-0">
            <a href="{{ route('filament.admin.auth.login') }}" class="hidden items-center gap-1.5 rounded-pildora px-3 py-2.5 text-[14.5px] font-medium text-marino-800 transition-colors hover:bg-celeste-100 focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none md:inline-flex">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4"><rect x="3" y="6.5" width="14" height="10" rx="2.5" /><path d="M7 6.5V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 13 5v1.5M3 11h14" /></svg>
                Personal CILN
            </a>
            <x-publico.boton variante="claro" tamano="compacto" :href="route('ingresar')">Ingresar</x-publico.boton>
            <x-publico.boton variante="oscuro" tamano="compacto" :href="route('estudiante.registro')" class="max-sm:hidden">Crear cuenta</x-publico.boton>
        </div>
    </div>
</header>
