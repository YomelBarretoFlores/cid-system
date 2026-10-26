@php
    use App\Modules\Academico\Enums\Modalidad;
    use App\Shared\Ui\Formato;

    $primerIdioma = collect($idiomas)->firstWhere('grupos', '>', 0)['nombre'] ?? ($idiomas[0]['nombre'] ?? '');
    $primerVinculo = $vinculos[0]['clave'] ?? '';
    $nombresDeIdiomas = array_column($idiomas, 'nombre');
    $saludos = ['Inglés' => 'Hello', 'Quechua' => 'Allillanchu', 'Francés' => 'Bonjour', 'Italiano' => 'Ciao', 'Portugués' => 'Olá', 'Alemán' => 'Hallo', 'Chino' => 'Nǐ hǎo', 'Japonés' => 'Konnichiwa', 'Ruso' => 'Privet', 'Coreano' => 'Annyeong'];
    $palabrasDelTitular = array_map(mb_strtolower(...), $nombresDeIdiomas) ?: ['un idioma'];
    $manifiesto = explode(' ', 'Del quechua al inglés: matrícula, pagos y certificados del CILN, en un solo portal.');
    $conteos = collect($grupos)->groupBy('idioma')->map(fn ($deIdioma) => $deIdioma->countBy('modalidad')->put('Todas', $deIdioma->count()));
    $vacantes = fn ($grupo) => match (true) {
        ! $grupo->abierto => ['Próximamente', 'gray'],
        $grupo->vacantes === 0 => ['Sin vacantes', 'gray'],
        default => [$grupo->vacantes.($grupo->vacantes === 1 ? ' vacante' : ' vacantes'), $grupo->vacantes <= 5 ? 'warning' : 'success'],
    };
@endphp
<div x-data="{ idioma: @js($primerIdioma), modalidad: 'Todas', vinculo: @js($primerVinculo), conteos: @js($conteos) }" class="flex flex-col gap-3.5">
    <header x-data="cabeceraCiln" x-on:focusin="oculta = false" x-bind:class="{ '-translate-y-[140%]': oculta, 'shadow-[0_12px_32px_rgb(29_27_22/0.08)]': desplazada }"
        class="sticky top-2 z-20 flex items-center gap-3 rounded-[20px] bg-tarjeta/90 py-2.5 pr-3 pl-[18px] shadow-[0_1px_2px_rgb(29_27_22/0.04)] backdrop-blur-md transition-[translate,box-shadow] duration-500 ease-salida sm:top-4">
        <x-ciln.marca href="#inicio" emblema="w-[50px]" />
        <nav aria-label="Secciones" class="ml-auto hidden text-[14.5px] whitespace-nowrap lg:flex">
            @foreach (['oferta' => 'Oferta', 'costos' => 'Costos', 'fechas' => 'Fechas', 'requisitos' => 'Requisitos', 'verificar' => 'Verificar'] as $ancla => $seccion)
                @continue($ancla === 'fechas' && $fechas === [])
                <a href="#{{ $ancla }}" class="rounded-pildora px-[11px] py-2 text-tinta transition-colors hover:bg-lienzo">{{ $seccion }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex shrink-0 items-center gap-1.5 lg:ml-0">
            <a href="{{ route('filament.admin.auth.login') }}" class="hidden items-center gap-1.5 rounded-pildora px-3 py-2.5 text-[14.5px] font-medium text-secundario transition-colors hover:bg-lienzo hover:text-tinta focus-visible:shadow-foco focus-visible:outline-none md:inline-flex">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4"><rect x="3" y="6.5" width="14" height="10" rx="2.5" /><path d="M7 6.5V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 13 5v1.5M3 11h14" /></svg>
                Personal CILN
            </a>
            <x-portal.boton variante="secundario" tamano="compacto" :href="route('ingresar')">Ingresar</x-portal.boton>
            <x-portal.boton tamano="compacto" :href="route('estudiante.registro')" class="max-sm:hidden">Crear cuenta</x-portal.boton>
        </div>
    </header>

    <main id="contenido" x-data="revelarCiln" class="flex flex-col gap-3.5">
        <section id="inicio" class="relative grid grid-cols-[minmax(0,1fr)] items-center gap-10 overflow-hidden rounded-tarjeta bg-tarjeta p-[clamp(24px,5vw,72px)] lg:grid-cols-[minmax(0,1.25fr)_minmax(280px,1fr)]">
            <div class="relative flex flex-col gap-[22px]">
                @if ($oferta)
                    <span class="aparecer flex items-center gap-2 self-start rounded-pildora bg-acento-claro py-1.5 pr-3.5 pl-3 text-sm font-medium" style="--orden: -4">
                        <span class="relative flex size-2">
                            @if ($oferta->matriculaAbierta)
                                <span class="absolute inset-0 animate-ping rounded-full bg-positivo opacity-60 motion-reduce:hidden"></span>
                            @endif
                            <span @class(['relative size-2 rounded-full', 'bg-positivo' => $oferta->matriculaAbierta, 'bg-acento' => ! $oferta->matriculaAbierta])></span>
                        </span>
                        @if ($oferta->matriculaAbierta)
                            Matrícula {{ $oferta->periodo }} abierta hasta el {{ Formato::diaDeMes($oferta->matriculaFin) }}
                        @else
                            Matrícula {{ $oferta->periodo }} desde el {{ Formato::diaDeMes($oferta->matriculaInicio) }}
                        @endif
                    </span>
                @endif
                {{-- Screen readers get this sentence; the visible headline below rotates the catalog languages in its place. Keep both in step. --}}
                <h1 class="sr-only">Aprende un idioma en la UNASAM.</h1>
                <p aria-hidden="true" class="text-[clamp(44px,6.4vw,92px)] leading-[0.98] font-semibold tracking-[-0.045em]">
                    <span class="linea-mascara" style="--orden: 0"><span>Aprende</span></span>
                    <span class="linea-mascara" style="--orden: 1">
                        <span>
                            <span x-data="rotadorCiln" class="rotador text-oro-600">
                                @foreach ($palabrasDelTitular as $palabra)
                                    <span x-bind:class="clase({{ $loop->index }})" @class(['actual' => $loop->first])>{{ $palabra }}</span>
                                @endforeach
                            </span>
                        </span>
                    </span>
                    <span class="linea-mascara" style="--orden: 2"><span>en la UNASAM.</span></span>
                </p>
                <p class="aparecer max-w-[540px] text-[clamp(17px,1.5vw,20px)] text-pretty text-secundario" style="--orden: 0">
                    {{ $nombresDeIdiomas === [] ? 'Idiomas extranjeros y lenguas originarias' : Illuminate\Support\Arr::join($nombresDeIdiomas, ', ', ' y ') }} en ciclos presenciales y virtuales. Matrícula, pagos y certificados desde el portal.
                </p>
                <div class="aparecer flex flex-wrap gap-2.5" style="--orden: 1">
                    <x-portal.boton tamano="grande" href="#oferta" class="group">Ver horarios <span aria-hidden="true" class="transition-transform duration-300 ease-salida group-hover:translate-x-1">→</span></x-portal.boton>
                    <x-portal.boton variante="secundario" tamano="grande" href="#costos">Consultar costos</x-portal.boton>
                </div>
            </div>
            <div aria-hidden="true" class="relative hidden aspect-[1/0.9] min-h-[300px] sm:block">
                <div class="aura absolute inset-[6%_4%_0_8%] rounded-[40px] bg-[radial-gradient(120%_90%_at_30%_20%,var(--color-oro-100),var(--color-acento-claro)_55%,transparent)]"></div>
                <x-ciln.emblema decorativo class="aparecer absolute right-[6%] bottom-[6%] left-[14%] w-4/5" style="--orden: -2" />
                @foreach ([['Inglés', 12, 6, 56, -24], ['Quechua', 66, 2, 52, 18], ['Francés', 2, 32, 48, -34], ['Alemán', 80, 24, 44, 10], ['Italiano', 8, 58, 46, -14], ['Portugués', 84, 50, 48, 22]] as [$hoja, $x, $y, $tamano, $giro])
                    <span class="flotar absolute drop-shadow-[0_4px_8px_rgb(29_27_22/0.12)]" style="left: {{ $x }}%; top: {{ $y }}%; --orden: {{ $loop->index }}">
                        <x-ciln.hoja-bandera :idioma="$hoja" :size="$tamano" :rot="$giro" forma="hoja" />
                    </span>
                @endforeach
            </div>
        </section>

        @if ($idiomas !== [])
            <div aria-hidden="true" class="ticker overflow-hidden rounded-tarjeta bg-tinta py-5 text-white">
                {{-- Two identical copies: the ticker keyframe moves the track by -50% to loop without a seam. --}}
                <div class="ticker-pista">
                    @foreach ([1, 2] as $copia)
                        @foreach (array_merge($idiomas, $idiomas, $idiomas) as $idioma)
                            <span class="flex shrink-0 items-center gap-3 px-7">
                                <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="34" />
                                <span class="text-[clamp(22px,2.4vw,30px)] font-medium tracking-[-0.02em]">{{ $saludos[$idioma['nombre']] ?? $idioma['nombre'] }}</span>
                                <span class="text-sm text-piedra-400">{{ $idioma['nombre'] }}</span>
                                <span class="ml-4 size-1.5 rounded-full bg-oro-400"></span>
                            </span>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            <div data-revelar class="flex flex-col gap-1.5 rounded-tarjeta bg-tarjeta px-7 py-[26px]" style="--orden: 0">
                <span x-data="contadorCiln" class="text-[clamp(36px,4vw,48px)] leading-none font-medium tracking-[-0.04em] tabular-nums">{{ count($idiomas) }}</span>
                <span class="text-secundario">{{ in_array('Quechua', $nombresDeIdiomas, true) ? 'idiomas, incluido Quechua' : 'idiomas' }}</span>
            </div>
            <div data-revelar class="flex flex-col gap-1.5 rounded-tarjeta bg-tarjeta px-7 py-[26px]" style="--orden: 1">
                <span x-data="contadorCiln" class="text-[clamp(36px,4vw,48px)] leading-none font-medium tracking-[-0.04em] tabular-nums">{{ $gruposConVacantes }}</span>
                <span class="text-secundario">{{ $gruposConVacantes === 1 ? 'grupo con vacantes' : 'grupos con vacantes' }}</span>
            </div>
            <div data-revelar class="flex flex-col gap-1.5 rounded-tarjeta bg-tarjeta px-7 py-[26px]" style="--orden: 2">
                <span class="text-[clamp(36px,4vw,48px)] leading-none font-medium tracking-[-0.04em]">I–XII</span>
                <span class="text-secundario">Básico, Intermedio y Avanzado</span>
            </div>
            @if ($oferta)
                <div data-revelar class="flex flex-col gap-1.5 rounded-tarjeta bg-tarjeta px-7 py-[26px]" style="--orden: 3">
                    <span class="text-[clamp(36px,4vw,48px)] leading-none font-medium tracking-[-0.04em] text-oro-800">{{ Formato::diaYMes($oferta->clasesInicio) }}</span>
                    <span class="font-medium text-oro-600">inicio de clases</span>
                </div>
            @endif
        </div>

        <section aria-label="El CILN en una frase" class="relative overflow-hidden rounded-tarjeta bg-tarjeta px-[clamp(24px,6vw,96px)] py-[clamp(48px,9vw,128px)]">
            <x-ciln.emblema decorativo class="pointer-events-none absolute top-1/2 -right-[4%] hidden w-[38%] -translate-y-1/2 opacity-[0.08] lg:block" />
            <p class="relative max-w-[22ch] text-[clamp(30px,4.6vw,64px)] leading-[1.06] font-semibold tracking-[-0.035em] text-balance">
                @foreach ($manifiesto as $palabra)
                    <span class="palabra" style="--i: {{ $loop->index }}; --n: {{ $loop->count }}">{{ $palabra }}</span>
                @endforeach
            </p>
        </section>

        <section id="oferta" data-revelar aria-labelledby="titulo-oferta" class="flex scroll-mt-28 flex-col gap-7 rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,48px)]">
            <div class="flex flex-wrap items-end justify-between gap-5">
                <div class="flex flex-col gap-1.5">
                    <span class="font-semibold text-enlace">{{ $oferta ? "Oferta {$oferta->periodo}" : 'Oferta' }}</span>
                    <h2 id="titulo-oferta" class="text-[clamp(30px,3.4vw,44px)] font-semibold tracking-[-0.03em]">Elige idioma y horario</h2>
                </div>
                @if ($grupos !== [])
                    <div class="max-w-full overflow-x-auto">
                        <div role="group" aria-label="Modalidad" x-data="indicadorCiln" class="group relative flex w-max gap-1 rounded-pildora bg-lienzo p-1">
                            <span aria-hidden="true" x-bind:style="estilo" class="indicador absolute top-0 left-0 rounded-pildora bg-tinta"></span>
                            @foreach (['Todas', ...array_map(fn (Modalidad $modalidad): string => $modalidad->etiqueta(), Modalidad::cases())] as $modalidad)
                                <button type="button" x-on:click="modalidad = @js($modalidad)" x-bind:aria-pressed="modalidad === @js($modalidad)"
                                    class="relative rounded-pildora px-4 py-[9px] text-[14.5px] font-medium transition-colors duration-300 aria-pressed:bg-tinta aria-pressed:text-white group-data-listo:aria-pressed:bg-transparent" @if ($loop->first) aria-pressed="true" @endif>{{ $modalidad }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($grupos === [])
                <x-portal.vacio titulo="La oferta del próximo periodo se publicará pronto.">Mientras tanto, crea tu cuenta para matricularte cuando abra la matrícula.</x-portal.vacio>
            @else
                <div role="group" aria-label="Idioma" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($idiomas as $idioma)
                        <button type="button" x-on:click="idioma = @js($idioma['nombre'])" x-bind:aria-pressed="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] === $primerIdioma) aria-pressed="true" @endif
                            class="flex flex-col items-start gap-3 rounded-[20px] bg-tarjeta-interna px-3.5 py-4 text-left shadow-[inset_0_0_0_1px_var(--color-borde-interno)] transition-[translate,background-color,box-shadow] duration-300 ease-salida hover:-translate-y-1 aria-pressed:bg-fila-hover aria-pressed:shadow-[inset_0_0_0_2px_var(--color-acento)] motion-reduce:hover:translate-y-0">
                            <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="44" />
                            <span class="flex flex-col">
                                <span class="text-lg font-semibold">{{ $idioma['nombre'] }}</span>
                                <span class="text-[13.5px] text-terciario">{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo' : 'grupos' }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="rounded-[20px] bg-tarjeta-interna p-2 shadow-[inset_0_0_0_1px_var(--color-borde-interno)]">
                    <div class="flex flex-wrap justify-between gap-3 px-[18px] pt-4 pb-3">
                        @foreach ($idiomas as $idioma)
                            <span x-show="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] !== $primerIdioma) x-cloak @endif class="flex flex-col">
                                <span class="text-lg font-semibold">{{ $idioma['nombre'] }}</span>
                                <span class="text-terciario">{{ $idioma['ciclos'] > 0 ? 'Ciclos I a '.Formato::romano($idioma['ciclos']) : 'Ciclos por publicar' }}</span>
                            </span>
                        @endforeach
                        <span class="self-center text-[13.5px] text-apagado">Vacantes al {{ Formato::diaYMes($ahora) }}, {{ $ahora->format('H:i') }}</span>
                    </div>
                    @foreach ($grupos as $grupo)
                        @php([$textoVacantes, $colorVacantes] = $vacantes($grupo))
                        <div x-show="idioma === @js($grupo->idioma) && (modalidad === 'Todas' || modalidad === @js($grupo->modalidad))" @if ($grupo->idioma !== $primerIdioma) x-cloak @endif
                            x-transition:enter="transition duration-500 ease-salida motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-2"
                            class="mt-1.5 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 rounded-[14px] bg-tarjeta px-[18px] py-3.5 md:grid-cols-[100px_minmax(0,1.5fr)_minmax(0,1fr)_auto]">
                            <span class="font-semibold">Ciclo {{ Formato::romano($grupo->cicloNumero) }}</span>
                            <span class="col-start-1 flex flex-col md:col-start-auto">
                                <span class="font-medium">{{ implode(' / ', $grupo->horario) ?: 'Horario por publicar' }}</span>
                                <span class="text-[13.5px] text-apagado">{{ $grupo->nivel }}{{ $grupo->esAcelerado ? ' · acelerado' : '' }}</span>
                            </span>
                            <span class="col-start-1 text-secundario md:col-start-auto">{{ $grupo->modalidad }}</span>
                            <x-portal.insignia :color="$colorVacantes" class="col-start-2 row-span-3 row-start-1 self-center md:col-start-auto md:row-span-1 md:row-start-auto">{{ $textoVacantes }}</x-portal.insignia>
                        </div>
                    @endforeach
                    <p x-show="! ((conteos[idioma] ?? {})[modalidad] ?? 0)" x-cloak class="px-[18px] py-6 text-apagado">
                        No hay grupos de <span x-text="idioma"></span> en esta modalidad. Prueba con otra.
                    </p>
                    <p class="px-[18px] pt-3.5 pb-2.5 text-[15px] text-secundario">
                        ¿Ya sabes el idioma? Rinde el examen de clasificación desde el <a href="{{ route('ingresar') }}" class="enlace font-medium">portal del estudiante</a> y empieza en un ciclo superior.
                    </p>
                </div>
            @endif
        </section>

        <section id="costos" aria-labelledby="titulo-costos" class="grid scroll-mt-28 grid-cols-[minmax(0,1fr)] gap-3.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
            <div data-revelar class="relative flex flex-col gap-4 overflow-hidden rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,44px)]">
                <x-ciln.emblema decorativo class="pointer-events-none absolute -right-[70px] -bottom-[60px] w-[280px] opacity-[0.07]" />
                <span class="font-semibold text-enlace">Costos TUPA / TUSNE</span>
                <h2 id="titulo-costos" class="text-[clamp(28px,3vw,40px)] font-semibold tracking-[-0.03em] text-balance">La tarifa depende de tu vínculo con la UNASAM</h2>
                <p class="text-pretty text-secundario">Paga en el Banco de la Nación, Págalo.pe o la caja de la universidad y registra tu recibo en el portal.</p>
                @if ($vinculos !== [])
                    <div role="group" aria-label="Vínculo con la UNASAM" class="relative mt-1.5 flex flex-col gap-2">
                        @foreach ($vinculos as $vinculo)
                            <button type="button" x-on:click="vinculo = @js($vinculo['clave'])" x-bind:aria-pressed="vinculo === @js($vinculo['clave'])" @if ($loop->first) aria-pressed="true" @endif
                                class="group flex items-center justify-between rounded-interna bg-lienzo px-[18px] py-3.5 text-left font-medium transition-colors duration-300 aria-pressed:bg-tinta aria-pressed:text-white">
                                {{ $vinculo['etiqueta'] }}
                                <span class="flex size-5 items-center justify-center rounded-full shadow-[inset_0_0_0_2px_var(--color-piedra-300)] group-aria-pressed:shadow-[inset_0_0_0_2px_#fff]">
                                    <span class="size-2 rounded-full group-aria-pressed:bg-oro-300"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div data-revelar class="flex flex-col gap-3 rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,44px)]" style="--orden: 1">
                @forelse ($vinculos as $vinculo)
                    <div x-show="vinculo === @js($vinculo['clave'])" @unless ($loop->first) x-cloak @endunless x-transition:enter="transition duration-500 ease-salida motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-2" class="flex flex-col gap-3">
                        @foreach ($vinculo['tarifas'] as $tarifa)
                            <div @class(['grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 rounded-interna px-[18px] py-4', 'bg-acento-claro' => $loop->first, 'bg-tarjeta-interna' => ! $loop->first])>
                                <span class="flex flex-col">
                                    <span class="text-[17px] font-semibold">{{ $tarifa->concepto }}</span>
                                    <span class="text-[13.5px] text-terciario">{{ $tarifa->codigo }} · {{ $tarifa->fuente->etiqueta() }}</span>
                                </span>
                                <span class="text-[26px] font-medium tracking-[-0.03em] whitespace-nowrap">S/ {{ $tarifa->monto }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <x-portal.vacio titulo="Las tarifas se publicarán pronto." />
                @endforelse
                @if ($tarifasDesde)
                    <span class="px-1 pt-1 text-[13.5px] text-apagado">Tarifas vigentes desde el {{ Formato::fechaLarga($tarifasDesde) }}.</span>
                @endif
            </div>
        </section>

        @if ($fechas !== [])
            <section id="fechas" data-revelar aria-labelledby="titulo-fechas" class="flex scroll-mt-28 flex-col gap-6 rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,48px)]">
                <div class="flex flex-col gap-1.5">
                    <span class="font-semibold text-enlace">Calendario {{ $oferta?->periodo }}</span>
                    <h2 id="titulo-fechas" class="text-[clamp(30px,3.4vw,44px)] font-semibold tracking-[-0.03em]">Fechas del periodo</h2>
                </div>
                <ol class="grid grid-cols-[minmax(0,1fr)] gap-3 sm:grid-cols-3">
                    @foreach ($fechas as $fecha)
                        <li @class(['flex flex-col gap-2.5 rounded-[20px] p-[22px]', 'bg-acento-claro' => $fecha['color'] === 'warning', 'bg-tarjeta-interna' => $fecha['color'] !== 'warning'])>
                            <x-portal.insignia :color="$fecha['color']" class="self-start">{{ $fecha['estado'] }}</x-portal.insignia>
                            <span @class(['text-[28px] font-semibold tracking-[-0.03em]', 'text-oro-800' => $fecha['color'] === 'warning', 'text-apagado' => $fecha['color'] === 'gray'])>{{ $fecha['fecha'] }}</span>
                            <span class="text-pretty text-secundario">{{ $fecha['detalle'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section id="requisitos" aria-labelledby="titulo-requisitos" class="flex scroll-mt-28 flex-col gap-8 rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,48px)]">
            <div data-revelar class="flex flex-col gap-1.5">
                <span class="font-semibold text-enlace">Cómo matricularte</span>
                <h2 id="titulo-requisitos" class="text-[clamp(30px,3.4vw,44px)] font-semibold tracking-[-0.03em]">Cuatro pasos, todo en línea</h2>
            </div>
            <div class="relative">
                <span aria-hidden="true" class="trazo absolute top-[22px] right-[12%] left-[22px] hidden h-0.5 rounded-full bg-linear-to-r from-oro-400 to-oro-200 lg:block"></span>
                <ol class="relative grid grid-cols-[minmax(0,1fr)] gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Crea tu cuenta', 'Regístrate con tu documento de identidad y tu correo personal, y confirma el enlace que te enviamos.'],
                        ['Paga la matrícula', 'Paga el derecho de matrícula de tu vínculo en el Banco de la Nación, Págalo.pe o la caja de la UNASAM.'],
                        ['Registra tu recibo', 'Súbelo en Mis pagos con el número de operación. El CILN lo revisa y lo valida.'],
                        ['Elige tu grupo', 'Ve los horarios con vacantes de tu ciclo y confirma. Descarga tu ficha de matrícula al instante.'],
                    ] as [$paso, $detalle])
                        <li data-revelar class="flex flex-col gap-3.5" style="--orden: {{ $loop->index }}">
                            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-control bg-tinta font-bold text-oro-300 ring-8 ring-tarjeta">{{ $loop->iteration }}</span>
                            <span class="text-[19px] font-semibold">{{ $paso }}</span>
                            <span class="text-pretty text-secundario">{{ $detalle }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section id="verificar" data-revelar aria-labelledby="titulo-verificar" class="relative grid scroll-mt-28 grid-cols-[minmax(0,1fr)] items-start gap-10 overflow-hidden rounded-tarjeta bg-tarjeta p-[clamp(20px,4vw,48px)] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <x-ciln.emblema decorativo class="pointer-events-none absolute -bottom-20 -left-[60px] w-[300px] opacity-[0.06]" />
            <div class="relative flex flex-col gap-3">
                <span class="font-semibold text-enlace">Verificación pública</span>
                <h2 id="titulo-verificar" class="text-[clamp(28px,3vw,40px)] font-semibold tracking-[-0.03em] text-balance">Verifica una constancia o certificado</h2>
                <p class="text-pretty text-secundario">Escanea el código QR del documento o escribe su código. Mostramos solo los datos mínimos.</p>
            </div>
            <form method="GET" action="{{ route('verificacion.documento') }}" class="relative flex flex-col gap-3">
                <label for="codigo" class="sr-only">Código único del documento</label>
                <div class="flex gap-2 rounded-pildora bg-lienzo p-1.5">
                    <input id="codigo" name="codigo" type="text" required maxlength="30" autocomplete="off" placeholder="CILN-XXXXXXXXXX"
                        class="min-w-0 flex-1 rounded-pildora border-0 bg-transparent px-[18px] py-3 tracking-[0.03em] uppercase focus:shadow-foco focus:outline-none">
                    <x-portal.boton>Verificar</x-portal.boton>
                </div>
                <p class="px-2 text-[13px] text-apagado">El código está junto al QR, al pie de cada constancia o certificado del CILN.</p>
            </form>
        </section>
    </main>

    <footer class="flex flex-wrap items-center justify-between gap-4 rounded-tarjeta bg-tarjeta px-[26px] py-[22px] text-sm text-secundario">
        <span class="flex items-center gap-3">
            <x-ciln.emblema decorativo class="w-10" />
            Centro de Idiomas · Universidad Nacional Santiago Antúnez de Mayolo · Huaraz
        </span>
        <nav aria-label="Accesos" class="flex flex-wrap gap-[18px]">
            <a href="{{ route('ingresar') }}" class="enlace">Portal del estudiante y docente</a>
            <a href="{{ route('filament.admin.auth.login') }}" class="enlace">Personal del CILN</a>
        </nav>
    </footer>
</div>
