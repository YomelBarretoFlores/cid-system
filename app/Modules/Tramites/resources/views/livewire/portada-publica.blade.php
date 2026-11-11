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
    $contenedor = 'mx-auto max-w-[1200px] px-4 sm:px-6 lg:px-8';
    $seccion = 'scroll-mt-32 py-16 sm:py-24';
    $nota = 'text-[15.5px] text-pretty text-marino-800/80';
@endphp
<div x-data="{ idioma: @js($primerIdioma), modalidad: 'Todas', conteos: @js($conteos) }" class="tema-publico min-h-screen overflow-x-clip bg-white text-marino-900">
    <x-publico.cabecera :secciones="Arr::only($secciones, ['idiomas', 'requisitos', 'oferta', 'costos', 'preguntas'])" />

    @if ($oferta)
        <div class="border-b border-marino-900/10 bg-celeste-50">
            <div class="{{ $contenedor }} flex items-center gap-4 py-2.5 text-[14.5px]">
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

    <main id="contenido" x-data="revelarCiln">
        <section id="inicio" class="sobre-oscuro relative isolate overflow-hidden bg-marino-900 text-white">
            <div aria-hidden="true" class="trama-institucional absolute inset-0 -z-10"></div>
            <div aria-hidden="true" class="absolute inset-y-0 right-0 -z-10 w-2/3 bg-linear-to-l from-marino-700/45 to-transparent"></div>
            <x-ciln.emblema blanco decorativo class="pointer-events-none absolute top-1/2 -right-24 -z-10 w-[560px] max-w-none -translate-y-1/2 opacity-[0.06] sm:-right-16 lg:right-[-4%] lg:w-[640px]" />
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] gap-12 py-16 sm:py-24 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-center lg:gap-16 lg:py-28">
                <div class="flex flex-col gap-7">
                    <span data-revelar class="flex items-center gap-3 text-[13px] font-semibold tracking-[0.14em] text-celeste-200 uppercase">
                        <span aria-hidden="true" class="h-px w-10 bg-celeste-300"></span>
                        Universidad Nacional Santiago Antúnez de Mayolo
                    </span>
                    <h1 x-data="titularCiln" class="font-serif text-[clamp(36px,5vw,62px)] leading-[1.06] font-semibold text-balance">Centro de Idiomas y Lenguas Nativas</h1>
                    <p data-revelar style="--orden: 3" class="max-w-[600px] text-[17.5px] leading-relaxed text-pretty text-celeste-100/85">
                        {{ $nombresDeIdiomas === [] ? 'Idiomas extranjeros y lenguas originarias' : Arr::join($nombresDeIdiomas, ', ', ' y ') }} para estudiantes universitarios, profesionales y público en general. Matrícula, pagos, notas y certificados en línea.
                    </p>
                    <div data-revelar style="--orden: 4" class="flex flex-wrap gap-3">
                        <x-publico.boton tamano="grande" href="#oferta">Ver horarios</x-publico.boton>
                        <x-publico.boton variante="contorno" tamano="grande" href="#costos">Consultar costos</x-publico.boton>
                    </div>
                </div>
                <div data-revelar style="--orden: 5" class="border-t-4 border-celeste-500 bg-white text-marino-900 shadow-2xl shadow-marino-950/40">
                    <div class="flex items-center justify-between gap-4 border-b border-marino-900/10 px-6 py-5">
                        <span class="flex flex-col gap-1">
                            <span class="text-[12.5px] font-semibold tracking-[0.12em] text-real-600 uppercase">{{ match (true) { (bool) $oferta?->matriculaAbierta => 'Matrícula abierta', $oferta !== null => 'Próxima matrícula', default => 'Oferta académica' } }}</span>
                            <span class="font-serif text-[22px] leading-tight font-semibold">{{ $oferta ? 'Periodo '.$oferta->periodo : 'Próximo periodo' }}</span>
                        </span>
                        <x-ciln.emblema decorativo class="w-11" />
                    </div>
                    <p class="px-6 py-4 text-[15px] text-pretty text-marino-800/80">
                        @if ($oferta?->matriculaAbierta)
                            Matricúlese en línea hasta el {{ Formato::fechaLarga($oferta->matriculaFin) }}.
                        @elseif ($oferta)
                            La matrícula en línea abre el {{ Formato::fechaLarga($oferta->matriculaInicio) }}.
                        @else
                            Cree su cuenta para matricularse cuando el CILN publique la oferta.
                        @endif
                    </p>
                    <div class="flex flex-wrap gap-2 border-t border-marino-900/10 px-6 py-4">
                        <x-publico.boton :href="route('estudiante.registro')" class="flex-1">Crear cuenta</x-publico.boton>
                        <x-publico.boton variante="claro" :href="route('ingresar')" class="flex-1">Ingresar al portal</x-publico.boton>
                    </div>
                </div>
            </div>
        </section>

        <div class="border-b border-marino-900/12 bg-white">
            <dl aria-label="Cifras del periodo" class="{{ $contenedor }} grid grid-cols-2 lg:grid-cols-4">
                @foreach ($cifras as $cifra)
                    <div data-revelar style="--orden: {{ $loop->index }}" @class([
                        'flex flex-col-reverse justify-end gap-1.5 border-marino-900/12 py-7 sm:py-9',
                        'pr-4 sm:pr-8' => $loop->index % 2 === 0,
                        'border-l pl-4 sm:pl-8' => $loop->index % 2 === 1,
                        'border-t lg:border-t-0' => $loop->index >= 2,
                        'lg:border-l lg:pl-8' => $loop->index === 2,
                    ])>
                        <dt class="text-[13px] font-semibold tracking-[0.1em] text-marino-800/75 uppercase">{{ $cifra['etiqueta'] }}</dt>
                        <dd @if ($cifra['contar']) x-data="contadorCiln" @endif class="font-serif text-[clamp(30px,3.6vw,44px)] leading-none font-semibold text-marino-900 tabular-nums">{{ $cifra['valor'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <section id="idiomas" aria-labelledby="titulo-idiomas" class="{{ $seccion }}">
            <div class="{{ $contenedor }} flex flex-col gap-10">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-idiomas" etiqueta="Oferta académica">Idiomas</x-publico.titulo>
                    <p class="max-w-[460px] {{ $nota }}">Cada idioma se estudia por ciclos, organizados en niveles. Al aprobar un nivel se solicita el certificado correspondiente.</p>
                </div>
                @if ($idiomas === [])
                    <x-portal.vacio titulo="Los idiomas del CILN se publicarán pronto." />
                @else
                    <ul role="list" class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,270px),1fr))] border-t border-l border-marino-900/12">
                        @foreach ($idiomas as $idioma)
                            <li class="border-r border-b border-marino-900/12">
                                <div data-revelar style="--orden: {{ $loop->index % 4 }}" class="flex h-full flex-col gap-5 p-6">
                                    <span class="flex items-center gap-3 border-b border-marino-900/10 pb-4">
                                        <x-ciln.hoja-bandera :idioma="$idioma['nombre']" size="34" forma="rect" />
                                        <span class="font-serif text-[21px] font-semibold">{{ $idioma['nombre'] }}</span>
                                    </span>
                                    <dl class="flex flex-col gap-2.5 text-[14.5px]">
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-marino-800/75">Ciclos</dt>
                                            <dd class="text-right font-semibold">{{ $ciclosDe($idioma['ciclos']) }}</dd>
                                        </div>
                                        @if ($idioma['horas'])
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-marino-800/75">Horas lectivas por ciclo</dt>
                                                <dd class="text-right font-semibold tabular-nums">{{ $idioma['horas'][0] === $idioma['horas'][1] ? $idioma['horas'][0] : $idioma['horas'][0].' a '.$idioma['horas'][1] }}</dd>
                                            </div>
                                        @endif
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-marino-800/75">Modalidades</dt>
                                            <dd class="text-right font-semibold">{{ $idioma['modalidades'] === [] ? 'Sin grupos en este periodo' : Arr::join($idioma['modalidades'], ', ', ' y ') }}</dd>
                                        </div>
                                        @if ($idioma['matriculaDesde'])
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-marino-800/75">Matrícula desde</dt>
                                                <dd class="text-right font-semibold whitespace-nowrap tabular-nums">S/ {{ $idioma['matriculaDesde'] }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                    <a href="#oferta" x-on:click="idioma = @js($idioma['nombre'])" class="mt-auto flex items-center justify-between border-t border-marino-900/10 pt-4 text-[14.5px] font-semibold text-real-600 hover:text-marino-900">
                                        <span>{{ $idioma['grupos'] }} {{ $idioma['grupos'] === 1 ? 'grupo con horario' : 'grupos con horario' }}</span>
                                        <span class="underline underline-offset-3">Ver horarios</span>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section id="requisitos" aria-labelledby="titulo-requisitos" class="{{ $seccion }} border-y border-marino-900/10 bg-papel">
            <div class="{{ $contenedor }} flex flex-col gap-10">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-requisitos" etiqueta="Requisitos">Cómo matricularse</x-publico.titulo>
                    <x-publico.boton :href="route('estudiante.registro')">Crear cuenta</x-publico.boton>
                </div>
                <ol class="grid grid-cols-[minmax(0,1fr)] gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($pasos as [$paso, $detalle])
                        <li data-revelar style="--orden: {{ $loop->index }}" class="flex flex-col gap-2 border-t-2 border-marino-900 pt-4">
                            <span class="font-serif text-[30px] leading-none font-semibold text-real-600">{{ $loop->iteration }}.</span>
                            <span class="text-[17px] font-semibold">{{ $paso }}</span>
                            <span class="text-[14.5px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                        </li>
                    @endforeach
                </ol>
                <p data-revelar class="border-l-4 border-real-600 bg-white px-5 py-4 text-[15px] text-pretty text-marino-800/85">
                    Modalidades: {{ Arr::join($etiquetasDeModalidad, ', ', ' y ') }}. Cada constancia y certificado lleva un código y un QR para verificar su autenticidad.
                </p>
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

        <section id="clasificacion" aria-labelledby="titulo-clasificacion" class="{{ $seccion }} border-y border-marino-900/10 bg-papel">
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-16">
                <div class="flex flex-col gap-8">
                    <div data-revelar class="flex flex-col gap-5">
                        <x-publico.titulo id="titulo-clasificacion" etiqueta="Examen de clasificación">¿Ya conoce el idioma?</x-publico.titulo>
                        <p class="max-w-[620px] {{ $nota }}">Rinda el examen de clasificación y, según su resultado, empiece en un ciclo superior sin cursar desde el ciclo I.</p>
                    </div>
                    <ol class="border-t border-marino-900/15">
                        @foreach ($pasosDeClasificacion as [$paso, $detalle])
                            <li data-revelar style="--orden: {{ $loop->index }}" class="grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-4 gap-y-1 border-b border-marino-900/15 py-4">
                                <span class="row-span-2 font-serif text-[24px] leading-none font-semibold text-real-600">{{ $loop->iteration }}.</span>
                                <span class="text-[16px] font-semibold">{{ $paso }}</span>
                                <span class="text-[14.5px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div data-revelar style="--orden: 2" class="flex flex-col gap-5 self-start border-t-4 border-real-600 bg-white p-6">
                    @if ($examen['tarifas'] !== [])
                        <table class="tabla-publica">
                            <caption class="pb-3 text-left font-serif text-[19px] font-semibold">Costo del examen</caption>
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
        </section>

        <section aria-labelledby="titulo-ruta" class="{{ $seccion }}">
            <div class="{{ $contenedor }} flex flex-col gap-12">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-ruta" etiqueta="Plan de estudios">Ruta de aprendizaje</x-publico.titulo>
                    <p class="max-w-[460px] {{ $nota }}">Cada nivel agrupa varios ciclos. El estudiante avanza de un ciclo al siguiente al aprobarlo.</p>
                </div>
                <ol class="grid grid-cols-[minmax(0,1fr)] border-b-2 border-marino-900 lg:grid-cols-3 lg:items-end">
                    @foreach ($niveles as [$nivel, $detalle, $logro])
                        <li data-revelar style="--orden: {{ $loop->index }}" @class([
                            'flex flex-col gap-3 border-t border-marino-900/15 py-6 lg:border-t-2 lg:border-l lg:border-t-marino-900 lg:border-l-marino-900/15 lg:px-6 lg:pb-8',
                            'lg:min-h-[270px]' => $loop->index === 0,
                            'lg:min-h-[335px]' => $loop->index === 1,
                            'lg:min-h-[400px]' => $loop->index === 2,
                        ])>
                            <span class="text-[12.5px] font-semibold tracking-[0.12em] text-real-600 uppercase">Nivel {{ $loop->iteration }}</span>
                            <span class="font-serif text-[26px] leading-tight font-semibold">{{ $nivel }}</span>
                            <span class="text-[15px] text-pretty text-marino-800/80">{{ $detalle }}</span>
                            <span class="mt-auto border-t border-marino-900/10 pt-3 text-[14.5px] text-pretty">
                                <span class="block font-semibold">Al concluir el nivel podrá:</span>
                                <span class="text-marino-800/80">{{ $logro }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <div data-revelar class="grid grid-cols-[minmax(0,1fr)] gap-x-8 gap-y-3 border-l-4 border-real-600 bg-papel px-5 py-4 text-[15px] sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                    <p class="text-pretty text-marino-800/85"><strong class="font-semibold text-marino-900">Examen de clasificación.</strong> Para quien ya conoce el idioma: ubica al estudiante en un ciclo superior.</p>
                    <a href="#clasificacion" class="font-semibold text-real-600 underline underline-offset-3 hover:text-marino-900">Ver requisitos del examen</a>
                </div>
                <div class="flex flex-col gap-6">
                    <h3 data-revelar class="font-serif text-[22px] font-semibold">Modalidades de dictado</h3>
                    <dl class="grid grid-cols-[minmax(0,1fr)] border-t border-marino-900/15 md:grid-cols-3">
                        @foreach ($modalidades as $modalidad)
                            <div data-revelar style="--orden: {{ $loop->index }}" class="flex flex-col gap-1 border-b border-marino-900/15 py-4 md:border-b-0 md:not-first:border-l md:not-first:pl-6 md:pr-6">
                                <dt class="font-semibold">{{ $modalidad->etiqueta() }}</dt>
                                <dd class="text-[15px] text-pretty text-marino-800/80">{{ $detallesDeModalidad[$modalidad->value] ?? 'Grupos que publica el CILN en cada periodo.' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section id="costos" aria-labelledby="titulo-costos" class="{{ $seccion }} border-y border-marino-900/10 bg-papel">
            <div class="{{ $contenedor }} flex flex-col gap-10">
                <div data-revelar class="flex flex-wrap items-end justify-between gap-6">
                    <x-publico.titulo id="titulo-costos" etiqueta="TUPA / TUSNE">Costos según su vínculo con la UNASAM</x-publico.titulo>
                    <p class="max-w-[420px] {{ $nota }}">Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.</p>
                </div>
                @if ($vinculos === [])
                    <x-portal.vacio titulo="Las tarifas se publicarán pronto." />
                @else
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,330px),1fr))] gap-8">
                        @foreach ($vinculos as $vinculo)
                            <table data-revelar style="--orden: {{ $loop->index }}" class="tabla-publica self-start bg-white">
                                <caption class="bg-marino-900 px-4 py-2.5 text-left font-serif text-[17px] font-semibold text-white">{{ $vinculo['etiqueta'] }}</caption>
                                <thead class="sr-only">
                                    <tr><th scope="col">Concepto</th><th scope="col">Monto</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($vinculo['tarifas'] as $tarifa)
                                        <tr>
                                            <td>
                                                <span class="block font-semibold">{{ $tarifa->concepto }}</span>
                                                <span class="block text-[13px] text-marino-800/75">{{ $tarifa->codigo }} · {{ $tarifa->fuenteEtiqueta }}</span>
                                            </td>
                                            <td class="text-right font-semibold whitespace-nowrap tabular-nums">S/ {{ $tarifa->monto }}</td>
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
                <div class="{{ $contenedor }} flex flex-col gap-8">
                    <div data-revelar>
                        <x-publico.titulo id="titulo-fechas" resaltado="periodo" :etiqueta="'Calendario '.$oferta?->periodo">Fechas del</x-publico.titulo>
                    </div>
                    <div data-revelar class="overflow-x-auto">
                        <table class="tabla-publica min-w-[560px]">
                            <thead>
                                <tr>
                                    <th scope="col" class="w-40">Estado</th>
                                    <th scope="col" class="w-56">Fecha</th>
                                    <th scope="col">Actividad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($fechas as $fecha)
                                    <tr>
                                        <td @class(['font-semibold', 'text-real-600' => $fecha['color'] === 'warning', 'text-marino-800/75' => $fecha['color'] === 'gray'])>{{ $fecha['estado'] }}</td>
                                        <td @class(['font-semibold whitespace-nowrap', 'text-marino-800/75' => $fecha['color'] === 'gray'])>{{ $fecha['fecha'] }}</td>
                                        <td class="text-marino-800/85">{{ $fecha['detalle'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <section id="preguntas" aria-labelledby="titulo-preguntas" class="{{ $seccion }} border-t border-marino-900/10">
            <div class="{{ $contenedor }} grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,4fr)_minmax(0,8fr)] lg:gap-16">
                <div data-revelar>
                    <x-publico.titulo id="titulo-preguntas" etiqueta="Orientación">Preguntas frecuentes</x-publico.titulo>
                </div>
                <div class="preguntas-suaves border-t border-marino-900/15">
                    @foreach ($preguntas as [$pregunta, $respuesta])
                        <details data-revelar style="--orden: {{ $loop->index }}" name="preguntas" class="group border-b border-marino-900/15" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-[16.5px] font-semibold transition-colors hover:text-real-600 [&::-webkit-details-marker]:hidden">
                                {{ $pregunta }}
                                <span aria-hidden="true" class="shrink-0 text-xl leading-none text-real-600 transition-transform duration-300 group-open:rotate-45 motion-reduce:transition-none">+</span>
                            </summary>
                            <p class="pb-5 text-[15px] text-pretty text-marino-800/85">{{ $respuesta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section aria-labelledby="titulo-empieza" class="bg-celeste-50 py-14">
            <div data-revelar class="{{ $contenedor }} flex flex-wrap items-center justify-between gap-6">
                <div class="flex flex-col gap-1.5">
                    <h2 id="titulo-empieza" class="font-serif text-[26px] font-semibold">Matrícula en línea</h2>
                    <p class="{{ $nota }}">Cree su cuenta, registre su pago y elija su horario.</p>
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
