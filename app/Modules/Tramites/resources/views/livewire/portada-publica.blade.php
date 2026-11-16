@php
    use App\Modules\Academico\Enums\Modalidad;
    use App\Shared\Ui\Formato;
    use Illuminate\Support\Arr;

    $primerIdioma = collect($idiomas)->firstWhere('grupos', '>', 0)['nombre'] ?? ($idiomas[0]['nombre'] ?? '');
    $nombresDeIdiomas = array_column($idiomas, 'nombre');
    $modalidades = Modalidad::cases();
    $etiquetasDeModalidad = array_map(fn (Modalidad $modalidad): string => $modalidad->etiqueta(), $modalidades);
    $detallesDeModalidad = [
        'presencial' => 'En las aulas del CILN, con su grupo y su docente.',
        'virtual' => 'Clases en línea con el enlace del grupo.',
        'semipresencial' => 'Sesiones en aula y sesiones en línea.',
    ];
    $conteos = collect($grupos)->groupBy('idioma')->map(fn ($deIdioma) => $deIdioma->countBy('modalidad')->put('Todas', $deIdioma->count()));
    $vacantes = fn ($grupo) => match (true) {
        ! $grupo->abierto => ['Próximamente', 'text-marino-800/75'],
        $grupo->vacantes === 0 => ['Sin vacantes', 'text-marino-800/75'],
        default => [$grupo->vacantes.($grupo->vacantes === 1 ? ' vacante' : ' vacantes'), $grupo->vacantes <= 5 ? 'text-ambar-texto' : 'text-verde-texto'],
    };
    $ciclosDe = fn (int $ciclos): string => match ($ciclos) {
        0 => 'Por publicar',
        1 => '1 (I)',
        default => $ciclos.' (I a '.Formato::romano($ciclos).')',
    };
    $secciones = array_filter(
        ['idiomas' => 'Idiomas', 'requisitos' => 'Matrícula', 'oferta' => 'Horarios', 'clasificacion' => 'Clasificación', 'costos' => 'Costos', 'fechas' => 'Calendario', 'preguntas' => 'Preguntas'],
        fn (string $ancla): bool => $ancla !== 'fechas' || $fechas !== [],
        ARRAY_FILTER_USE_KEY,
    );
    $pasos = [
        ['Cree su cuenta', 'Regístrese con su documento de identidad y su correo personal, y confirme el enlace que le enviamos.'],
        ['Pague la matrícula', 'Pague el derecho de matrícula de su vínculo en el Banco de la Nación, Págalo.pe o la caja de la UNASAM.'],
        ['Registre su recibo', 'Regístrelo en Registro de pagos con el número de operación. El CILN lo revisa y lo valida.'],
        ['Elija su grupo', 'Elija un horario con vacantes de su ciclo y descargue su ficha de matrícula.'],
    ];
    $pasosDeClasificacion = [
        ['Pague el examen', 'En el Banco de la Nación, Págalo.pe o la caja de la UNASAM, y registre el recibo en el portal.'],
        ['Solicítelo en el portal', 'Ingrese al portal del estudiante, elija el idioma y el pago validado del examen.'],
        ['Rinda el examen', 'En la fecha y el lugar que le comunique la coordinación académica.'],
        ['Reciba su ciclo', 'La coordinación registra el resultado y le asigna el ciclo en el que puede matricularse.'],
    ];
    $niveles = [
        ['Básico', 'Para quien inicia el estudio del idioma: vocabulario, estructuras y expresiones de uso cotidiano.', 'Comprender y usar expresiones habituales, presentarse y desenvolverse en situaciones sencillas.'],
        ['Intermedio', 'Amplía el vocabulario y la gramática para comunicarse con mayor precisión en el ámbito personal y académico.', 'Participar en conversaciones, comprender las ideas principales de textos claros y redactar textos breves.'],
        ['Avanzado', 'Consolida el uso del idioma en contextos académicos y profesionales.', 'Expresarse con fluidez, comprender textos extensos y redactar documentos académicos y profesionales.'],
    ];
    $preguntas = [
        ['¿Cómo me matriculo?', 'Cree su cuenta, pague el derecho de matrícula de su vínculo, registre el recibo en Registro de pagos y, cuando el CILN lo valide, elija un grupo con vacantes. La ficha de matrícula se descarga al instante.'],
        ['¿Dónde pago?', 'En el Banco de la Nación, en Págalo.pe o en la caja de la UNASAM. Luego registre el número de operación y el recibo en el portal.'],
        ['Ya conozco el idioma, ¿debo empezar en el ciclo I?', 'No. Rinda el examen de clasificación desde el portal del estudiante y, según su resultado, se matricula en un ciclo superior.'],
        ['¿Cómo obtengo mi certificado?', 'Al aprobar los ciclos de un nivel, solicite el certificado como trámite desde el portal del estudiante. Cada certificado lleva un código y un QR verificables.'],
        ['¿Cómo verifico una constancia o certificado?', 'Escriba el código impreso al pie del documento en la sección de verificación, al final de esta página, o escanee su código QR.'],
        ['Soy docente del CILN, ¿dónde ingreso?', 'En Ingresar, con el mismo correo y contraseña. El sistema lo lleva a su panel de docente.'],
    ];
    $avisos = $oferta === null ? [] : [
        $oferta->matriculaAbierta
            ? 'Matrícula '.$oferta->periodo.' abierta hasta el '.Formato::diaDeMes($oferta->matriculaFin)
            : 'Matrícula '.$oferta->periodo.' desde el '.Formato::diaDeMes($oferta->matriculaInicio),
        'Inicio de clases: '.Formato::diaDeMes($oferta->clasesInicio),
        'Examen de clasificación para quien ya conoce el idioma',
        'Constancias y certificados verificables con su código',
    ];
    $contenedor = 'mx-auto max-w-[1240px] px-4 sm:px-6 lg:px-8';
    $seccion = 'scroll-mt-32 py-20 sm:py-28';
    $nota = 'text-[16px] leading-relaxed text-pretty text-marino-800/80';
    $numero = fn (int $indice): string => str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT);
@endphp
<div x-data="{ idioma: @js($primerIdioma), modalidad: 'Todas', conteos: @js($conteos) }" class="tema-publico min-h-screen overflow-x-clip bg-white text-marino-900">
    <x-publico.cabecera :secciones="Arr::only($secciones, ['idiomas', 'requisitos', 'oferta', 'costos', 'preguntas'])" />

    @if ($avisos !== [])
        <div class="overflow-hidden border-b border-marino-900/10 bg-celeste-50 text-[14px] text-marino-800">
            <div class="flex items-stretch">
                <span class="relative z-10 flex shrink-0 items-center bg-real-600 px-4 font-semibold text-white sm:px-6">Avisos</span>
                <div class="min-w-0 flex-1 overflow-hidden">
                    <ul class="cinta-avisos py-2.5">
                        @foreach ([false, true] as $copia)
                            @foreach ($avisos as $aviso)
                                <li @if ($copia) aria-hidden="true" @endif class="flex shrink-0 items-center gap-6 pl-6 whitespace-nowrap">
                                    @if ($loop->first && ! $copia)
                                        <strong class="font-semibold text-marino-900">{{ $aviso }}.</strong>
                                    @else
                                        <span>{{ $aviso }}.</span>
                                    @endif
                                    <span aria-hidden="true" class="size-1 rounded-full bg-real-600/50"></span>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <main id="contenido" x-data="revelarCiln">
        <section id="inicio" class="sobre-oscuro relative isolate flex min-h-[min(86vh,780px)] items-end overflow-hidden bg-marino-950 text-white">
            <img src="{{ asset('images/portada/portada.jpg') }}" alt="" width="2200" height="1238" fetchpriority="high"
                class="foto-portada absolute inset-0 -z-20 size-full object-cover object-[center_35%]">
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-t from-marino-950/85 via-marino-950/30 to-transparent"></div>
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-r from-marino-950/55 via-marino-950/10 to-transparent"></div>
            <div class="{{ $contenedor }} w-full pt-28 pb-12 sm:pb-16 lg:pb-20">
                <div class="grid grid-cols-[minmax(0,1fr)] gap-10 lg:grid-cols-[minmax(0,8fr)_minmax(0,4fr)] lg:items-end lg:gap-16">
                    <div class="flex flex-col gap-6">
                        <span data-revelar class="flex items-center gap-2 text-[14px] font-medium text-celeste-100/90">
                            <span aria-hidden="true" class="size-1.5 bg-celeste-300"></span>
                            Universidad Nacional Santiago Antúnez de Mayolo · Huaraz
                        </span>
                        <h1 x-data="titularCiln" class="font-display text-[clamp(42px,6.6vw,96px)] leading-[0.98] font-semibold tracking-[-0.035em] text-balance">Centro de Idiomas y Lenguas Nativas</h1>
                    </div>
                    <div data-revelar style="--orden: 4" class="flex flex-col gap-6 lg:pb-3">
                        <p class="text-[17px] leading-relaxed text-pretty text-white/85">
                            {{ $nombresDeIdiomas === [] ? 'Idiomas extranjeros y lenguas originarias' : Arr::join($nombresDeIdiomas, ', ', ' y ') }} para estudiantes universitarios, profesionales y público en general.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <x-publico.boton variante="blanco" tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                            <x-publico.boton variante="contorno" tamano="grande" :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-centro" class="{{ $seccion }}">
            <div class="{{ $contenedor }} flex flex-col gap-16 sm:gap-20">
                <div class="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-24">
                    <div data-revelar>
                        <x-publico.titulo id="titulo-centro" etiqueta="El centro">Idiomas para la universidad y la región</x-publico.titulo>
                    </div>
                    <div data-revelar style="--orden: 1" class="flex flex-col gap-5 {{ $nota }} lg:pt-10">
                        <p>El Centro de Idiomas y Lenguas Nativas de la UNASAM enseña idiomas extranjeros y lenguas originarias del Perú. Cada idioma se estudia por ciclos, agrupados en los niveles básico, intermedio y avanzado.</p>
                        <p>La matrícula, los pagos, las notas y los certificados se gestionan en línea desde el portal del estudiante, y cada constancia o certificado lleva un código y un QR para verificar su autenticidad.</p>
                    </div>
                </div>
                <dl aria-label="Cifras del periodo" class="grid grid-cols-2 border-t border-marino-900/15 lg:grid-cols-4">
                    @foreach ($cifras as $cifra)
                        <div data-revelar style="--orden: {{ $loop->index }}" @class([
                            'flex flex-col-reverse justify-end gap-2 border-marino-900/15 pt-7 pb-2',
                            'pr-4 sm:pr-8' => $loop->index % 2 === 0,
                            'border-l pl-4 sm:pl-8' => $loop->index % 2 === 1,
                            'mt-6 border-t pt-7 lg:mt-0 lg:border-t-0' => $loop->index >= 2,
                            'lg:border-l lg:pl-8' => $loop->index === 2,
                        ])>
                            <dt class="text-[14px] text-marino-800/75">{{ $cifra['etiqueta'] }}</dt>
                            <dd class="font-display text-[clamp(38px,4.6vw,60px)] leading-none font-semibold tracking-[-0.03em] text-marino-900 tabular-nums">{{ $cifra['valor'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section id="idiomas" aria-labelledby="titulo-idiomas" class="scroll-mt-32 border-t border-marino-900/12">
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                <div class="py-14 lg:border-r lg:border-marino-900/12 lg:py-24 lg:pr-12">
                    <div class="lg:sticky lg:top-40">
                        <figure data-cortina class="aspect-[3/2] overflow-hidden bg-marino-900/5 lg:aspect-[4/5]">
                            <img src="{{ asset('images/portada/idiomas.jpg') }}" alt="Estudiante leyendo un libro en la biblioteca" width="1100" height="1650" loading="lazy" class="size-full object-cover">
                        </figure>
                    </div>
                </div>
                <div class="flex flex-col gap-10 pb-20 lg:py-24 lg:pl-12">
                    <div data-revelar class="flex flex-col gap-5">
                        <x-publico.titulo id="titulo-idiomas" etiqueta="Oferta académica">Idiomas</x-publico.titulo>
                        <p class="max-w-[520px] {{ $nota }}">Cada idioma se estudia por ciclos, organizados en niveles. Al aprobar un nivel se solicita el certificado correspondiente.</p>
                    </div>
                    @if ($idiomas === [])
                        <x-portal.vacio titulo="Los idiomas del CILN se publicarán pronto." />
                    @else
                        <ol class="border-t border-marino-900/15">
                            @foreach ($idiomas as $idioma)
                                <li data-idioma="{{ $idioma['nombre'] }}" class="border-b border-marino-900/15">
                                    <div data-revelar style="--orden: {{ $loop->index % 4 }}" class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-4 py-8 sm:grid-cols-[3.5rem_minmax(0,1fr)]">
                                        <span class="pt-2 font-display text-[15px] font-semibold text-real-600 tabular-nums">{{ $numero($loop->index) }}</span>
                                        <div class="flex flex-col gap-5">
                                            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                                                <span class="flex items-center gap-3">
                                                    <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="30" forma="rect" />
                                                    <span class="font-display text-[28px] leading-tight font-semibold tracking-[-0.02em]">{{ $idioma['nombre'] }}</span>
                                                </span>
                                                <a href="#oferta" x-on:click="idioma = @js($idioma['nombre'])" class="group flex items-center gap-2 text-[14.5px] font-semibold text-real-600 hover:text-marino-900">
                                                    <span>{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo' : 'grupos' }} · Ver horarios</span>
                                                    <span aria-hidden="true" class="transition-transform duration-300 group-hover:translate-x-1">→</span>
                                                </a>
                                            </div>
                                            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-[14.5px] xl:grid-cols-[auto_auto_minmax(0,1fr)_auto]">
                                                <div class="flex flex-col gap-0.5">
                                                    <dt class="text-[13px] text-marino-800/70">Ciclos</dt>
                                                    <dd class="font-semibold">{{ $ciclosDe($idioma['ciclos']) }}</dd>
                                                </div>
                                                @if ($idioma['horas'])
                                                    <div class="flex flex-col gap-0.5">
                                                        <dt class="text-[13px] text-marino-800/70">Horas lectivas por ciclo</dt>
                                                        <dd class="font-semibold tabular-nums">{{ $idioma['horas'][0] === $idioma['horas'][1] ? $idioma['horas'][0] : $idioma['horas'][0].' a '.$idioma['horas'][1] }}</dd>
                                                    </div>
                                                @endif
                                                <div class="flex flex-col gap-0.5">
                                                    <dt class="text-[13px] text-marino-800/70">Modalidades</dt>
                                                    <dd class="font-semibold">{{ $idioma['modalidades'] === [] ? 'Sin grupos en este periodo' : Arr::join($idioma['modalidades'], ', ', ' y ') }}</dd>
                                                </div>
                                                @if ($idioma['matriculaDesde'])
                                                    <div class="flex flex-col gap-0.5">
                                                        <dt class="text-[13px] text-marino-800/70">Matrícula desde</dt>
                                                        <dd class="font-semibold whitespace-nowrap tabular-nums">S/ {{ $idioma['matriculaDesde'] }}</dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </section>

        <section id="requisitos" aria-labelledby="titulo-requisitos" class="sobre-oscuro scroll-mt-32 bg-real-600 text-white">
            <div class="{{ $contenedor }}">
                <div class="grid grid-cols-[minmax(0,1fr)] gap-8 border-b border-white/25 pt-20 pb-12 sm:pt-28 lg:grid-cols-[minmax(0,6fr)_minmax(0,6fr)] lg:gap-0">
                    <div data-revelar class="lg:pr-12">
                        <x-publico.titulo id="titulo-requisitos" etiqueta="Matrícula" claro>Cómo matricularse</x-publico.titulo>
                    </div>
                    <div data-revelar style="--orden: 1" class="flex flex-col items-start gap-6 lg:border-l lg:border-white/25 lg:pl-12">
                        <p class="text-[16px] leading-relaxed text-pretty text-white/90">Todo el proceso es en línea: cree su cuenta, registre su pago y elija un grupo con vacantes. Modalidades: {{ Arr::join($etiquetasDeModalidad, ', ', ' y ') }}.</p>
                        <x-publico.boton variante="blanco" :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                    </div>
                </div>
                <figure data-cortina class="mt-12 aspect-[16/9] overflow-hidden bg-white/10 sm:aspect-[21/9]">
                    <img src="{{ asset('images/portada/aula.jpg') }}" alt="Docente dictando clase a un grupo de estudiantes" width="2000" height="1125" loading="lazy" class="size-full object-cover">
                </figure>
                <ol class="grid grid-cols-[minmax(0,1fr)] pt-4 pb-20 sm:-mx-6 sm:grid-cols-2 lg:-mx-8 sm:pb-28 lg:grid-cols-4">
                    @foreach ($pasos as [$paso, $detalle])
                        <li data-revelar style="--orden: {{ $loop->index }}" @class([
                            'flex flex-col gap-3 border-b border-white/25 py-8 sm:px-6 lg:border-b-0 lg:px-8',
                            'sm:border-l' => $loop->index % 2 === 1,
                            'lg:border-l' => $loop->index > 0,
                        ])>
                            <span class="font-display text-[15px] font-semibold text-celeste-100 tabular-nums">{{ $numero($loop->index) }}</span>
                            <span class="font-display text-[20px] font-semibold tracking-[-0.01em]">{{ $paso }}</span>
                            <span class="text-[14.5px] leading-relaxed text-pretty text-white/85">{{ $detalle }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section id="oferta" aria-labelledby="titulo-oferta" class="{{ $seccion }}">
            <div class="{{ $contenedor }} flex flex-col gap-8">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-oferta" :etiqueta="$oferta ? 'Oferta '.$oferta->periodo : 'Oferta'">Horarios y vacantes</x-publico.titulo>
                    @if ($grupos !== [])
                        <div role="group" aria-label="Modalidad" class="flex max-w-full flex-wrap border border-marino-900/25">
                            @foreach (['Todas', ...$etiquetasDeModalidad] as $modalidad)
                                <button type="button" x-on:click="modalidad = @js($modalidad)" x-bind:aria-pressed="modalidad === @js($modalidad)" @if ($loop->first) aria-pressed="true" @endif
                                    class="px-4 py-2 text-[14px] font-medium transition-colors duration-200 not-first:border-l not-first:border-marino-900/25 hover:bg-celeste-50 aria-pressed:bg-marino-900 aria-pressed:text-white">{{ $modalidad }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($grupos === [])
                    <x-portal.vacio titulo="La oferta del próximo periodo se publicará pronto.">Mientras tanto, cree su cuenta para matricularse cuando abra la matrícula.</x-portal.vacio>
                @else
                    <div data-revelar class="flex flex-col gap-6">
                        <div role="group" aria-label="Idioma" class="flex gap-1 overflow-x-auto border-b border-marino-900/15">
                            @foreach ($idiomas as $idioma)
                                <button type="button" x-on:click="idioma = @js($idioma['nombre'])" x-bind:aria-pressed="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] === $primerIdioma) aria-pressed="true" @endif
                                    class="-mb-px flex shrink-0 items-center gap-2 border-b-3 border-transparent px-4 py-2.5 font-medium text-marino-800/80 transition-colors duration-200 hover:text-marino-900 aria-pressed:border-real-600 aria-pressed:text-marino-900">
                                    {{ $idioma['nombre'] }}
                                    <span class="text-[13px] text-marino-800/75">({{ $idioma['grupos'] }})</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="overflow-x-auto">
                            <table class="tabla-publica min-w-[760px]">
                                <caption class="pb-3 text-left text-[14px] text-marino-800/75">
                                    @foreach ($idiomas as $idioma)
                                        <span x-show="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] !== $primerIdioma) x-cloak @endif>{{ $idioma['nombre'] }}: {{ $idioma['ciclos'] > 0 ? 'ciclos I a '.Formato::romano($idioma['ciclos']) : 'ciclos por publicar' }}.</span>
                                    @endforeach
                                    Vacantes al {{ Formato::diaYMes($ahora) }}, {{ $ahora->format('H:i') }}.
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Ciclo</th>
                                        <th scope="col">Nivel</th>
                                        <th scope="col">Días</th>
                                        <th scope="col">Hora</th>
                                        <th scope="col">Modalidad</th>
                                        <th scope="col">Inicio</th>
                                        <th scope="col" class="text-right!">Vacantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grupos as $grupo)
                                        @php([$textoVacantes, $colorVacantes] = $vacantes($grupo))
                                        <tr x-show="idioma === @js($grupo->idioma) && (modalidad === 'Todas' || modalidad === @js($grupo->modalidad))" @if ($grupo->idioma !== $primerIdioma) x-cloak @endif
                                            x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                            <td class="font-semibold whitespace-nowrap">Ciclo {{ Formato::romano($grupo->cicloNumero) }}</td>
                                            <td>{{ $grupo->nivel }}{{ $grupo->esAcelerado ? ' (acelerado)' : '' }}</td>
                                            @if ($grupo->bloques === [])
                                                <td colspan="2" class="text-marino-800/75">Horario por publicar</td>
                                            @else
                                                <td>
                                                    @foreach ($grupo->bloques as $bloque)
                                                        <span class="block whitespace-nowrap">{{ $bloque['dias'] }}</span>
                                                    @endforeach
                                                </td>
                                                <td class="tabular-nums">
                                                    @foreach ($grupo->bloques as $bloque)
                                                        <span class="block whitespace-nowrap">{{ $bloque['hora'] }}</span>
                                                    @endforeach
                                                </td>
                                            @endif
                                            <td>{{ $grupo->modalidad }}</td>
                                            <td class="whitespace-nowrap">{{ $oferta ? Formato::diaYMes($oferta->clasesInicio) : '' }}</td>
                                            <td class="text-right font-semibold whitespace-nowrap {{ $colorVacantes }}">{{ $textoVacantes }}</td>
                                        </tr>
                                    @endforeach
                                    <tr x-show="! ((conteos[idioma] ?? {})[modalidad] ?? 0)" x-cloak>
                                        <td colspan="7" class="text-marino-800/75">No hay grupos de <span x-text="idioma"></span> en esta modalidad.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section id="clasificacion" aria-labelledby="titulo-clasificacion" class="scroll-mt-32 border-y border-marino-900/10 bg-papel">
            <div class="grid grid-cols-[minmax(0,1fr)] lg:grid-cols-2">
                <div class="flex justify-end">
                    <div class="flex w-full max-w-[620px] flex-col gap-10 px-4 py-20 sm:px-6 sm:py-28 lg:pr-16 lg:pl-8">
                        <div data-revelar class="flex flex-col gap-5">
                            <x-publico.titulo id="titulo-clasificacion" etiqueta="Examen de clasificación">¿Ya conoce el idioma?</x-publico.titulo>
                            <p class="{{ $nota }}">Rinda el examen de clasificación y, según su resultado, empiece en un ciclo superior sin cursar desde el ciclo I.</p>
                        </div>
                        <ol class="border-t border-marino-900/15">
                            @foreach ($pasosDeClasificacion as [$paso, $detalle])
                                <li data-revelar style="--orden: {{ $loop->index }}" class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-3 gap-y-1 border-b border-marino-900/15 py-5">
                                    <span class="row-span-2 pt-0.5 font-display text-[15px] font-semibold text-real-600 tabular-nums">{{ $numero($loop->index) }}</span>
                                    <span class="font-display text-[17px] font-semibold">{{ $paso }}</span>
                                    <span class="text-[14.5px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <div data-revelar class="flex flex-col gap-5">
                            @if ($examen['tarifas'] !== [])
                                <table class="tabla-publica">
                                    <caption class="pb-3 text-left font-display text-[19px] font-semibold">Costo del examen</caption>
                                    <thead class="sr-only">
                                        <tr><th scope="col">Vínculo</th><th scope="col">Monto</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($examen['tarifas'] as $tarifa)
                                            <tr>
                                                <td>{{ $tarifa->tipoVinculo->etiqueta() }}</td>
                                                <td class="text-right font-semibold whitespace-nowrap tabular-nums">S/ {{ $tarifa->monto }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                            <p class="text-[15px] text-pretty text-marino-800/85">La solicitud queda vigente por {{ $examen['diasDeVigencia'] }} días desde su registro en el portal.</p>
                            <x-publico.boton :href="route('ingresar')" class="self-start">Solicitar en el portal</x-publico.boton>
                        </div>
                    </div>
                </div>
                <figure data-cortina class="relative min-h-[360px] overflow-hidden bg-marino-900/5 sm:min-h-[460px]">
                    <img src="{{ asset('images/portada/clasificacion.jpg') }}" alt="Estudiantes escribiendo en sus cuadernos durante una clase" width="1600" height="900" loading="lazy" class="absolute inset-0 size-full object-cover">
                </figure>
            </div>
        </section>

        <section aria-labelledby="titulo-ruta" class="{{ $seccion }}">
            <div class="{{ $contenedor }} flex flex-col gap-14">
                <div class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:items-end lg:gap-24">
                    <div data-revelar>
                        <x-publico.titulo id="titulo-ruta" etiqueta="Plan de estudios">Ruta de aprendizaje</x-publico.titulo>
                    </div>
                    <p data-revelar style="--orden: 1" class="{{ $nota }}">Cada nivel agrupa varios ciclos. El estudiante avanza de un ciclo al siguiente al aprobarlo; quien ya conoce el idioma puede <a href="#clasificacion" class="font-semibold text-real-600 underline underline-offset-3 hover:text-marino-900">rendir el examen de clasificación</a>.</p>
                </div>
                <ol class="relative grid grid-cols-[minmax(0,1fr)] border-t border-marino-900/15 lg:-mx-8 lg:grid-cols-3 lg:border-t-0 lg:before:absolute lg:before:inset-x-8 lg:before:top-0 lg:before:h-px lg:before:bg-marino-900/15">
                    @foreach ($niveles as [$nivel, $detalle, $logro])
                        {{-- A subgrid per level lines up its rows with the other levels, so the rule above the outcome sits at one height. --}}
                        <li data-revelar style="--orden: {{ $loop->index }}" @class([
                            'flex flex-col gap-4 border-b border-marino-900/15 py-10 lg:row-span-5 lg:grid lg:grid-rows-subgrid lg:gap-y-4 lg:border-b-0 lg:px-8',
                            'lg:border-l' => $loop->index > 0,
                        ])>
                            <span class="font-display text-[64px] leading-none font-semibold tracking-[-0.04em] text-real-600/25 tabular-nums">{{ $numero($loop->index) }}</span>
                            <span class="text-[14px] font-medium text-real-600">Nivel {{ $loop->iteration }}</span>
                            <span class="font-display text-[28px] leading-tight font-semibold tracking-[-0.02em]">{{ $nivel }}</span>
                            <span class="text-[15px] leading-relaxed text-pretty text-marino-800/80">{{ $detalle }}</span>
                            <span class="mt-4 border-t border-marino-900/10 pt-4 text-[14.5px] text-pretty lg:mt-2">
                                <span class="block font-semibold">Al concluir el nivel podrá:</span>
                                <span class="text-marino-800/80">{{ $logro }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <div class="flex flex-col gap-6 border-t border-marino-900/15 pt-10">
                    <h3 data-revelar class="font-display text-[22px] font-semibold tracking-[-0.01em]">Modalidades de dictado</h3>
                    <dl class="grid grid-cols-[minmax(0,1fr)] md:-mx-6 md:grid-cols-3">
                        @foreach ($modalidades as $modalidad)
                            <div data-revelar style="--orden: {{ $loop->index }}" class="flex flex-col gap-1 border-b border-marino-900/15 py-4 md:border-b-0 md:px-6 md:not-first:border-l">
                                <dt class="font-semibold">{{ $modalidad->etiqueta() }}</dt>
                                <dd class="text-[15px] text-pretty text-marino-800/80">{{ $detallesDeModalidad[$modalidad->value] ?? 'Grupos que publica el CILN en cada periodo.' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section id="costos" aria-labelledby="titulo-costos" class="{{ $seccion }} border-y border-marino-900/10 bg-papel">
            <div class="{{ $contenedor }} flex flex-col gap-12">
                <div class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:items-end lg:gap-24">
                    <div data-revelar>
                        <x-publico.titulo id="titulo-costos" etiqueta="TUPA / TUSNE">Costos según su vínculo con la UNASAM</x-publico.titulo>
                    </div>
                    <p data-revelar style="--orden: 1" class="{{ $nota }}">Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.</p>
                </div>
                @if ($vinculos === [])
                    <x-portal.vacio titulo="Las tarifas se publicarán pronto." />
                @else
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,330px),1fr))] gap-x-10 gap-y-12">
                        @foreach ($vinculos as $vinculo)
                            <table data-revelar style="--orden: {{ $loop->index }}" class="tabla-publica self-start">
                                <caption class="border-b-2 border-marino-900 pb-3 text-left font-display text-[20px] font-semibold tracking-[-0.01em]">{{ $vinculo['etiqueta'] }}</caption>
                                <thead class="sr-only">
                                    <tr><th scope="col">Concepto</th><th scope="col">Monto</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($vinculo['tarifas'] as $tarifa)
                                        <tr>
                                            <td class="pl-0!">
                                                <span class="block font-semibold">{{ $tarifa->concepto }}</span>
                                                <span class="block text-[13px] text-marino-800/75">{{ $tarifa->codigo }} · {{ $tarifa->fuenteEtiqueta }}</span>
                                            </td>
                                            <td class="pr-0! text-right font-semibold whitespace-nowrap tabular-nums">S/ {{ $tarifa->monto }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                @endif
                @if ($tarifasDesde)
                    <span class="text-[13.5px] text-marino-800/75">Tarifas vigentes desde el {{ Formato::fechaLarga($tarifasDesde) }}.</span>
                @endif
            </div>
        </section>

        @if ($fechas !== [])
            <section id="fechas" aria-labelledby="titulo-fechas" class="{{ $seccion }}">
                <div class="{{ $contenedor }} flex flex-col gap-12">
                    <div data-revelar>
                        <x-publico.titulo id="titulo-fechas" resaltado="periodo" :etiqueta="'Calendario '.$oferta?->periodo">Fechas del</x-publico.titulo>
                    </div>
                    <ol class="border-t border-marino-900/15">
                        @foreach ($fechas as $fecha)
                            <li data-revelar style="--orden: {{ $loop->index }}" @class([
                                'grid grid-cols-[minmax(0,1fr)] gap-x-10 gap-y-2 border-b border-marino-900/15 py-7 sm:grid-cols-[9rem_minmax(0,16rem)_minmax(0,1fr)] sm:items-baseline',
                                'opacity-60' => $fecha['color'] === 'gray',
                            ])>
                                <span @class([
                                    'flex items-center gap-2 text-[14px] font-semibold',
                                    'text-real-600' => $fecha['color'] === 'warning',
                                    'text-marino-800/75' => $fecha['color'] !== 'warning',
                                ])>
                                    <span aria-hidden="true" @class(['size-2 rounded-full', 'bg-real-600' => $fecha['color'] === 'warning', 'bg-marino-900/25' => $fecha['color'] !== 'warning'])></span>
                                    {{ $fecha['estado'] }}
                                </span>
                                <span class="font-display text-[clamp(24px,2.6vw,32px)] leading-tight font-semibold tracking-[-0.02em] whitespace-nowrap">{{ $fecha['fecha'] }}</span>
                                <span class="text-[16px] text-pretty text-marino-800/85">{{ $fecha['detalle'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        <section id="preguntas" aria-labelledby="titulo-preguntas" class="scroll-mt-32 border-t border-marino-900/12">
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                <div data-revelar class="flex flex-col justify-between gap-10 pt-20 pb-8 sm:pt-28 lg:border-r lg:border-marino-900/12 lg:pr-12 lg:pb-28">
                    <div class="flex flex-col gap-5">
                        <x-publico.titulo id="titulo-preguntas" etiqueta="Orientación">Preguntas frecuentes</x-publico.titulo>
                        <p class="{{ $nota }}">Lo que más consultan los postulantes y estudiantes del CILN.</p>
                    </div>
                    <p class="flex flex-wrap items-baseline justify-between gap-4 border-t border-marino-900/12 pt-5 text-[14.5px] text-marino-800/80">
                        <span>¿Tiene una constancia o un certificado?</span>
                        <a href="{{ route('verificacion.documento') }}" class="font-semibold text-real-600 underline underline-offset-3 hover:text-marino-900">Verificar documento</a>
                    </p>
                </div>
                <div class="preguntas-suaves pb-20 sm:pb-28 lg:pt-28 lg:pl-12">
                    <div class="border-t border-marino-900/15">
                        @foreach ($preguntas as [$pregunta, $respuesta])
                            <details data-revelar style="--orden: {{ $loop->index }}" name="preguntas" class="group border-b border-marino-900/15" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center gap-5 py-5 font-display text-[17.5px] font-semibold transition-colors hover:text-real-600 [&::-webkit-details-marker]:hidden">
                                    <span aria-hidden="true" class="shrink-0 text-[22px] leading-none font-normal text-real-600 transition-transform duration-300 group-open:rotate-45 motion-reduce:transition-none">+</span>
                                    {{ $pregunta }}
                                </summary>
                                <p class="pb-6 pl-[2.1rem] text-[15px] leading-relaxed text-pretty text-marino-800/85">{{ $respuesta }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-empieza" class="border-t border-marino-900/10 bg-papel">
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] lg:grid-cols-2">
                <div class="py-12 lg:border-r lg:border-marino-900/12 lg:py-16 lg:pr-12">
                    <figure data-cortina class="aspect-[4/3] overflow-hidden bg-marino-900/5 lg:aspect-[4/5]">
                        <img src="{{ asset('images/portada/huaraz.jpg') }}" alt="Vista de la ciudad de Huaraz al atardecer" width="1200" height="1600" loading="lazy" class="size-full object-cover">
                    </figure>
                </div>
                <div data-revelar class="flex flex-col justify-center gap-7 pb-16 lg:py-16 lg:pl-12">
                    <x-publico.titulo id="titulo-empieza" etiqueta="Huaraz, Áncash">Matrícula en línea</x-publico.titulo>
                    <p class="max-w-[480px] {{ $nota }}">Cree su cuenta, registre su pago y elija su horario. El CILN valida su recibo y su ficha de matrícula se descarga al instante.</p>
                    <div class="flex flex-wrap gap-3">
                        <x-publico.boton tamano="grande" :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                        <x-publico.boton variante="claro" tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <x-publico.pie :secciones="$secciones" />
</div>
