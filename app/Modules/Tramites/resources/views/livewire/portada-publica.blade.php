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
        ! $grupo->abierto => ['Próximamente', 'text-marino-800/60'],
        $grupo->vacantes === 0 => ['Sin vacantes', 'text-marino-800/60'],
        default => [$grupo->vacantes.($grupo->vacantes === 1 ? ' vacante' : ' vacantes'), $grupo->vacantes <= 5 ? 'text-ambar-texto' : 'text-positivo'],
    };
    $secciones = array_filter(
        ['idiomas' => 'Idiomas', 'requisitos' => 'Matrícula', 'oferta' => 'Horarios', 'costos' => 'Costos', 'fechas' => 'Calendario', 'preguntas' => 'Preguntas'],
        fn (string $ancla): bool => $ancla !== 'fechas' || $fechas !== [],
        ARRAY_FILTER_USE_KEY,
    );
    $pasos = [
        ['Cree su cuenta', 'Regístrese con su documento de identidad y su correo personal, y confirme el enlace que le enviamos.'],
        ['Pague la matrícula', 'Pague el derecho de matrícula de su vínculo en el Banco de la Nación, Págalo.pe o la caja de la UNASAM.'],
        ['Registre su recibo', 'Regístrelo en Registro de pagos con el número de operación. El CILN lo revisa y lo valida.'],
        ['Elija su grupo', 'Elija un horario con vacantes de su ciclo y descargue su ficha de matrícula.'],
    ];
    $niveles = [
        ['Básico', 'Desde cero, con lo esencial para comunicarse.'],
        ['Intermedio', 'Mayor vocabulario y conversación con soltura.'],
        ['Avanzado', 'Uso fluido en contextos académicos y profesionales.'],
        ['Examen de clasificación', 'Para quien ya conoce el idioma: ubica al estudiante en un ciclo superior.'],
    ];
    $preguntas = [
        ['¿Cómo me matriculo?', 'Cree su cuenta, pague el derecho de matrícula de su vínculo, registre el recibo en Registro de pagos y, cuando el CILN lo valide, elija un grupo con vacantes. La ficha de matrícula se descarga al instante.'],
        ['¿Dónde pago?', 'En el Banco de la Nación, en Págalo.pe o en la caja de la UNASAM. Luego registre el número de operación y el recibo en el portal.'],
        ['Ya conozco el idioma, ¿debo empezar en el ciclo I?', 'No. Rinda el examen de clasificación desde el portal del estudiante y, según su resultado, se matricula en un ciclo superior.'],
        ['¿Cómo obtengo mi certificado?', 'Al aprobar los ciclos de un nivel, solicite el certificado como trámite desde el portal del estudiante. Cada certificado lleva un código y un QR verificables.'],
        ['¿Cómo verifico una constancia o certificado?', 'Escriba el código impreso al pie del documento en la sección de verificación, al final de esta página, o escanee su código QR.'],
        ['Soy docente del CILN, ¿dónde ingreso?', 'En Ingresar, con el mismo correo y contraseña. El sistema lo lleva a su panel de docente.'],
    ];
    $celda = 'border-b border-marino-900/10 px-4 py-3 align-top';
    $cabeceraDeTabla = 'border-b-2 border-marino-900 px-4 py-2.5 text-left text-[12.5px] font-semibold tracking-[0.08em] text-marino-900 uppercase';
@endphp
<div x-data="{ idioma: @js($primerIdioma), modalidad: 'Todas', conteos: @js($conteos) }" class="tema-publico min-h-screen overflow-x-clip bg-white text-marino-900">
    <x-publico.cabecera :secciones="$secciones" />

    @if ($oferta)
        <div class="border-b border-marino-900/10 bg-celeste-50">
            <div class="mx-auto flex max-w-[1200px] items-center gap-4 px-4 py-2.5 text-[14.5px] sm:px-6 lg:px-8">
                <span class="shrink-0 bg-marino-900 px-2.5 py-1 text-[12px] font-semibold tracking-[0.12em] text-white uppercase">Aviso</span>
                <span>
                    @if ($oferta->matriculaAbierta)
                        <strong class="font-semibold">Matrícula {{ $oferta->periodo }} abierta hasta el {{ Formato::diaDeMes($oferta->matriculaFin) }}.</strong>
                    @else
                        <strong class="font-semibold">Matrícula {{ $oferta->periodo }} desde el {{ Formato::diaDeMes($oferta->matriculaInicio) }}.</strong>
                    @endif
                    <a href="#oferta" class="text-real-600 underline underline-offset-3 hover:text-marino-900">Ver horarios</a>
                </span>
            </div>
        </div>
    @endif

    <main id="contenido">
        <section id="inicio" class="bg-marino-900 text-white">
            <div class="mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-center lg:gap-16 lg:px-8">
                <div class="flex flex-col gap-6">
                    <span class="text-[13px] font-semibold tracking-[0.14em] text-celeste-200 uppercase">Universidad Nacional Santiago Antúnez de Mayolo</span>
                    <h1 class="font-serif text-[clamp(34px,4.6vw,56px)] leading-[1.08] font-semibold text-balance">Centro de Idiomas y Lenguas Nativas</h1>
                    <p class="max-w-[600px] text-[17px] text-pretty text-celeste-100/85">
                        {{ $nombresDeIdiomas === [] ? 'Idiomas extranjeros y lenguas originarias' : Arr::join($nombresDeIdiomas, ', ', ' y ') }} para estudiantes universitarios, profesionales y público en general. Matrícula, pagos, notas y certificados en línea.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <x-publico.boton tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                        <x-publico.boton variante="contorno" tamano="grande" href="#costos">Consultar costos</x-publico.boton>
                    </div>
                </div>
                <div class="border-t-4 border-real-600 bg-white text-marino-900">
                    <div class="flex items-center justify-between gap-4 border-b border-marino-900/10 px-6 py-4">
                        <span class="font-serif text-[20px] font-semibold">{{ $oferta ? 'Periodo '.$oferta->periodo : 'Próximo periodo' }}</span>
                        <x-ciln.emblema decorativo class="w-10" />
                    </div>
                    <dl class="divide-y divide-marino-900/10 px-6 text-[15px]">
                        @foreach (array_values(array_filter([
                            ['Idiomas', count($idiomas) > 0 ? (string) count($idiomas) : 'Por publicar'],
                            ['Grupos con vacantes', (string) $gruposConVacantes],
                            ['Niveles', 'Básico, Intermedio y Avanzado'],
                            $oferta ? ['Inicio de clases', Formato::fechaLarga($oferta->clasesInicio)] : null,
                        ])) as [$dato, $valor])
                            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-4 py-3">
                                <dt class="text-marino-800/75">{{ $dato }}</dt>
                                <dd class="text-right font-semibold">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <div class="flex flex-wrap gap-2 border-t border-marino-900/10 px-6 py-4">
                        <x-publico.boton :href="route('estudiante.registro')" class="flex-1">Crear cuenta</x-publico.boton>
                        <x-publico.boton variante="claro" :href="route('ingresar')" class="flex-1">Ingresar al portal</x-publico.boton>
                    </div>
                </div>
            </div>
        </section>

        <section id="idiomas" aria-labelledby="titulo-idiomas" class="scroll-mt-32 py-16 sm:py-20">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-idiomas" etiqueta="Oferta académica">Idiomas</x-publico.titulo>
                    <p class="max-w-[460px] text-[15.5px] text-pretty text-marino-800/80">Cada idioma se estudia por ciclos, organizados en niveles. Al aprobar un nivel se solicita el certificado correspondiente.</p>
                </div>
                @if ($idiomas === [])
                    <x-portal.vacio titulo="Los idiomas del CILN se publicarán pronto." />
                @else
                    <ul role="list" class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,250px),1fr))] border-t border-l border-marino-900/12">
                        @foreach ($idiomas as $idioma)
                            <li class="border-r border-b border-marino-900/12">
                                <a href="#oferta" x-on:click="idioma = @js($idioma['nombre'])" class="group flex h-full flex-col gap-4 p-5 transition-colors hover:bg-celeste-50 focus-visible:ring-3 focus-visible:ring-celeste-300 focus-visible:outline-none focus-visible:ring-inset">
                                    <span class="flex items-center gap-3">
                                        <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="34" forma="rect" />
                                        <span class="font-serif text-[21px] font-semibold">{{ $idioma['nombre'] }}</span>
                                    </span>
                                    <span class="flex flex-col gap-0.5 text-[14.5px]">
                                        <span class="text-marino-800/75">{{ $idioma['ciclos'] > 0 ? 'Ciclos I a '.Formato::romano($idioma['ciclos']) : 'Ciclos por publicar' }}</span>
                                        <span class="font-semibold">{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo con horario' : 'grupos con horario' }}</span>
                                    </span>
                                    <span class="mt-auto text-[14px] font-semibold text-real-600 group-hover:underline">Ver horarios</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section id="requisitos" aria-labelledby="titulo-requisitos" class="scroll-mt-32 border-y border-marino-900/10 bg-papel py-16 sm:py-20">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-requisitos" etiqueta="Requisitos">Cómo matricularse</x-publico.titulo>
                    <x-publico.boton :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                </div>
                <ol class="grid grid-cols-[minmax(0,1fr)] gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($pasos as [$paso, $detalle])
                        <li class="flex flex-col gap-2 border-t-2 border-marino-900 pt-4">
                            <span class="font-serif text-[30px] leading-none font-semibold text-real-600">{{ $loop->iteration }}.</span>
                            <span class="text-[17px] font-semibold">{{ $paso }}</span>
                            <span class="text-[14.5px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="border-l-4 border-real-600 bg-white px-5 py-4 text-[15px] text-pretty text-marino-800/85">
                    Modalidades: {{ Arr::join($etiquetasDeModalidad, ', ', ' y ') }}. Cada constancia y certificado lleva un código y un QR para verificar su autenticidad.
                </p>
            </div>
        </section>

        <section id="oferta" aria-labelledby="titulo-oferta" class="scroll-mt-32 py-16 sm:py-20">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-oferta" :etiqueta="$oferta ? 'Oferta '.$oferta->periodo : 'Oferta'">Horarios y vacantes</x-publico.titulo>
                    @if ($grupos !== [])
                        <div role="group" aria-label="Modalidad" class="flex max-w-full flex-wrap border border-marino-900/25">
                            @foreach (['Todas', ...$etiquetasDeModalidad] as $modalidad)
                                <button type="button" x-on:click="modalidad = @js($modalidad)" x-bind:aria-pressed="modalidad === @js($modalidad)" @if ($loop->first) aria-pressed="true" @endif
                                    class="px-4 py-2 text-[14px] font-medium transition-colors not-first:border-l not-first:border-marino-900/25 hover:bg-celeste-50 aria-pressed:bg-marino-900 aria-pressed:text-white">{{ $modalidad }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($grupos === [])
                    <x-portal.vacio titulo="La oferta del próximo periodo se publicará pronto.">Mientras tanto, cree su cuenta para matricularse cuando abra la matrícula.</x-portal.vacio>
                @else
                    <div role="group" aria-label="Idioma" class="flex gap-1 overflow-x-auto border-b border-marino-900/15">
                        @foreach ($idiomas as $idioma)
                            <button type="button" x-on:click="idioma = @js($idioma['nombre'])" x-bind:aria-pressed="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] === $primerIdioma) aria-pressed="true" @endif
                                class="-mb-px flex shrink-0 items-center gap-2 border-b-3 border-transparent px-4 py-2.5 font-medium text-marino-800/80 transition-colors hover:text-marino-900 aria-pressed:border-real-600 aria-pressed:text-marino-900">
                                {{ $idioma['nombre'] }}
                                <span class="text-[13px] text-marino-800/60">({{ $idioma['grupos'] }})</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] border-collapse text-[15px]">
                            <caption class="pb-3 text-left text-[14px] text-marino-800/70">
                                @foreach ($idiomas as $idioma)
                                    <span x-show="idioma === @js($idioma['nombre'])" @if ($idioma['nombre'] !== $primerIdioma) x-cloak @endif>{{ $idioma['nombre'] }}: {{ $idioma['ciclos'] > 0 ? 'ciclos I a '.Formato::romano($idioma['ciclos']) : 'ciclos por publicar' }}.</span>
                                @endforeach
                                Vacantes al {{ Formato::diaYMes($ahora) }}, {{ $ahora->format('H:i') }}.
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="{{ $cabeceraDeTabla }}">Ciclo</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }}">Nivel</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }}">Horario</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }}">Modalidad</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }} text-right">Vacantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grupos as $grupo)
                                    @php([$textoVacantes, $colorVacantes] = $vacantes($grupo))
                                    <tr x-show="idioma === @js($grupo->idioma) && (modalidad === 'Todas' || modalidad === @js($grupo->modalidad))" @if ($grupo->idioma !== $primerIdioma) x-cloak @endif class="hover:bg-celeste-50">
                                        <td class="{{ $celda }} font-semibold whitespace-nowrap">Ciclo {{ Formato::romano($grupo->cicloNumero) }}</td>
                                        <td class="{{ $celda }}">{{ $grupo->nivel }}{{ $grupo->esAcelerado ? ' (acelerado)' : '' }}</td>
                                        <td class="{{ $celda }}">{{ implode(' / ', $grupo->horario) ?: 'Horario por publicar' }}</td>
                                        <td class="{{ $celda }}">{{ $grupo->modalidad }}</td>
                                        <td class="{{ $celda }} text-right font-semibold whitespace-nowrap {{ $colorVacantes }}">{{ $textoVacantes }}</td>
                                    </tr>
                                @endforeach
                                <tr x-show="! ((conteos[idioma] ?? {})[modalidad] ?? 0)" x-cloak>
                                    <td colspan="5" class="{{ $celda }} text-marino-800/70">No hay grupos de <span x-text="idioma"></span> en esta modalidad.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[15px] text-marino-800/85">
                        ¿Ya conoce el idioma? Rinda el examen de clasificación desde el <a href="{{ route('ingresar') }}" class="font-semibold text-real-600 underline underline-offset-3 hover:text-marino-900">portal del estudiante</a> y empiece en un ciclo superior.
                    </p>
                @endif
            </div>
        </section>

        <section aria-labelledby="titulo-niveles" class="border-y border-marino-900/10 bg-papel py-16 sm:py-20">
            <div class="mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div class="flex flex-col gap-6">
                    <x-publico.titulo id="titulo-niveles" etiqueta="Plan de estudios">Niveles</x-publico.titulo>
                    <dl class="border-t border-marino-900/15">
                        @foreach ($niveles as [$nivel, $detalle])
                            <div class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-4 border-b border-marino-900/15 py-3.5 text-[15px] max-sm:grid-cols-[minmax(0,1fr)] max-sm:gap-1">
                                <dt class="font-semibold">{{ $nivel }}</dt>
                                <dd class="text-pretty text-marino-800/80">{{ $detalle }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="flex flex-col gap-6">
                    <x-publico.titulo id="titulo-modalidades" etiqueta="Dictado">Modalidades</x-publico.titulo>
                    <dl class="border-t border-marino-900/15">
                        @foreach ($modalidades as $modalidad)
                            <div class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-4 border-b border-marino-900/15 py-3.5 text-[15px] max-sm:grid-cols-[minmax(0,1fr)] max-sm:gap-1">
                                <dt class="font-semibold">{{ $modalidad->etiqueta() }}</dt>
                                <dd class="text-pretty text-marino-800/80">{{ $detallesDeModalidad[$modalidad->value] ?? 'Grupos que publica el CILN en cada periodo.' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section id="costos" aria-labelledby="titulo-costos" class="scroll-mt-32 py-16 sm:py-20">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-10 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-costos" etiqueta="TUPA / TUSNE">Costos según su vínculo con la UNASAM</x-publico.titulo>
                    <p class="max-w-[420px] text-[15.5px] text-pretty text-marino-800/80">Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.</p>
                </div>
                @if ($vinculos === [])
                    <x-portal.vacio titulo="Las tarifas se publicarán pronto." />
                @else
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,330px),1fr))] gap-8">
                        @foreach ($vinculos as $vinculo)
                            <table class="w-full border-collapse self-start text-[15px]">
                                <caption class="bg-marino-900 px-4 py-2.5 text-left font-serif text-[17px] font-semibold text-white">{{ $vinculo['etiqueta'] }}</caption>
                                <thead class="sr-only">
                                    <tr><th scope="col">Concepto</th><th scope="col">Monto</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($vinculo['tarifas'] as $tarifa)
                                        <tr>
                                            <td class="{{ $celda }}">
                                                <span class="block font-semibold">{{ $tarifa->concepto }}</span>
                                                <span class="block text-[13px] text-marino-800/65">{{ $tarifa->codigo }} · {{ $tarifa->fuenteEtiqueta }}</span>
                                            </td>
                                            <td class="{{ $celda }} text-right font-semibold whitespace-nowrap tabular-nums">S/ {{ $tarifa->monto }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                @endif
                @if ($tarifasDesde)
                    <span class="text-[13.5px] text-marino-800/65">Tarifas vigentes desde el {{ Formato::fechaLarga($tarifasDesde) }}.</span>
                @endif
            </div>
        </section>

        @if ($fechas !== [])
            <section id="fechas" aria-labelledby="titulo-fechas" class="scroll-mt-32 border-y border-marino-900/10 bg-papel py-16 sm:py-20">
                <div class="mx-auto flex max-w-[1200px] flex-col gap-8 px-4 sm:px-6 lg:px-8">
                    <x-publico.titulo id="titulo-fechas" resaltado="periodo" :etiqueta="'Calendario '.$oferta?->periodo">Fechas del</x-publico.titulo>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] border-collapse bg-white text-[15px]">
                            <thead>
                                <tr>
                                    <th scope="col" class="{{ $cabeceraDeTabla }} w-40">Estado</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }} w-56">Fecha</th>
                                    <th scope="col" class="{{ $cabeceraDeTabla }}">Actividad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($fechas as $fecha)
                                    <tr>
                                        <td @class([$celda, 'font-semibold', 'text-real-600' => $fecha['color'] === 'warning', 'text-marino-800/60' => $fecha['color'] === 'gray'])>{{ $fecha['estado'] }}</td>
                                        <td @class([$celda, 'font-semibold whitespace-nowrap', 'text-marino-800/60' => $fecha['color'] === 'gray'])>{{ $fecha['fecha'] }}</td>
                                        <td class="{{ $celda }} text-marino-800/85">{{ $fecha['detalle'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <section id="preguntas" aria-labelledby="titulo-preguntas" class="scroll-mt-32 py-16 sm:py-20">
            <div class="mx-auto grid max-w-[1200px] grid-cols-[minmax(0,1fr)] gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,4fr)_minmax(0,8fr)] lg:gap-16 lg:px-8">
                <x-publico.titulo id="titulo-preguntas" etiqueta="Orientación">Preguntas frecuentes</x-publico.titulo>
                <div class="border-t border-marino-900/15">
                    @foreach ($preguntas as [$pregunta, $respuesta])
                        <details name="preguntas" class="group border-b border-marino-900/15" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-[16.5px] font-semibold focus-visible:ring-3 focus-visible:ring-celeste-300 focus-visible:outline-none [&::-webkit-details-marker]:hidden">
                                {{ $pregunta }}
                                <span aria-hidden="true" class="shrink-0 text-xl leading-none text-real-600 transition-transform duration-200 group-open:rotate-45">+</span>
                            </summary>
                            <p class="pb-5 text-[15px] text-pretty text-marino-800/85">{{ $respuesta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-empieza" class="bg-celeste-50 py-14">
            <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-1.5">
                    <h2 id="titulo-empieza" class="font-serif text-[26px] font-semibold">Matrícula en línea</h2>
                    <p class="text-[15.5px] text-marino-800/80">Cree su cuenta, registre su pago y elija su horario.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-publico.boton tamano="grande" :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                    <x-publico.boton variante="claro" tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                </div>
            </div>
        </section>
    </main>

    <x-publico.pie :secciones="$secciones" />
</div>
