@php
    use App\Modules\Academico\Enums\Modalidad;
    use App\Shared\Ui\BanderaDeIdioma;
    use App\Shared\Ui\BanderasDeIdiomas;
    use App\Shared\Ui\Formato;

    $primerIdioma = collect($idiomas)->firstWhere('grupos', '>', 0)['nombre'] ?? ($idiomas[0]['nombre'] ?? '');
    $nombresDeIdiomas = array_column($idiomas, 'nombre');
    $nombresConBandera = $nombresDeIdiomas ?: ['Inglés', 'Quechua'];
    $banderas = app(BanderasDeIdiomas::class);
    $saludos = ['Inglés' => 'Hello', 'Quechua' => 'Allillanchu', 'Francés' => 'Bonjour', 'Italiano' => 'Ciao', 'Portugués' => 'Olá', 'Alemán' => 'Hallo', 'Chino' => 'Nǐ hǎo', 'Japonés' => 'Konnichiwa', 'Ruso' => 'Privet', 'Coreano' => 'Annyeong'];
    $palabrasDelTitular = $nombresDeIdiomas === []
        ? [['un idioma', BanderaDeIdioma::Neutra->fondo()]]
        : array_map(fn (string $nombre): array => [mb_strtolower($nombre), $banderas->para($nombre)->fondo()], $nombresDeIdiomas);
    $modalidades = Modalidad::cases();
    $etiquetasDeModalidad = array_map(fn (Modalidad $modalidad): string => $modalidad->etiqueta(), $modalidades);
    $detallesDeModalidad = [
        'presencial' => ['En las aulas del CILN, con tu grupo y tu docente.', 'M3 16.5h14M4.5 16.5V8l5.5-4 5.5 4v8.5M8 16.5v-4h4v4'],
        'virtual' => ['Clases en línea con el enlace de tu grupo, desde donde estés.', 'M3.5 5.5h13v8h-13zM7.5 16.5h5M10 13.5v3'],
        'semipresencial' => ['Combina sesiones en aula y sesiones en línea.', 'M2.5 15.5h6v-4L5.5 9l-3 2.5zM10.5 7.5h7v6h-7zM12.5 15.5h3'],
    ];
    $tintes = ['bg-hoja-celeste', 'bg-hoja-verde', 'bg-hoja-rojo', 'bg-hoja-ambar', 'bg-hoja-lila'];
    $conteos = collect($grupos)->groupBy('idioma')->map(fn ($deIdioma) => $deIdioma->countBy('modalidad')->put('Todas', $deIdioma->count()));
    $vacantes = fn ($grupo) => match (true) {
        ! $grupo->abierto => ['Próximamente', 'gray'],
        $grupo->vacantes === 0 => ['Sin vacantes', 'gray'],
        default => [$grupo->vacantes.($grupo->vacantes === 1 ? ' vacante' : ' vacantes'), $grupo->vacantes <= 5 ? 'warning' : 'success'],
    };
    $secciones = array_filter(
        ['idiomas' => 'Idiomas', 'oferta' => 'Horarios', 'costos' => 'Costos', 'fechas' => 'Fechas', 'preguntas' => 'Preguntas'],
        fn (string $ancla): bool => $ancla !== 'fechas' || $fechas !== [],
        ARRAY_FILTER_USE_KEY,
    );
    $preguntas = [
        ['¿Cómo me matriculo?', 'Crea tu cuenta, paga el derecho de matrícula de tu vínculo, registra el recibo en Registro de pagos y, cuando el CILN lo valide, elige un grupo con vacantes. Tu ficha de matrícula se descarga al instante.'],
        ['¿Dónde pago?', 'En el Banco de la Nación, en Págalo.pe o en la caja de la UNASAM. Luego registra el número de operación y el recibo en el portal.'],
        ['Ya sé el idioma, ¿debo empezar en el ciclo I?', 'No. Rinde el examen de clasificación desde el portal del estudiante y, según tu resultado, te matriculas en un ciclo superior.'],
        ['¿Cómo obtengo mi certificado?', 'Al aprobar los ciclos de un nivel, solicita el certificado como trámite desde el portal del estudiante. Cada certificado lleva un código y un QR verificables.'],
        ['¿Cómo verifico una constancia o certificado?', 'Escanea su código QR o escribe el código impreso al pie del documento en la sección Verificar de esta página.'],
        ['Soy docente del CILN, ¿dónde ingreso?', 'En Ingresar, con el mismo correo y contraseña. El sistema te lleva a tu panel de docente.'],
    ];
    $tarjeta = 'rounded-3xl border border-marino-900/8 bg-white shadow-[0_1px_2px_rgb(10_46_82/0.05)]';
@endphp
<div x-data="{ idioma: @js($primerIdioma), modalidad: 'Todas', conteos: @js($conteos) }" class="tema-publico min-h-screen overflow-x-clip bg-papel text-marino-900">
    <x-publico.cabecera :secciones="[...$secciones, 'verificar' => 'Verificar']" />

    <main id="contenido" x-data="revelarCiln">
        <section id="inicio" class="relative">
            <div class="mx-auto flex max-w-[980px] flex-col items-center gap-6 px-4 pt-14 text-center sm:px-6 sm:pt-20">
                @if ($oferta)
                    <span class="aparecer flex items-center gap-2 rounded-pildora border border-marino-900/8 bg-white py-1.5 pr-4 pl-3 text-sm font-medium text-marino-800" style="--orden: -4">
                        <span class="relative flex size-2 shrink-0">
                            @if ($oferta->matriculaAbierta)
                                <span class="absolute inset-0 animate-ping rounded-full bg-positivo opacity-60 motion-reduce:hidden"></span>
                            @endif
                            <span @class(['relative size-2 rounded-full', 'bg-positivo' => $oferta->matriculaAbierta, 'bg-celeste-500' => ! $oferta->matriculaAbierta])></span>
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
                <p aria-hidden="true" class="text-[clamp(44px,7.4vw,100px)] leading-[0.98] font-bold tracking-[-0.05em]">
                    <span class="linea-mascara" style="--orden: 0"><span>Aprende</span></span>
                    <span class="linea-mascara -mb-[0.28em] pb-[0.28em]" style="--orden: 1">
                        <span>
                            <span class="relative inline-block">
                                <span x-data="rotadorCiln" class="rotador">
                                    @foreach ($palabrasDelTitular as [$palabra, $bandera])
                                        <span x-bind:class="clase({{ $loop->index }})" @class(['palabra-bandera', 'actual' => $loop->first]) style="background-image: {{ $bandera }}">{{ $palabra }}</span>
                                    @endforeach
                                </span>
                                <x-publico.subrayado class="-bottom-[0.1em] h-[0.15em]" />
                            </span>
                        </span>
                    </span>
                    <span class="linea-mascara" style="--orden: 2"><span>en la UNASAM.</span></span>
                </p>
                <p class="aparecer max-w-[600px] text-[clamp(17px,1.5vw,20px)] text-pretty text-marino-800/80" style="--orden: 0">
                    {{ $nombresDeIdiomas === [] ? 'Idiomas extranjeros y lenguas originarias' : Illuminate\Support\Arr::join($nombresDeIdiomas, ', ', ' y ') }} en ciclos presenciales y virtuales. Matrícula, pagos y certificados desde el portal.
                </p>
                <div class="aparecer flex flex-wrap justify-center gap-3" style="--orden: 1">
                    <x-publico.boton tamano="grande" href="#oferta" class="group">Ver horarios <span aria-hidden="true" class="transition-transform duration-300 ease-salida group-hover:translate-x-1">→</span></x-publico.boton>
                    <x-publico.boton variante="claro" tamano="grande" href="#costos">Consultar costos</x-publico.boton>
                </div>
            </div>

            <div aria-hidden="true" class="mt-10 overflow-hidden sm:mt-14">
                <svg x-data="cintaCiln" viewBox="0 0 1440 190" class="relative left-1/2 block h-auto w-[max(100%,1100px)] -translate-x-1/2">
                    <path id="cinta-ciln" d="M-60 120C220 190 420 30 720 70S1180 0 1500 70" fill="none" stroke="var(--color-marino-800)" stroke-width="46" />
                    <text class="cinta-texto" fill="#fff" dy="6">
                        <textPath href="#cinta-ciln" startOffset="0">
                            @foreach ([1, 2, 3, 4] as $copia)
                                <tspan>@foreach ($nombresConBandera as $nombre){{ $saludos[$nombre] ?? $nombre }} · {{ $nombre }} &nbsp;|&nbsp; @endforeach</tspan>
                            @endforeach
                        </textPath>
                    </text>
                </svg>
            </div>
            <div aria-hidden="true" class="nubes-arriba mt-4 h-[46px]" style="--nube: var(--color-papel-oscuro)"></div>
        </section>

        <section id="idiomas" aria-labelledby="titulo-idiomas" class="scroll-mt-20 bg-papel-oscuro py-20 sm:py-28">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:gap-12 sm:px-6 lg:px-8">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-idiomas" resaltado="idioma" etiqueta="{{ count($idiomas) }} {{ count($idiomas) === 1 ? 'idioma' : 'idiomas' }}{{ in_array('Quechua', $nombresDeIdiomas, true) ? ', incluido Quechua' : '' }}">Elige tu</x-publico.titulo>
                    <x-publico.boton variante="claro" href="#oferta">Ver todos los horarios</x-publico.boton>
                </div>
                @if ($idiomas === [])
                    <x-portal.vacio titulo="Los idiomas del CILN se publicarán pronto." />
                @else
                    <ul role="list" class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,210px),1fr))] gap-4 sm:gap-5">
                        @foreach ($idiomas as $idioma)
                            <li data-revelar style="--orden: {{ $loop->index }}">
                                <a href="#oferta" x-on:click="idioma = @js($idioma['nombre'])" @class(['group flex h-full flex-col gap-5 rounded-3xl p-5 transition-[translate,box-shadow] duration-300 ease-salida hover:-translate-y-1 hover:shadow-[0_16px_36px_rgb(10_46_82/0.12)] focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none motion-reduce:hover:translate-y-0', $tintes[$loop->index % count($tintes)]])>
                                    <span class="flex items-start justify-between gap-3">
                                        <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="44" forma="pill" />
                                        <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-full bg-marino-900 text-white transition-transform duration-300 ease-salida group-hover:translate-x-0.5">→</span>
                                    </span>
                                    <span class="flex flex-col gap-1">
                                        <span class="text-[22px] leading-tight font-bold tracking-[-0.02em]">{{ $idioma['nombre'] }}</span>
                                        <span aria-hidden="true" class="text-[15px] font-medium text-real-600">«{{ $saludos[$idioma['nombre']] ?? $idioma['nombre'] }}»</span>
                                    </span>
                                    <span class="mt-auto flex flex-col gap-0.5 border-t border-marino-900/10 pt-4 text-[14px]">
                                        <span class="text-marino-800/75">{{ $idioma['ciclos'] > 0 ? 'Ciclos I a '.Formato::romano($idioma['ciclos']) : 'Ciclos por publicar' }}</span>
                                        <span class="font-semibold">{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo con horario' : 'grupos con horario' }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section aria-labelledby="titulo-portal" class="py-20 sm:py-28">
            <div class="mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div data-revelar class="flex flex-col gap-8">
                    <div class="flex flex-col gap-4">
                        <x-publico.titulo id="titulo-portal" resaltado="un solo portal" etiqueta="El CILN en cifras">Del quechua al inglés, en</x-publico.titulo>
                        <p class="max-w-[480px] text-[17px] text-pretty text-marino-800/80">Matrícula, pagos, notas, trámites y certificados del CILN, desde tu celular o tu computadora.</p>
                    </div>
                    <dl class="grid grid-cols-2 overflow-hidden rounded-3xl bg-marino-900 text-white">
                        @foreach (array_values(array_filter([
                            [count($idiomas), in_array('Quechua', $nombresDeIdiomas, true) ? 'idiomas, incluido Quechua' : 'idiomas', true],
                            [$gruposConVacantes, $gruposConVacantes === 1 ? 'grupo con vacantes' : 'grupos con vacantes', true],
                            ['I–XII', 'Básico, Intermedio y Avanzado', false],
                            $oferta ? [Formato::diaYMes($oferta->clasesInicio), 'inicio de clases', false] : null,
                        ])) as [$cifra, $detalle, $cuenta])
                            <div @class(['flex flex-col-reverse gap-2 p-6 sm:p-7', 'border-l border-white/12' => $loop->index % 2 === 1, 'border-t border-white/12' => $loop->index > 1])>
                                <dt class="text-[14px] text-celeste-100/80">{{ $detalle }}</dt>
                                <dd @if ($cuenta) x-data="contadorCiln" @endif class="text-[clamp(34px,3.6vw,46px)] leading-none font-semibold tracking-[-0.04em] tabular-nums">{{ $cifra }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div data-revelar class="flex justify-center" style="--orden: 1">
                    <img src="{{ asset('images/ciln/logo.webp') }}" alt="Logo del Centro de Idiomas de la UNASAM" width="720" height="768" class="h-auto w-[min(72%,400px)] select-none lg:w-[min(88%,440px)]">
                </div>
            </div>
        </section>

        <section id="requisitos" aria-labelledby="titulo-requisitos" class="scroll-mt-20 pb-20 sm:pb-28">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
                    <div data-revelar class="flex flex-col gap-5 lg:sticky lg:top-28">
                        <x-publico.titulo id="titulo-requisitos" resaltado="todo en línea" etiqueta="Cómo matricularte">Cuatro pasos,</x-publico.titulo>
                        <p class="max-w-[420px] text-[17px] text-pretty text-marino-800/80">Desde tu cuenta hasta tu ficha de matrícula, sin colas: el CILN valida tu pago y tú eliges el horario.</p>
                        <x-publico.boton :href="route('estudiante.registro')" class="mt-1 self-start">Crear cuenta</x-publico.boton>
                    </div>
                    <ol class="flex flex-col gap-4">
                        @foreach ([
                            ['Crea tu cuenta', 'Regístrate con tu documento de identidad y tu correo personal, y confirma el enlace que te enviamos.', 'bg-hoja-celeste'],
                            ['Paga la matrícula', 'Paga el derecho de matrícula de tu vínculo en el Banco de la Nación, Págalo.pe o la caja de la UNASAM.', 'bg-hoja-verde'],
                            ['Registra tu recibo', 'Súbelo en Registro de pagos con el número de operación. El CILN lo revisa y lo valida.', 'bg-hoja-rojo'],
                            ['Elige tu grupo', 'Ve los horarios con vacantes de tu ciclo y confirma. Descarga tu ficha de matrícula al instante.', 'bg-hoja-ambar'],
                        ] as [$paso, $detalle, $tinte])
                            <li data-revelar class="{{ $tarjeta }} flex items-start gap-4 p-5 sm:gap-5 sm:p-6" style="--orden: {{ $loop->index }}">
                                <span aria-hidden="true" @class(['flex size-11 shrink-0 items-center justify-center rounded-2xl text-[15px] font-bold', $tinte])>0{{ $loop->iteration }}</span>
                                <span class="flex flex-col gap-1 pt-0.5">
                                    <span class="text-[19px] leading-snug font-bold tracking-[-0.02em]">{{ $paso }}</span>
                                    <span class="text-[15px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <ul role="list" data-revelar class="grid grid-cols-[minmax(0,1fr)] gap-6 rounded-3xl bg-hoja-celeste p-6 sm:grid-cols-2 sm:p-8 lg:grid-cols-4">
                    @foreach ([
                        [Illuminate\Support\Arr::join($etiquetasDeModalidad, ', ', ' y '), 'Grupos en las modalidades que publique cada periodo.'],
                        ['Certificado por nivel', 'Al aprobar un nivel, solicítalo como trámite desde el portal.'],
                        ['Verificable con QR', 'Cada constancia y certificado lleva un código para verificarlo.'],
                        ['Todo en un portal', 'Pagos, matrícula, notas y trámites en un solo lugar.'],
                    ] as [$titulo, $detalle])
                        <li class="flex gap-3">
                            <span aria-hidden="true" class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-marino-900 text-white"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.6" class="size-3"><path d="m5 10.5 3.2 3L15 7" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                            <span class="flex flex-col gap-1">
                                <span class="leading-snug font-semibold">{{ $titulo }}</span>
                                <span class="text-[14px] text-pretty text-marino-800/75">{{ $detalle }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="oferta" aria-labelledby="titulo-oferta" class="relative scroll-mt-20 bg-celeste-50 pb-20 sm:pb-28">
            <div aria-hidden="true" class="nubes absolute inset-x-0 top-0 h-10" style="--nube: var(--color-papel)"></div>
            <div class="mx-auto flex max-w-[1200px] flex-col gap-8 px-4 pt-24 sm:px-6 sm:pt-28 lg:px-8">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-oferta" resaltado="horario" :etiqueta="$oferta ? 'Oferta '.$oferta->periodo : 'Oferta'">Elige idioma y</x-publico.titulo>
                    @if ($grupos !== [])
                        <div class="max-w-full overflow-x-auto">
                            <div role="group" aria-label="Modalidad" x-data="indicadorCiln" class="group relative flex w-max gap-1 rounded-pildora border border-marino-900/8 bg-white p-1">
                                <span aria-hidden="true" x-bind:style="estilo" class="indicador absolute top-0 left-0 rounded-pildora bg-marino-900"></span>
                                @foreach (['Todas', ...$etiquetasDeModalidad] as $modalidad)
                                    <button type="button" x-on:click="modalidad = @js($modalidad)" x-bind:aria-pressed="modalidad === @js($modalidad)"
                                        class="relative rounded-pildora px-4 py-[9px] text-[14.5px] font-medium transition-colors duration-300 aria-pressed:bg-marino-900 aria-pressed:text-white group-data-listo:aria-pressed:bg-transparent" @if ($loop->first) aria-pressed="true" @endif>{{ $modalidad }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if ($grupos === [])
                    <x-portal.vacio titulo="La oferta del próximo periodo se publicará pronto.">Mientras tanto, crea tu cuenta para matricularte cuando abra la matrícula.</x-portal.vacio>
                @else
                    <div role="group" aria-label="Idioma" class="flex flex-wrap gap-2">
                        @foreach ($idiomas as $idioma)
                            <button type="button" x-on:click="idioma = @js($idioma['nombre'])" x-bind:aria-pressed="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] === $primerIdioma) aria-pressed="true" @endif
                                class="flex items-center gap-2.5 rounded-pildora border border-marino-900/8 bg-white py-2 pr-4 pl-2.5 transition-colors duration-300 hover:border-celeste-300 aria-pressed:border-marino-900 aria-pressed:bg-marino-900 aria-pressed:text-white">
                                <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="30" forma="pill" />
                                <span class="font-semibold">{{ $idioma['nombre'] }}</span>
                                <span class="text-[13px] opacity-70">{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo' : 'grupos' }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="{{ $tarjeta }} p-2">
                        <div class="flex flex-wrap items-end justify-between gap-3 px-4 pt-4 pb-3 sm:px-5">
                            @foreach ($idiomas as $idioma)
                                <span x-show="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] !== $primerIdioma) x-cloak @endif class="flex flex-col">
                                    <span class="text-lg font-bold">{{ $idioma['nombre'] }}</span>
                                    <span class="text-[14px] text-marino-800/70">{{ $idioma['ciclos'] > 0 ? 'Ciclos I a '.Formato::romano($idioma['ciclos']) : 'Ciclos por publicar' }}</span>
                                </span>
                            @endforeach
                            <span class="text-[13.5px] text-marino-800/60">Vacantes al {{ Formato::diaYMes($ahora) }}, {{ $ahora->format('H:i') }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            @foreach ($grupos as $grupo)
                                @php([$textoVacantes, $colorVacantes] = $vacantes($grupo))
                                <div x-show="idioma === @js($grupo->idioma) && (modalidad === 'Todas' || modalidad === @js($grupo->modalidad))" @if ($grupo->idioma !== $primerIdioma) x-cloak @endif
                                    x-transition:enter="transition duration-500 ease-salida motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-2"
                                    class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 rounded-2xl bg-celeste-50 px-4 py-3.5 sm:px-5 md:grid-cols-[96px_minmax(0,1.5fr)_minmax(0,1fr)_auto]">
                                    <span class="font-bold">Ciclo {{ Formato::romano($grupo->cicloNumero) }}</span>
                                    <span class="col-start-1 flex flex-col md:col-start-auto">
                                        <span class="font-medium">{{ implode(' / ', $grupo->horario) ?: 'Horario por publicar' }}</span>
                                        <span class="text-[13.5px] text-marino-800/60">{{ $grupo->nivel }}{{ $grupo->esAcelerado ? ' · acelerado' : '' }}</span>
                                    </span>
                                    <span class="col-start-1 text-marino-800/80 md:col-start-auto">{{ $grupo->modalidad }}</span>
                                    <x-portal.insignia :color="$colorVacantes" class="col-start-2 row-span-3 row-start-1 self-center md:col-start-auto md:row-span-1 md:row-start-auto">{{ $textoVacantes }}</x-portal.insignia>
                                </div>
                            @endforeach
                        </div>
                        <p x-show="! ((conteos[idioma] ?? {})[modalidad] ?? 0)" x-cloak class="px-4 py-6 text-marino-800/60 sm:px-5">
                            No hay grupos de <span x-text="idioma"></span> en esta modalidad. Prueba con otra.
                        </p>
                        <p class="border-t border-marino-900/8 px-4 pt-4 pb-3 text-[15px] text-marino-800/80 sm:px-5">
                            ¿Ya sabes el idioma? Rinde el examen de clasificación desde el <a href="{{ route('ingresar') }}" class="font-semibold text-real-600 underline decoration-celeste-300 decoration-2 underline-offset-4 hover:text-marino-900">portal del estudiante</a> y empieza en un ciclo superior.
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <section aria-labelledby="titulo-niveles" class="py-20 sm:py-28">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:gap-12 sm:px-6 lg:px-8">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-niveles" resaltado="estás" etiqueta="Niveles">Empieza donde</x-publico.titulo>
                    <p class="max-w-[400px] text-[16px] text-pretty text-marino-800/75">Cada idioma avanza por ciclos en tres niveles. Si ya lo hablas, el examen de clasificación te ubica.</p>
                </div>
                <ul role="list" class="grid grid-cols-[minmax(0,1fr)] gap-4 md:grid-cols-2 md:gap-5">
                    @foreach ([
                        ['Básico', 'Empiezas desde cero, con lo esencial para comunicarte.', 'bg-hoja-celeste', '#oferta', 'M4 5.5A1.5 1.5 0 0 1 5.5 4H9a2 2 0 0 1 2 2v10a1.5 1.5 0 0 0-1.5-1.5H4zm12 0A1.5 1.5 0 0 0 14.5 4H11'],
                        ['Intermedio', 'Amplías vocabulario y conversas con más soltura.', 'bg-hoja-verde', '#oferta', 'M3.5 6A2.5 2.5 0 0 1 6 3.5h8A2.5 2.5 0 0 1 16.5 6v5a2.5 2.5 0 0 1-2.5 2.5H9l-3.5 3v-3A2.5 2.5 0 0 1 3.5 11z'],
                        ['Avanzado', 'Usas el idioma con fluidez en lo académico y lo profesional.', 'bg-hoja-ambar', '#oferta', 'M10 3.5 2.5 7.5 10 11.5l7.5-4zM5.5 9.3v3.9c1 1.3 2.7 2.3 4.5 2.3s3.5-1 4.5-2.3V9.3'],
                        ['Examen de clasificación', '¿Ya sabes el idioma? Rinde el examen y empieza en un ciclo superior.', 'bg-hoja-rojo', route('ingresar'), 'M7 3.5h6M7.5 3.5v2h5v-2M5.5 5h9A1.5 1.5 0 0 1 16 6.5v9a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 4 15.5v-9A1.5 1.5 0 0 1 5.5 5zm2 6.5 1.8 1.8 3.2-3.6'],
                    ] as [$nivel, $detalle, $tinte, $destino, $icono])
                        <li data-revelar style="--orden: {{ $loop->index }}">
                            <a href="{{ $destino }}" class="{{ $tarjeta }} group flex h-full items-center gap-4 p-5 transition-[translate,box-shadow] duration-300 ease-salida hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgb(10_46_82/0.10)] focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none motion-reduce:hover:translate-y-0 sm:gap-5 sm:p-6">
                                <span aria-hidden="true" @class(['flex size-12 shrink-0 items-center justify-center rounded-2xl', $tinte])>
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round" class="size-6"><path d="{{ $icono }}" /></svg>
                                </span>
                                <span class="flex flex-1 flex-col gap-1">
                                    <span class="text-[19px] leading-snug font-bold tracking-[-0.02em]">{{ $nivel }}</span>
                                    <span class="text-[15px] text-pretty text-marino-800/75">{{ $detalle }}</span>
                                </span>
                                <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-full bg-marino-900 text-white transition-transform duration-300 ease-salida group-hover:translate-x-0.5">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section aria-labelledby="titulo-modalidades" class="bg-hoja-verde py-20 sm:py-28">
            <div class="mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] items-center gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16 lg:px-8">
                <div data-revelar class="flex flex-col gap-5">
                    <x-publico.titulo id="titulo-modalidades" resaltado="te acomode" etiqueta="Modalidades">Aprende donde</x-publico.titulo>
                    <p class="max-w-[440px] text-[17px] text-pretty text-marino-800/80">Cada periodo el CILN publica grupos en estas modalidades. Al matricularte eliges el horario y la modalidad que te acomoden.</p>
                    <x-publico.boton href="#oferta" class="mt-1 self-start">Ver horarios</x-publico.boton>
                </div>
                <ul role="list" class="grid grid-cols-[minmax(0,1fr)] gap-4 sm:grid-cols-3">
                    @foreach ($modalidades as $modalidad)
                        @php([$detalle, $icono] = $detallesDeModalidad[$modalidad->value] ?? ['Grupos que publica el CILN en cada periodo.', 'M4 10h12'])
                        <li data-revelar style="--orden: {{ $loop->index }}" class="{{ $tarjeta }} flex flex-col gap-4 p-6">
                            <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-marino-900 text-white">
                                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round" class="size-6"><path d="{{ $icono }}" /></svg>
                            </span>
                            <span class="flex flex-col gap-1">
                                <span class="text-[19px] font-bold tracking-[-0.02em]">{{ $modalidad->etiqueta() }}</span>
                                <span class="text-[15px] text-pretty text-marino-800/75">{{ $detalle }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="costos" aria-labelledby="titulo-costos" class="scroll-mt-20 bg-papel-oscuro py-20 sm:py-28">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:gap-12 sm:px-6 lg:px-8">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-costos" resaltado="vínculo" despues=" con la UNASAM" etiqueta="Costos TUPA / TUSNE" class="max-w-[640px]">La tarifa depende de tu</x-publico.titulo>
                    <p class="max-w-[400px] text-[16px] text-pretty text-marino-800/75">Paga en el Banco de la Nación, Págalo.pe o la caja de la universidad y registra tu recibo en el portal.</p>
                </div>
                @if ($vinculos === [])
                    <x-portal.vacio titulo="Las tarifas se publicarán pronto." />
                @else
                    <ul role="list" class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-5">
                        @foreach ($vinculos as $vinculo)
                            @php($oscura = $loop->count > 1 && $loop->index === intdiv($loop->count, 2))
                            <li data-revelar style="--orden: {{ $loop->index }}" @class(['flex flex-col gap-5 rounded-3xl p-6 sm:p-7', 'bg-marino-900 text-white' => $oscura, 'border border-marino-900/8 bg-white' => ! $oscura])>
                                <h3 class="text-[19px] font-bold">{{ $vinculo['etiqueta'] }}</h3>
                                <ul role="list" @class(['flex flex-col border-t', 'border-white/15' => $oscura, 'border-marino-900/10' => ! $oscura])>
                                    @foreach ($vinculo['tarifas'] as $tarifa)
                                        <li @class(['grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-b py-3.5', 'border-white/15' => $oscura, 'border-marino-900/10' => ! $oscura])>
                                            <span class="flex flex-col">
                                                <span class="text-[15.5px] font-semibold">{{ $tarifa->concepto }}</span>
                                                <span @class(['text-[13px]', 'text-celeste-200/80' => $oscura, 'text-marino-800/60' => ! $oscura])>{{ $tarifa->codigo }} · {{ $tarifa->fuenteEtiqueta }}</span>
                                            </span>
                                            <span class="text-[22px] font-semibold tracking-[-0.02em] whitespace-nowrap tabular-nums">S/ {{ $tarifa->monto }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <x-publico.boton :variante="$oscura ? 'primario' : 'claro'" :href="route('estudiante.registro')" class="mt-auto">Crear cuenta</x-publico.boton>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($tarifasDesde)
                    <span class="text-[13.5px] text-marino-800/60">Tarifas vigentes desde el {{ Formato::fechaLarga($tarifasDesde) }}.</span>
                @endif
            </div>
        </section>

        @if ($fechas !== [])
            <section id="fechas" aria-labelledby="titulo-fechas" class="scroll-mt-20 py-20 sm:py-28">
                <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:gap-12 sm:px-6 lg:px-8">
                    <x-publico.titulo data-revelar id="titulo-fechas" resaltado="periodo" :etiqueta="'Calendario '.$oferta?->periodo">Fechas del</x-publico.titulo>
                    <ol class="grid grid-cols-[minmax(0,1fr)] gap-4 sm:grid-cols-3 sm:gap-5">
                        @foreach ($fechas as $fecha)
                            <li data-revelar style="--orden: {{ $loop->index }}" @class(['flex flex-col gap-3 rounded-3xl p-6 sm:p-7', 'bg-marino-900 text-white' => $fecha['color'] === 'warning', 'border border-marino-900/8 bg-white' => $fecha['color'] !== 'warning'])>
                                <span class="flex items-center justify-between gap-3">
                                    <x-portal.insignia :color="$fecha['color']">{{ $fecha['estado'] }}</x-portal.insignia>
                                    <span aria-hidden="true" @class(['text-[13px] font-semibold', 'text-celeste-200/80' => $fecha['color'] === 'warning', 'text-marino-800/45' => $fecha['color'] !== 'warning'])>0{{ $loop->iteration }}</span>
                                </span>
                                <span @class(['text-[28px] leading-tight font-bold tracking-[-0.03em]', 'text-marino-800/45' => $fecha['color'] === 'gray'])>{{ $fecha['fecha'] }}</span>
                                <span @class(['text-[15px] text-pretty', 'text-celeste-100/85' => $fecha['color'] === 'warning', 'text-marino-800/75' => $fecha['color'] !== 'warning'])>{{ $fecha['detalle'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        <section id="preguntas" aria-labelledby="titulo-preguntas" @class(['scroll-mt-20 pb-20 sm:pb-28', 'pt-20 sm:pt-28' => $fechas === []])>
            <div class="mx-auto flex max-w-[820px] flex-col gap-10 px-4 sm:gap-12 sm:px-6">
                <x-publico.titulo data-revelar id="titulo-preguntas" resaltado="frecuentes" etiqueta="Sin sorpresas" centrado>Preguntas</x-publico.titulo>
                <div class="flex flex-col gap-3">
                    @foreach ($preguntas as [$pregunta, $respuesta])
                        <details name="preguntas" data-revelar style="--orden: {{ $loop->index }}" class="group {{ $tarjeta }} rounded-2xl open:border-celeste-300" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-2xl px-5 py-4 text-[17px] font-semibold focus-visible:ring-4 focus-visible:ring-celeste-300 focus-visible:outline-none sm:px-6 [&::-webkit-details-marker]:hidden">
                                {{ $pregunta }}
                                <span aria-hidden="true" class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-hoja-celeste text-lg leading-none transition-transform duration-300 group-open:rotate-45">+</span>
                            </summary>
                            <p class="px-5 pb-5 text-[15.5px] text-pretty text-marino-800/80 sm:px-6">{{ $respuesta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-empieza" class="relative overflow-hidden bg-celeste-100 pt-24 pb-28 sm:pt-32 sm:pb-36">
            <div aria-hidden="true" class="nubes absolute inset-x-0 top-0 h-10" style="--nube: var(--color-papel)"></div>
            @foreach ([['left-[6%] top-[34%]', -10], ['left-[14%] bottom-[22%]', 8], ['right-[6%] top-[30%]', 10], ['right-[14%] bottom-[20%]', -8]] as [$posicion, $giro])
                <span aria-hidden="true" class="flotar absolute hidden drop-shadow-[0_6px_12px_rgb(10_46_82/0.14)] lg:block {{ $posicion }}" style="--orden: {{ $loop->index }}">
                    <x-ciln.hoja-bandera :idioma="$nombresConBandera[$loop->index % count($nombresConBandera)]" size="64" :rot="$giro" forma="pill" />
                </span>
            @endforeach
            <div data-revelar class="relative mx-auto flex max-w-[720px] flex-col items-center gap-6 px-4 text-center sm:px-6">
                <x-publico.titulo id="titulo-empieza" resaltado="idioma" etiqueta="Matrícula en línea" centrado>Empieza tu próximo</x-publico.titulo>
                <p class="max-w-[520px] text-[17px] text-pretty text-marino-800/80">Crea tu cuenta, registra tu pago y elige tu horario. El CILN te acompaña en cada ciclo.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <x-publico.boton tamano="grande" :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                    <x-publico.boton variante="claro" tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                </div>
            </div>
        </section>
    </main>

    <x-publico.pie :secciones="$secciones" fondo="var(--color-celeste-100)" />
</div>
