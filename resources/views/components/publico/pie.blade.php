@props(['verificar' => true, 'fondo' => 'var(--color-papel)', 'secciones' => ['idiomas' => 'Idiomas', 'oferta' => 'Horarios', 'costos' => 'Costos', 'preguntas' => 'Preguntas']])
<footer {{ $attributes->class('relative bg-marino-900 text-white') }}>
    <div aria-hidden="true" class="nubes absolute inset-x-0 -top-px h-10" style="--nube: {{ $fondo }}"></div>
    <div @class(['mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] gap-12 px-4 pt-24 pb-10 sm:px-6', 'lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.3fr)]' => $verificar, 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]' => ! $verificar])>
        <div class="flex flex-col gap-4">
            <span class="flex items-center gap-3">
                <x-ciln.emblema blanco decorativo class="w-14" />
                <span class="flex flex-col leading-[1.15]">
                    <span class="text-lg font-bold">Centro de Idiomas y Lenguas Nativas</span>
                    <span class="text-[13px] tracking-[0.12em] text-celeste-300">UNASAM</span>
                </span>
            </span>
            <p class="max-w-[34ch] text-[14.5px] text-pretty text-celeste-100/80">Universidad Nacional Santiago Antúnez de Mayolo · Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos presenciales y virtuales.</p>
        </div>
        <div class="grid grid-cols-2 gap-6 text-[14.5px]">
            <nav aria-label="Secciones de la portada" class="flex flex-col gap-2.5">
                <span class="mb-1 text-[15px] font-semibold">Portada</span>
                @foreach ($secciones as $ancla => $seccion)
                    <a href="{{ route('portada') }}#{{ $ancla }}" class="self-start text-celeste-100/80 transition-colors hover:text-white">{{ $seccion }}</a>
                @endforeach
            </nav>
            <nav aria-label="Accesos" class="flex flex-col gap-2.5">
                <span class="mb-1 text-[15px] font-semibold">Accesos</span>
                <a href="{{ route('ingresar') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white">Portal del estudiante y docente</a>
                <a href="{{ route('estudiante.registro') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white">Crear cuenta</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white">Personal del CILN</a>
            </nav>
        </div>
        @if ($verificar)
            <section id="verificar" aria-labelledby="titulo-verificar" class="flex scroll-mt-28 flex-col gap-3">
                <h2 id="titulo-verificar" class="text-[22px] leading-tight font-semibold text-balance">Verifica una constancia o certificado</h2>
                <p class="text-[14.5px] text-pretty text-celeste-100/80">Escanea el código QR del documento o escribe su código. Mostramos solo los datos mínimos.</p>
                <form method="GET" action="{{ route('verificacion.documento') }}" class="flex flex-col gap-2">
                    <label for="codigo" class="sr-only">Código único del documento</label>
                    <div class="flex gap-1.5 rounded-pildora bg-white p-1.5 focus-within:ring-4 focus-within:ring-celeste-400/60">
                        <input id="codigo" name="codigo" type="text" required maxlength="30" autocomplete="off" placeholder="CILN-XXXXXXXXXX"
                            class="min-w-0 flex-1 rounded-pildora border-0 bg-transparent px-4 py-2.5 tracking-[0.03em] text-marino-900 uppercase placeholder:text-plata-300 focus:outline-none">
                        <button type="submit" class="flex items-center gap-1.5 rounded-pildora bg-real-600 px-4 py-2.5 text-[14.5px] font-semibold transition-colors hover:bg-real-500 focus-visible:outline-none">
                            Verificar
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </button>
                    </div>
                    <p class="px-2 text-[13px] text-celeste-100/70">El código está junto al QR, al pie de cada constancia o certificado del CILN.</p>
                </form>
            </section>
        @endif
    </div>
    <div class="mx-auto max-w-[1200px] px-4 pb-8 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-[18px] bg-marino-950/60 px-6 py-3.5 sm:rounded-pildora text-[13.5px] text-celeste-100/75">
            <span>© {{ now('America/Lima')->year }} Centro de Idiomas y Lenguas Nativas · UNASAM</span>
            <span>Huaraz, Perú</span>
        </div>
    </div>
</footer>
