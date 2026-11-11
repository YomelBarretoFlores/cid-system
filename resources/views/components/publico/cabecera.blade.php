@props(['secciones' => ['idiomas' => 'Idiomas', 'oferta' => 'Horarios', 'costos' => 'Costos', 'preguntas' => 'Preguntas']])
<header x-data="cabeceraCiln" x-bind:class="{ 'shadow-[0_6px_18px_rgb(10_46_82/0.08)]': desplazada || menu }"
    {{ $attributes->class('sticky top-0 z-30 border-b border-marino-900/12 bg-white transition-shadow duration-300') }}>
    <div class="bg-marino-900 text-[13px] text-celeste-100/85">
        <div class="mx-auto flex max-w-[1200px] items-center justify-between gap-4 px-4 py-1.5 sm:px-6 lg:px-8">
            <a href="https://www.unasam.edu.pe" class="truncate transition-colors hover:text-white">Universidad Nacional Santiago Antúnez de Mayolo</a>
            <nav aria-label="Accesos" class="flex shrink-0 items-center divide-x divide-white/20">
                <a href="{{ route('verificacion.documento') }}" class="hidden px-3 transition-colors hover:text-white sm:inline">Verificar documento</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="pl-3 transition-colors hover:text-white">Personal CILN</a>
            </nav>
        </div>
    </div>
    <div class="mx-auto flex max-w-[1200px] items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('portada') }}" class="flex shrink-0 items-center gap-3 text-marino-900">
            <img src="{{ asset('images/unasam/logo.png') }}" alt="UNASAM" width="800" height="215" class="hidden h-11 w-auto sm:block">
            <span aria-hidden="true" class="hidden h-10 w-px bg-marino-900/20 sm:block"></span>
            <x-ciln.emblema decorativo class="w-10" />
            <span class="flex flex-col leading-[1.15]">
                <span class="font-serif text-[17px] font-semibold">Centro de Idiomas</span>
                <span class="text-[12px] text-marino-800/75">y Lenguas Nativas · UNASAM</span>
            </span>
        </a>
        <nav aria-label="Secciones" class="ml-auto hidden text-[14.5px] whitespace-nowrap xl:flex">
            @foreach ($secciones as $ancla => $seccion)
                <a href="{{ route('portada') }}#{{ $ancla }}" class="border-b-2 border-transparent px-2.5 py-2 font-medium text-marino-900 transition-colors hover:border-real-600 hover:text-real-600">{{ $seccion }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex shrink-0 items-center gap-2 xl:ml-2">
            <x-publico.boton variante="claro" tamano="compacto" :href="route('ingresar')">Ingresar</x-publico.boton>
            <x-publico.boton tamano="compacto" :href="route('estudiante.registro')" class="max-sm:hidden">Crear cuenta</x-publico.boton>
            <button type="button" x-on:click="menu = ! menu" x-bind:aria-expanded="menu" aria-controls="menu-publico"
                class="flex size-10 items-center justify-center rounded-[3px] text-marino-900 transition-colors hover:bg-celeste-50 focus-visible:ring-3 focus-visible:ring-celeste-300 focus-visible:outline-none xl:hidden">
                <span class="sr-only">Menú</span>
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-5"><path x-show="! menu" d="M3.5 6h13M3.5 10h13M3.5 14h13" /><path x-show="menu" x-cloak d="m5 5 10 10M15 5 5 15" /></svg>
            </button>
        </div>
    </div>
    <nav id="menu-publico" x-show="menu" x-cloak x-on:click="menu = false" x-on:keydown.escape.window="menu = false" aria-label="Menú de la portada" class="border-t border-marino-900/10 xl:hidden">
        <div class="mx-auto flex max-w-[1200px] flex-col px-4 py-2 sm:px-6 lg:px-8">
            @foreach ($secciones as $ancla => $seccion)
                <a href="{{ route('portada') }}#{{ $ancla }}" class="border-b border-marino-900/8 py-3 font-medium text-marino-900 hover:text-real-600">{{ $seccion }}</a>
            @endforeach
            <a href="{{ route('estudiante.registro') }}" class="border-b border-marino-900/8 py-3 font-medium text-real-600 sm:hidden">Crear cuenta</a>
            <a href="{{ route('verificacion.documento') }}" class="py-3 font-medium text-marino-800 sm:hidden">Verificar documento</a>
        </div>
    </nav>
</header>
