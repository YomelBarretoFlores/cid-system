@use('App\Modules\Matricula\Enums\EstadoMatricula')
@use('App\Modules\Matricula\Enums\PasoDelEstudiante')
@php
    $grupo = $avance->grupoActual;
    $hoy = \Carbon\CarbonImmutable::now('America/Lima');
    $lunes = $hoy->startOfWeek();
    $dias = array_filter($avance->horario, fn (array $clases, int $dia): bool => $dia < 7 || $clases !== [] || isset($plazos[$dia]), ARRAY_FILTER_USE_BOTH);
@endphp
<div class="grid gap-2 sm:gap-3.5 2xl:grid-cols-[minmax(0,1fr)_300px]">
    <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
        <x-portal.tarjeta>
            <header class="flex flex-col gap-1">
                <span class="text-[13px] font-semibold tracking-[0.12em] text-apagado uppercase">Matrícula{{ $grupo ? " · Periodo {$grupo->periodo}" : '' }}</span>
                @if ($avance->paso === PasoDelEstudiante::Matriculado)
                    <h2 class="text-[clamp(20px,2vw,26px)] leading-tight font-semibold text-verde-texto">Tu matrícula está validada, todo está en orden.</h2>
                    <p class="text-secundario">Estás matriculado en {{ $grupo?->idioma }} · Ciclo {{ $grupo?->cicloNumero }} ({{ $grupo?->nivel }}), grupo {{ $grupo?->codigo }}. Descarga tu ficha de matrícula y conserva una copia.</p>
                @elseif ($avance->paso === PasoDelEstudiante::PagoValidado)
                    <h2 class="text-[clamp(20px,2vw,26px)] leading-tight font-semibold">Tu pago está validado: ya puedes elegir tu grupo.</h2>
                @elseif (! $soloDocente)
                    <h2 class="text-[clamp(20px,2vw,26px)] leading-tight font-semibold">Aún no tienes una matrícula activa.</h2>
                @endif
            </header>
            @if ($avance->paso === PasoDelEstudiante::Matriculado && $avance->matriculaActual !== null)
                <div class="flex flex-wrap gap-2">
                    <x-portal.boton :href="route('estudiante.matriculas.ficha', $avance->matriculaActual)" variante="secundario" tamano="compacto" target="_blank" rel="noopener">Ver ficha de matrícula</x-portal.boton>
                    <x-portal.boton :href="route('estudiante.pagos')" variante="secundario" tamano="compacto">Ver registro de pagos</x-portal.boton>
                </div>
            @endif

            @if ($tareas !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="text-[13px] font-semibold tracking-[0.12em] text-enlace uppercase">Te toca a ti</h3>
                    <ul class="flex flex-col gap-2">
                        @foreach ($tareas as $tarea)
                            <li class="flex flex-col gap-3 rounded-xl bg-azul-fondo px-4 py-3.5 sm:flex-row sm:items-center">
                                <span class="flex flex-1 flex-col">
                                    <span class="font-semibold text-azul-texto">{{ $tarea->titulo }}</span>
                                    <span class="text-[14px] text-azul-texto/85">{{ $tarea->detalle }}</span>
                                </span>
                                <x-portal.boton :href="route($tarea->ruta)" tamano="compacto" class="self-start sm:self-center">{{ $tarea->accion }}</x-portal.boton>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($esperas !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="text-[13px] font-semibold tracking-[0.12em] text-apagado uppercase">Le toca al CILN</h3>
                    <ul class="flex flex-col gap-2">
                        @foreach ($esperas as $espera)
                            <li class="flex flex-col gap-3 rounded-xl bg-ambar-fondo px-4 py-3.5 sm:flex-row sm:items-center">
                                <span class="flex flex-1 flex-col">
                                    <span class="font-semibold text-ambar-texto">{{ $espera->titulo }}</span>
                                    <span class="text-[14px] text-ambar-texto/85">{{ $espera->detalle }}</span>
                                </span>
                                <span class="flex items-center gap-3 self-start sm:self-center">
                                    <x-portal.insignia color="warning">{{ $espera->estado }}</x-portal.insignia>
                                    <a href="{{ route($espera->ruta) }}" class="enlace">{{ $espera->accion }}</a>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless ($soloDocente)
                <div class="flex flex-col gap-3 border-t border-separador pt-4">
                    <h3 class="text-[13px] font-semibold tracking-[0.12em] text-apagado uppercase">Tus pagos</h3>
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
                </div>
            @endunless
        </x-portal.tarjeta>

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
                                            <span @class(['h-3 rounded', 'bg-acento' => $ciclo < $idioma->ciclo || ($ciclo === $idioma->ciclo && $idioma->terminado), 'bg-acento/40' => $ciclo === $idioma->ciclo && $idioma->estado === EstadoMatricula::Activa && ! $idioma->terminado, 'bg-gris-fondo' => $ciclo > $idioma->ciclo || ($ciclo === $idioma->ciclo && $idioma->estado !== EstadoMatricula::Activa)])></span>
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

        @unless ($soloDocente)
            <x-portal.tarjeta titulo="Esta semana">
                <x-slot:acciones><a href="{{ route('estudiante.reportes', ['ver' => 'horario']) }}" wire:navigate class="enlace text-[14px]">Ver horario completo</a></x-slot:acciones>
                @if ($grupo === null)
                    <x-portal.vacio titulo="Aún no tienes clases." icono="heroicon-o-calendar-days">Cuando te matricules, aquí verás tus clases de cada día.</x-portal.vacio>
                    @if ($plazos !== [])
                        <ul class="flex flex-col gap-1">
                            @foreach ($plazos as $dia => $avisos)
                                <x-matricula::dia-de-la-semana :dia="$dia" :numero="$lunes->addDays($dia - 1)->day" :avisos="$avisos" class="bg-lienzo" />
                            @endforeach
                        </ul>
                    @endif
                @else
                    <ul class="flex flex-col gap-1">
                        @foreach ($dias as $dia => $clases)
                            <x-matricula::dia-de-la-semana :dia="$dia" :numero="$lunes->addDays($dia - 1)->day" :clases="$clases" :avisos="$plazos[$dia] ?? []" :class="$dia === $hoy->dayOfWeekIso ? 'bg-acento-claro' : ''" />
                        @endforeach
                    </ul>
                @endif
            </x-portal.tarjeta>

        @endunless
    </div>

    @unless ($soloDocente)
        <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
            <x-portal.tarjeta titulo="Documentos">
                @if ($documentos === [])
                    <p class="text-[14.5px] text-secundario">Tus documentos aparecerán cuando te matricules.</p>
                @else
                    <ul class="flex flex-col gap-1">
                        @foreach ($documentos as $documento)
                            <x-portal.acceso-documento :titulo="$documento['titulo']" :detalle="$documento['detalle']" :href="$documento['href']" :color="$documento['color']" :icono="$documento['icono']" pdf />
                        @endforeach
                    </ul>
                @endif
            </x-portal.tarjeta>

            <x-portal.tarjeta titulo="Enlaces rápidos">
                <ul class="flex flex-col gap-1">
                    <x-portal.acceso-documento titulo="Trámites" detalle="Constancias y certificados" :href="route('estudiante.tramites')" color="verde" icono="heroicon-o-document-text" />
                    <x-portal.acceso-documento titulo="Verificar un documento" detalle="Con su código o QR" :href="route('verificacion.documento')" color="lila" icono="heroicon-o-qr-code" nueva-pestana />
                    <x-portal.acceso-documento titulo="Web del CILN" detalle="Oferta, costos y fechas" :href="route('portada')" color="ambar" icono="heroicon-o-globe-alt" nueva-pestana />
                </ul>
            </x-portal.tarjeta>
        </div>
    @endunless
</div>
