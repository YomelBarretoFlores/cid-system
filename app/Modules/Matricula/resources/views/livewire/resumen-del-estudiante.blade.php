@use('App\Modules\Academico\Data\SesionDeClase')
@use('App\Modules\Matricula\Enums\EstadoMatricula')
@use('App\Modules\Matricula\Enums\PasoDelEstudiante')
@php
    $grupo = $avance->grupoActual;
    [$titulo, $descripcion, $accion, $ruta] = match ($avance->paso) {
        PasoDelEstudiante::Matriculado => ["Ya estás matriculado en {$grupo?->idioma} · Ciclo {$grupo?->cicloNumero}.", 'Descarga tu ficha y tu constancia desde Mis matrículas.', 'Ver mis matrículas', 'estudiante.matriculas'],
        PasoDelEstudiante::PagoValidado => ['Tu pago de matrícula ya fue validado.', 'Elige tu grupo mientras la matrícula esté abierta.', 'Elegir mi grupo', 'estudiante.matricula'],
        PasoDelEstudiante::SinPago => match (true) {
            $avance->pagos->ultimoRechazo !== null => ['Tu último pago fue rechazado.', $avance->pagos->ultimoRechazo->motivo, 'Registrar pago', 'estudiante.pagos'],
            $avance->pagos->matriculaPorValidar => ['Tu pago de matrícula está por validar.', 'Cuando el CILN lo valide podrás elegir tu grupo.', 'Ver mis pagos', 'estudiante.pagos'],
            default => ['Registra tu pago de matrícula.', 'Sube el recibo en Mis pagos; cuando el CILN lo valide podrás elegir tu grupo.', 'Registrar pago', 'estudiante.pagos'],
        },
    };
    $hoy = now('America/Lima')->dayOfWeekIso;
    $soloDocente = $esDocente && $avance->idiomas === [] && $avance->paso === PasoDelEstudiante::SinPago;
    if ($soloDocente) {
        [$titulo, $descripcion, $accion, $ruta] = ['Tus grupos están en Mis grupos.', 'Registra allí la asistencia y las notas parciales.', 'Ir a mis grupos', 'filament.docente.pages.mis-grupos'];
    }
    $dias = array_filter($avance->horario, fn (array $clases, int $dia): bool => $dia < 7 || $clases !== [], ARRAY_FILTER_USE_BOTH);
@endphp
<div class="flex flex-col gap-2 sm:gap-3.5">
    <section class="relative grid gap-7 overflow-hidden rounded-tarjeta bg-tarjeta p-6 sm:px-[34px] sm:py-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
        <x-ciln.emblema decorativo class="pointer-events-none absolute -top-[70px] -right-[60px] w-[340px] opacity-[0.07]" />
        <div class="relative flex flex-col gap-3.5">
            <span class="text-apagado">{{ $grupo ? "Periodo {$grupo->periodo}" : "{$ficha->nombreCompleto} · {$ficha->documento}" }}</span>
            <h1 class="text-[clamp(32px,3.4vw,46px)] leading-[1.05] font-semibold tracking-[-0.03em]">Hola, {{ $ficha->nombres }}.</h1>
            <div class="flex max-w-[560px] flex-col gap-1">
                <span class="text-[19px] font-semibold text-pretty">{{ $titulo }}</span>
                <span class="text-pretty text-secundario">{{ $descripcion }}</span>
            </div>
            <ul @class(['mt-1 flex flex-wrap gap-2', 'hidden' => $soloDocente]) aria-label="Avance de tu matrícula">
                @foreach ([[PasoDelEstudiante::PagoValidado, 'Pago validado'], [PasoDelEstudiante::Matriculado, 'Matrícula']] as [$paso, $etiqueta])
                    @php($logrado = $avance->paso->alcanzo($paso))
                    <li @class(['flex items-center gap-2 rounded-pildora py-2 pr-3.5 pl-2 text-sm font-medium', 'bg-verde-fondo text-verde-texto' => $logrado, 'bg-lienzo text-apagado' => ! $logrado])>
                        <span @class(['flex size-6 items-center justify-center rounded-full text-xs font-bold', 'bg-positivo text-white' => $logrado, 'bg-piedra-300 text-white' => ! $logrado])>
                            @if ($logrado)
                                <x-heroicon-o-check aria-hidden="true" class="size-3.5 stroke-[2.5]" />
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>
                        {{ $etiqueta }}<span class="sr-only">{{ $logrado ? ': listo' : ': pendiente' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <x-portal.boton :href="route($ruta)" class="relative self-start lg:self-end">{{ $accion }} <x-heroicon-o-arrow-right aria-hidden="true" class="size-4" /></x-portal.boton>
    </section>

    @unless ($soloDocente)
    <div class="grid gap-2 sm:gap-3.5 lg:grid-cols-2">
        <x-portal.tarjeta titulo="Mis pagos">
            <x-slot:acciones><a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Ir a mis pagos</a></x-slot:acciones>
            <dl class="grid grid-cols-3 gap-2">
                @foreach ([['Por validar', $avance->pagos->porValidar], ['Validados', $avance->pagos->validados], ['Para matrícula', $avance->pagosParaMatricula]] as [$etiqueta, $cantidad])
                    <div class="tarjeta-interna flex flex-col gap-1 p-3.5">
                        <dt class="text-[13px] text-terciario">{{ $etiqueta }}</dt>
                        <dd class="text-[26px] leading-none font-semibold">{{ $cantidad }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($avance->pagos->montoDeMatricula !== null)
                <p class="text-[14px] text-secundario">Matrícula: S/ {{ $avance->pagos->montoDeMatricula }} según tu vínculo.</p>
            @endif
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Mis documentos">
            <ul class="flex flex-col gap-1">
                @if ($avance->matriculaActual !== null)
                    <x-portal.fila titulo="Ficha de matrícula" :detalle="'Periodo '.$grupo?->periodo">
                        <x-slot:fin><a href="{{ route('estudiante.matriculas.ficha', $avance->matriculaActual) }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">Abrir PDF</a></x-slot:fin>
                    </x-portal.fila>
                    <x-portal.fila titulo="Constancia de inscripción" detalle="De tu matrícula vigente">
                        <x-slot:fin><a href="{{ route('estudiante.matriculas.constancia', $avance->matriculaActual) }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">Abrir PDF</a></x-slot:fin>
                    </x-portal.fila>
                @endif
                <x-portal.fila titulo="Constancias y certificados" detalle="Solicítalos y descárgalos desde Mis trámites">
                    <x-slot:fin><a href="{{ route('estudiante.tramites') }}" class="font-semibold underline underline-offset-2">Ir a trámites</a></x-slot:fin>
                </x-portal.fila>
            </ul>
        </x-portal.tarjeta>
    </div>

    <x-portal.tarjeta titulo="Tu horario semanal">
        <x-slot:acciones><span class="text-apagado">Grupos con matrícula activa</span></x-slot:acciones>
        @if ($grupo === null)
            <x-portal.vacio titulo="Aún no tienes clases." icono="heroicon-o-calendar-days">Cuando te matricules, aquí verás tus clases de cada día.</x-portal.vacio>
        @else
            <div class="-mx-1 overflow-x-auto px-1">
                <div class="grid min-w-[560px] gap-2" style="grid-template-columns: repeat({{ count($dias) }}, minmax(0, 1fr))">
                    @foreach ($dias as $dia => $clases)
                        <div @class(['flex min-h-[150px] flex-col gap-2 rounded-interna px-2.5 py-3', 'bg-acento-claro' => $dia === $hoy, 'bg-lienzo' => $dia !== $hoy])>
                            <span @class(['text-[13px]', 'font-semibold text-enlace' => $dia === $hoy, 'text-apagado' => $dia !== $hoy])>{{ SesionDeClase::DIAS_CORTOS[$dia] }}</span>
                            @foreach ($clases as $clase)
                                <div class="flex flex-col gap-1.5 rounded-xl bg-tarjeta px-2 py-2.5 shadow-[0_1px_2px_rgb(29_27_22/0.06)]">
                                    <x-ciln.hoja-bandera :idioma="$clase->idioma" size="22" />
                                    <span class="text-[13px] font-semibold">{{ $clase->hora }}</span>
                                    <span class="text-xs text-terciario">{{ $clase->aula ?? 'Virtual' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="Mis idiomas">
        <x-slot:acciones><span class="text-apagado">Ciclos I a XII · Básico, Intermedio y Avanzado</span></x-slot:acciones>
        @if ($avance->idiomas === [])
            <x-portal.vacio titulo="Aún no estudias ningún idioma en el CILN." icono="heroicon-o-language">Tu avance por ciclos aparecerá aquí después de tu primera matrícula.</x-portal.vacio>
        @else
            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,280px),1fr))] gap-3">
                @foreach ($avance->idiomas as $idioma)
                    <div class="tarjeta-interna flex flex-col gap-3.5 p-5">
                        <span class="flex items-center gap-3">
                            <x-ciln.hoja-bandera :idioma="$idioma->idioma" size="40" rot="-8" />
                            <span class="flex flex-col">
                                <span class="text-[17px] font-semibold">{{ $idioma->idioma }}</span>
                                <span class="text-[13.5px] text-terciario">{{ match ($idioma->estado) { EstadoMatricula::Activa => "Cursando el ciclo {$idioma->ciclo}", EstadoMatricula::Retirada => "Retirada del ciclo {$idioma->ciclo} en {$idioma->periodo}" } }}</span>
                            </span>
                        </span>
                        <span class="grid grid-cols-12 gap-[3px]" role="img" aria-label="Ciclo {{ $idioma->ciclo }} de 12">
                            @for ($ciclo = 1; $ciclo <= 12; $ciclo++)
                                <span @class(['h-[22px] rounded-md', 'bg-acento' => $ciclo < $idioma->ciclo, 'bg-oro-200' => $ciclo === $idioma->ciclo && $idioma->estado === EstadoMatricula::Activa, 'bg-gris-fondo' => $ciclo > $idioma->ciclo || ($ciclo === $idioma->ciclo && $idioma->estado !== EstadoMatricula::Activa)])></span>
                            @endfor
                        </span>
                        <span class="flex justify-between text-[12.5px] text-apagado"><span>Básico</span><span>Intermedio</span><span>Avanzado</span></span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-portal.tarjeta>
    @endunless
</div>
