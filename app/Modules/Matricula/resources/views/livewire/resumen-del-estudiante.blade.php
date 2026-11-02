@use('App\Modules\Academico\Data\SesionDeClase')
@use('App\Modules\Matricula\Enums\EstadoMatricula')
@use('App\Modules\Matricula\Enums\PasoDelEstudiante')
@use('App\Modules\Tramites\Contracts\TramitesObservados')
@use('App\Shared\Ui\Formato')
@php
    $grupo = $avance->grupoActual;
    $soloDocente = $esDocente && $avance->idiomas === [] && $avance->paso === PasoDelEstudiante::SinPago;
    $observados = app(TramitesObservados::class)($ficha->personaId);
    // What the student has to do now, and what is waiting on the CILN; each row is [title, detail, button, route].
    $tareas = array_values(array_filter([
        $soloDocente ? ['Tus grupos están en Mis grupos.', 'Registra allí la asistencia y las notas parciales.', 'Ir a mis grupos', 'filament.docente.pages.mis-grupos'] : null,
        ! $soloDocente && $avance->paso === PasoDelEstudiante::SinPago && $avance->pagos->ultimoRechazo !== null
            ? ['Tu último pago fue rechazado.', $avance->pagos->ultimoRechazo->motivo, 'Registrar pago', 'estudiante.pagos'] : null,
        ! $soloDocente && $avance->paso === PasoDelEstudiante::SinPago && $avance->pagos->ultimoRechazo === null && ! $avance->pagos->matriculaPorValidar
            ? ['Registra tu pago de matrícula.', 'Sube el recibo en Mis pagos; cuando el CILN lo valide podrás elegir tu grupo.', 'Registrar pago', 'estudiante.pagos'] : null,
        $avance->paso === PasoDelEstudiante::PagoValidado ? ['Tu pago de matrícula ya fue validado.', $cierreDeMatricula ? "Elige tu grupo de {$cierreDeMatricula->periodo} hasta el ".Formato::diaDeMes($cierreDeMatricula->fin).'.' : 'Elige tu grupo mientras la matrícula esté abierta.', 'Elegir mi grupo', 'estudiante.matricula'] : null,
        $clasesPorConfirmar > 0 ? [$clasesPorConfirmar === 1 ? 'Tienes 1 clase por confirmar.' : "Tienes {$clasesPorConfirmar} clases por confirmar.", 'Como delegado, confirma que el docente dictó la clase antes de que venza el plazo.', 'Confirmar clases', 'estudiante.clases'] : null,
        $observados > 0 ? [$observados === 1 ? 'Subsana tu trámite observado.' : "Subsana tus {$observados} trámites observados.", 'El CILN te pidió corregir o completar algo.', 'Ver mis trámites', 'estudiante.tramites'] : null,
        ...array_map(fn (array $clasificacion): array => ["Rinde tu examen de clasificación de {$clasificacion[0]}.", "Acércate al CILN antes del {$clasificacion[1]}.", 'Ver clasificación', 'estudiante.clasificacion'], $soloDocente ? [] : $clasificaciones),
    ]));
    $esperas = array_values(array_filter([
        ! $soloDocente && $avance->paso === PasoDelEstudiante::SinPago && $avance->pagos->ultimoRechazo === null && $avance->pagos->matriculaPorValidar
            ? ['Tu pago de matrícula está por validar.', 'Cuando el CILN lo valide podrás elegir tu grupo. Lleva tu boleta original al CILN.', 'Por validar', 'Ver mis pagos', 'estudiante.pagos'] : null,
        $enProceso > 0 ? [$enProceso === 1 ? 'Tu trámite está en revisión.' : "Tus {$enProceso} trámites están en revisión.", 'Te avisaremos por correo cuando avancen.', 'En revisión', 'Ver mis trámites', 'estudiante.tramites'] : null,
    ]));
    $hoy = \Carbon\CarbonImmutable::now('America/Lima');
    $lunes = $hoy->startOfWeek();
    $dias = array_filter($avance->horario, fn (array $clases, int $dia): bool => $dia < 7 || $clases !== [] || isset($plazos[$dia]), ARRAY_FILTER_USE_BOTH);
@endphp
<div class="grid gap-2 sm:gap-3.5 xl:grid-cols-[minmax(0,1fr)_340px]">
    <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
        <header class="flex flex-col gap-1 px-1 pt-2 sm:pt-3">
            <span class="text-apagado">{{ ucfirst($hoy->translatedFormat('l j \d\e F')) }}{{ $grupo ? " · Periodo {$grupo->periodo}" : " · {$ficha->documento}" }}</span>
            <h1 class="text-[clamp(28px,3vw,38px)] leading-[1.1] font-semibold tracking-[-0.03em]">
                Hola, {{ $ficha->nombres }}.
                <span>{{ match (count($tareas)) { 0 => 'Estás al día.', 1 => 'Tienes 1 cosa por hacer.', default => 'Tienes '.count($tareas).' cosas por hacer.' } }}</span>
            </h1>
        </header>

        @if ($tareas !== [])
            <x-portal.tarjeta>
                <h2 class="text-[13px] font-semibold tracking-[0.12em] text-enlace uppercase">Te toca a ti</h2>
                <ul class="flex flex-col divide-y divide-separador">
                    @foreach ($tareas as [$titulo, $detalle, $accion, $ruta])
                        <li class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-acento-claro"><x-heroicon-o-pencil-square aria-hidden="true" class="size-5 text-oro-700" /></span>
                            <span class="flex flex-1 flex-col">
                                <span class="text-[16.5px] font-semibold">{{ $titulo }}</span>
                                <span class="text-[14px] text-terciario">{{ $detalle }}</span>
                            </span>
                            <x-portal.boton :href="route($ruta)" class="self-start sm:self-center">{{ $accion }}</x-portal.boton>
                        </li>
                    @endforeach
                </ul>
            </x-portal.tarjeta>
        @endif

        @if ($esperas !== [])
            <x-portal.tarjeta>
                <h2 class="text-[13px] font-semibold tracking-[0.12em] text-apagado uppercase">Le toca al CILN</h2>
                <ul class="flex flex-col divide-y divide-separador">
                    @foreach ($esperas as [$titulo, $detalle, $estado, $accion, $ruta])
                        <li class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-lienzo"><x-heroicon-o-clock aria-hidden="true" class="size-5 text-apagado" /></span>
                            <span class="flex flex-1 flex-col">
                                <span class="text-[16.5px] font-semibold">{{ $titulo }}</span>
                                <span class="text-[14px] text-terciario">{{ $detalle }}</span>
                            </span>
                            <span class="flex items-center gap-3 self-start sm:self-center">
                                <x-portal.insignia color="warning">{{ $estado }}</x-portal.insignia>
                                <a href="{{ route($ruta) }}" class="enlace">{{ $accion }}</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-portal.tarjeta>
        @endif

        @if ($avance->paso === PasoDelEstudiante::Matriculado)
            <x-portal.tarjeta>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-verde-fondo"><x-heroicon-o-check aria-hidden="true" class="size-5 text-verde-texto" /></span>
                    <span class="flex flex-1 flex-col">
                        <span class="text-[16.5px] font-semibold">Ya estás matriculado en {{ $grupo?->idioma }} · Ciclo {{ $grupo?->cicloNumero }}.</span>
                        <span class="text-[14px] text-terciario">Descarga tu ficha y tu constancia desde Mis matrículas.</span>
                    </span>
                    <x-portal.boton :href="route('estudiante.matriculas')" variante="secundario" class="self-start sm:self-center">Ver mis matrículas</x-portal.boton>
                </div>
            </x-portal.tarjeta>
        @endif

        @unless ($soloDocente)
            <x-portal.tarjeta titulo="Mis idiomas">
                @if ($avance->idiomas === [])
                    <x-portal.vacio titulo="Aún no estudias ningún idioma en el CILN." icono="heroicon-o-language">Tu avance por ciclos aparecerá aquí después de tu primera matrícula.</x-portal.vacio>
                @else
                    <ul class="flex flex-col gap-4">
                        @foreach ($avance->idiomas as $idioma)
                            <li class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                <x-ciln.hoja-bandera :idioma="$idioma->idioma" size="34" rot="-8" />
                                <span class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="grid grid-cols-12 gap-[3px]" role="img" aria-label="{{ $idioma->idioma }}: ciclo {{ $idioma->ciclo }} de 12">
                                        @for ($ciclo = 1; $ciclo <= 12; $ciclo++)
                                            <span @class(['h-3 rounded', 'bg-acento' => $ciclo < $idioma->ciclo || ($ciclo === $idioma->ciclo && $idioma->terminado), 'bg-oro-200' => $ciclo === $idioma->ciclo && $idioma->estado === EstadoMatricula::Activa && ! $idioma->terminado, 'bg-gris-fondo' => $ciclo > $idioma->ciclo || ($ciclo === $idioma->ciclo && $idioma->estado !== EstadoMatricula::Activa)])></span>
                                        @endfor
                                    </span>
                                    <span class="flex justify-between text-[12px] text-apagado"><span>Básico</span><span>Intermedio</span><span>Avanzado</span></span>
                                </span>
                                <span class="text-[14px] text-secundario sm:w-[200px] sm:text-right">{{ match (true) { $idioma->terminado => "Terminó el ciclo {$idioma->ciclo} en {$idioma->periodo}", $idioma->estado === EstadoMatricula::Activa => "Cursando el ciclo {$idioma->ciclo}", default => "Retirada del ciclo {$idioma->ciclo} en {$idioma->periodo}" } }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-portal.tarjeta>
        @endunless
    </div>

    @unless ($soloDocente)
        <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
            <x-portal.tarjeta titulo="Esta semana">
                <x-slot:acciones><a href="{{ route('estudiante.reportes', ['ver' => 'horario']) }}" wire:navigate class="enlace text-[14px]">Ver horario completo</a></x-slot:acciones>
                @if ($grupo === null)
                    <x-portal.vacio titulo="Aún no tienes clases." icono="heroicon-o-calendar-days">Cuando te matricules, aquí verás tus clases de cada día.</x-portal.vacio>
                    @if ($plazos !== [])
                        <ul class="flex flex-col gap-1">
                            @foreach ($plazos as $dia => $avisos)
                                <li class="flex items-center gap-4 rounded-interna bg-lienzo px-3 py-2.5">
                                    <span class="flex w-9 flex-col items-center leading-tight">
                                        <span class="text-[11px] tracking-[0.08em] text-apagado uppercase">{{ SesionDeClase::DIAS_CORTOS[$dia] }}</span>
                                        <span class="text-[18px] font-semibold">{{ $lunes->addDays($dia - 1)->day }}</span>
                                    </span>
                                    <span class="flex flex-col text-[14.5px]">
                                        @foreach ($avisos as $aviso)
                                            <span class="font-medium text-ambar-texto">{{ $aviso }}</span>
                                        @endforeach
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <ul class="flex flex-col gap-1">
                        @foreach ($dias as $dia => $clases)
                            <li @class(['flex items-center gap-4 rounded-interna px-3 py-2.5', 'bg-acento-claro' => $dia === $hoy->dayOfWeekIso])>
                                <span class="flex w-9 flex-col items-center leading-tight">
                                    <span class="text-[11px] tracking-[0.08em] text-apagado uppercase">{{ SesionDeClase::DIAS_CORTOS[$dia] }}</span>
                                    <span class="text-[18px] font-semibold">{{ $lunes->addDays($dia - 1)->day }}</span>
                                </span>
                                <span class="flex flex-col text-[14.5px]">
                                    @foreach ($clases as $clase)
                                        <span>{{ $clase->idioma }} · {{ $clase->hora }} · {{ $clase->aula ?? 'Virtual' }}</span>
                                    @endforeach
                                    @foreach ($plazos[$dia] ?? [] as $aviso)
                                        <span class="font-medium text-ambar-texto">{{ $aviso }}</span>
                                    @endforeach
                                    @if ($clases === [] && ! isset($plazos[$dia]))
                                        <span class="text-apagado">Sin clases</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-portal.tarjeta>

            <x-portal.tarjeta titulo="Mis pagos">
                <x-slot:acciones><a href="{{ route('estudiante.pagos') }}" class="enlace text-[14px]">Ir a mis pagos</a></x-slot:acciones>
                <dl class="grid grid-cols-3 gap-2">
                    @foreach ([['Por validar', $avance->pagos->porValidar], ['Validados', $avance->pagos->validados], ['Para matrícula', $avance->pagosParaMatricula]] as [$etiqueta, $cantidad])
                        <div class="tarjeta-interna flex flex-col gap-1 p-3">
                            <dt class="text-[12.5px] text-terciario">{{ $etiqueta }}</dt>
                            <dd class="text-[22px] leading-none font-semibold">{{ $cantidad }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($avance->pagos->montoDeMatricula !== null)
                    <p class="text-[13.5px] text-secundario">Matrícula: S/ {{ $avance->pagos->montoDeMatricula }} según tu vínculo.</p>
                @endif
            </x-portal.tarjeta>

            <x-portal.tarjeta titulo="Mis documentos">
                <ul class="flex flex-col divide-y divide-separador">
                    @if ($avance->matriculaActual !== null)
                        <x-portal.fila titulo="Ficha de matrícula" :detalle="'Periodo '.$grupo?->periodo" class="px-0">
                            <x-slot:fin><a href="{{ route('estudiante.matriculas.ficha', $avance->matriculaActual) }}" target="_blank" rel="noopener" class="enlace" title="Abrir PDF"><x-heroicon-o-arrow-down-tray aria-hidden="true" class="size-5" /><span class="sr-only">Abrir PDF</span></a></x-slot:fin>
                        </x-portal.fila>
                        <x-portal.fila titulo="Constancia de inscripción" detalle="De tu matrícula vigente" class="px-0">
                            <x-slot:fin><a href="{{ route('estudiante.matriculas.constancia', $avance->matriculaActual) }}" target="_blank" rel="noopener" class="enlace" title="Abrir PDF"><x-heroicon-o-arrow-down-tray aria-hidden="true" class="size-5" /><span class="sr-only">Abrir PDF</span></a></x-slot:fin>
                        </x-portal.fila>
                    @endif
                    <x-portal.fila titulo="Constancias y certificados" detalle="Solicítalos y descárgalos desde Mis trámites" class="px-0">
                        <x-slot:fin><a href="{{ route('estudiante.tramites') }}" class="enlace">Ir a trámites</a></x-slot:fin>
                    </x-portal.fila>
                </ul>
            </x-portal.tarjeta>
        </div>
    @endunless
</div>
