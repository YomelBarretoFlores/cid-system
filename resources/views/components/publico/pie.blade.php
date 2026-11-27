@props([
    'verificar' => true,
    'secciones' => null,
    'descripcion' => null,
    'contacto' => null,
    'redes' => null,
    'editar' => null,
])
@php
    // Pages that leave these out get the edited home footer from the shared site frame.
    if ($secciones === null || $descripcion === null || $contacto === null || $redes === null) {
        $marco = $marcoDelSitio->pie();
        $secciones ??= $marcoDelSitio->secciones()->enlaces();
        $descripcion ??= $marco['descripcion'];
        $contacto ??= $marco['contacto'];
        $redes ??= $marco['redes'];
    }
@endphp
<footer {{ $attributes->class('sobre-oscuro border-t-4 border-real-600 bg-marino-950 text-white') }}>
    <div @class(['mx-auto grid max-w-[1240px] grid-cols-[minmax(0,1fr)] gap-10 px-4 pt-14 pb-10 sm:px-6 lg:px-8', 'lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.3fr)]' => $verificar, 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]' => ! $verificar])>
        <div class="flex flex-col gap-4">
            <span class="flex items-center gap-3">
                <x-ciln.emblema blanco decorativo class="w-12" />
                <span class="flex flex-col leading-[1.2]">
                    <span class="font-display text-[18px] font-semibold">Centro de Idiomas y Lenguas Nativas</span>
                    <span class="text-[13px] text-celeste-100/75">Universidad Nacional Santiago Antúnez de Mayolo</span>
                </span>
            </span>
            <p class="max-w-[38ch] text-[14px] text-pretty text-celeste-100/75">{{ $descripcion }}</p>
            <x-publico.editar :atajo="$editar" claro />
            @if ($contacto !== [])
                <dl aria-label="Contacto" class="flex flex-col gap-1 text-[14px] text-celeste-100/75">
                    @foreach ($contacto as $dato => $valor)
                        <div><dt class="inline font-semibold text-celeste-100">{{ $dato }}:</dt> <dd class="inline">{{ $valor }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @if ($redes !== [])
                <nav aria-label="Redes del centro" class="flex flex-wrap gap-x-4 gap-y-1 text-[14px]">
                    @foreach ($redes as $red)
                        <a href="{{ $red['url'] }}" rel="noopener" class="text-celeste-100/80 transition-colors hover:text-white hover:underline">{{ $red['etiqueta'] }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-6 text-[14px]">
            <nav aria-label="Secciones de la portada" class="flex flex-col gap-2">
                <span class="mb-1 text-[12.5px] font-semibold tracking-[0.12em] text-celeste-200 uppercase">Portada</span>
                @foreach ($secciones as $ancla => $seccion)
                    <a href="{{ route('portada') }}#{{ $ancla }}" class="self-start text-celeste-100/80 transition-colors hover:text-white hover:underline">{{ $seccion }}</a>
                @endforeach
            </nav>
            <nav aria-label="Accesos del pie" class="flex flex-col gap-2">
                <span class="mb-1 text-[12.5px] font-semibold tracking-[0.12em] text-celeste-200 uppercase">Accesos</span>
                <a href="{{ route('ingresar') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white hover:underline">Portal del estudiante y docente</a>
                <a href="{{ route('estudiante.registro') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white hover:underline">Crear cuenta</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="self-start text-celeste-100/80 transition-colors hover:text-white hover:underline">Personal del CILN</a>
                <a href="https://www.unasam.edu.pe" class="self-start text-celeste-100/80 transition-colors hover:text-white hover:underline">UNASAM</a>
            </nav>
        </div>
        @if ($verificar)
            <section id="verificar" aria-labelledby="titulo-verificar" class="flex scroll-mt-32 flex-col gap-3">
                <h2 id="titulo-verificar" class="font-display text-[21px] leading-tight font-semibold">Verificación de constancias y certificados</h2>
                <p class="text-[14px] text-pretty text-celeste-100/75">Escriba el código impreso junto al QR, al pie del documento.</p>
                <form method="GET" action="{{ route('verificacion.documento') }}" class="flex flex-col gap-2">
                    <label for="codigo" class="sr-only">Código único del documento</label>
                    <div class="flex">
                        <input id="codigo" name="codigo" type="text" required maxlength="30" autocomplete="off" placeholder="CILN-XXXXXXXXXX"
                            class="min-w-0 flex-1 rounded-l-[3px] border border-white bg-white px-3.5 py-2.5 tracking-[0.03em] text-marino-900 uppercase placeholder:text-plata-300 focus:ring-3 focus:ring-celeste-400/60 focus:outline-none">
                        <button type="submit" class="rounded-r-[3px] border border-real-600 bg-real-600 px-4 py-2.5 text-[14.5px] font-semibold transition-colors hover:border-real-500 hover:bg-real-500">Verificar</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
    <div class="border-t border-white/12">
        <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-3 px-4 py-4 text-[13px] text-celeste-100/70 sm:px-6 lg:px-8">
            <span>© {{ now('America/Lima')->year }} Centro de Idiomas y Lenguas Nativas · UNASAM</span>
            <span class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ route('privacidad') }}" class="transition-colors hover:text-white hover:underline">Política de privacidad</a>
                <span>Huaraz, Perú</span>
            </span>
        </div>
    </div>
</footer>
