@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('App\Modules\Docencia\Enums\EstadoAsistencia')
@use('App\Modules\Matricula\Livewire\MisReportes')
@use('Illuminate\Support\Str')
@php
    $colorDelEstado = fn (?EstadoFinal $estado): string => match ($estado) {
        EstadoFinal::Aprobado => 'success',
        EstadoFinal::Desaprobado => 'danger',
        EstadoFinal::Nsp => 'gray',
        null => 'warning',
    };
@endphp
<div>
@if ($pestana === 'todos')
<x-portal.perfil-del-estudiante :ficha="$ficha" :es-docente="$esDocente">
    <x-slot:acciones>
        <x-portal.boton :href="route('estudiante.perfil.datos')" variante="secundario" tamano="compacto" wire:navigate>Editar perfil</x-portal.boton>
    </x-slot:acciones>

    <x-portal.tarjeta titulo="Reportes">
        <x-portal.alerta>Aquí encuentras todos tus reportes académicos. Elige el periodo y abre el reporte que necesites; los PDF se abren en una pestaña nueva.</x-portal.alerta>
        @if ($periodos === [])
            <p class="text-secundario">Aún no tienes reportes. Aparecen cuando te matriculas en un periodo.</p>
        @else
            <div class="sm:max-w-[280px]">
                <x-portal.seleccion nombre="periodo" etiqueta="Periodo" :opciones="$periodos" vivo />
            </div>
        @endif
        <div class="grid gap-x-8 gap-y-6 pt-2 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($columnas as $titulo => $accesos)
                <section aria-label="{{ $titulo }}" class="flex flex-col gap-2">
                    <h2 class="text-[12.5px] font-semibold tracking-[0.12em] text-apagado uppercase">{{ $titulo }}</h2>
                    <ul class="flex flex-col gap-1">
                        @foreach ($accesos as $acceso)
                            <x-portal.acceso-documento :titulo="$acceso['titulo']" :detalle="$acceso['detalle']" :href="$acceso['href']" :motivo="$acceso['motivo']" :color="$acceso['color']" :icono="$acceso['icono']" :pdf="$acceso['pdf']" />
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </x-portal.tarjeta>

    <div class="grid gap-2 sm:gap-3.5 md:grid-cols-2">
        <x-portal.tarjeta class="items-center text-center">
            <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-full bg-acento-claro"><x-heroicon-o-user-circle class="size-7 text-acento" /></span>
            <div class="flex flex-col gap-1">
                <h2 class="text-[19px] font-semibold">Perfil</h2>
                <p class="text-secundario">Tus datos, tu matrícula del periodo y tus documentos a la mano.</p>
            </div>
            <x-portal.boton :href="route('estudiante.inicio')" tamano="compacto" wire:navigate>Ir a mi perfil</x-portal.boton>
        </x-portal.tarjeta>
        <x-portal.tarjeta class="items-center text-center">
            <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-full bg-acento-claro"><x-heroicon-o-pencil-square class="size-7 text-acento" /></span>
            <div class="flex flex-col gap-1">
                <h2 class="text-[19px] font-semibold">Editar datos</h2>
                <p class="text-secundario">Cambia tu celular, tu dirección, tu fotografía y tu contraseña.</p>
            </div>
            <x-portal.boton :href="route('estudiante.perfil.datos')" tamano="compacto" wire:navigate>Modificar datos</x-portal.boton>
        </x-portal.tarjeta>
    </div>
</x-portal.perfil-del-estudiante>
@else
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado :titulo="MisReportes::PESTANAS[$pestana]">
        <a href="{{ route('estudiante.reportes', ['periodo' => $periodo]) }}" wire:navigate class="enlace inline-flex items-center gap-1"><x-heroicon-o-arrow-left aria-hidden="true" class="size-4" />Todos los reportes</a>
        @if ($periodos !== [])
            <x-slot:acciones>
                <x-portal.seleccion nombre="periodo" etiqueta="Periodo" :opciones="$periodos" vivo class="min-w-[220px]" />
            </x-slot:acciones>
        @endif
    </x-portal.encabezado>

    <div class="grid items-start gap-2 sm:gap-3.5 lg:grid-cols-[minmax(0,1fr)_300px]">
        <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
            <div id="panel-reportes" class="flex flex-col gap-2 sm:gap-3.5">
                @if ($pestana === 'aprobado')
                    @forelse ($records as $record)
                        <x-portal.tarjeta wire:key="aprobado-{{ Str::slug($record->idioma) }}">
                            <header class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <x-ciln.hoja-bandera :idioma="$record->idioma" size="34" rot="-8" />
                                    <div class="flex flex-col leading-tight">
                                        <h2 class="text-[19px] font-semibold">{{ $record->idioma }}</h2>
                                        <span class="text-secundario">Promedio de ciclos aprobados: <strong class="text-tinta">{{ $record->promedio ?? '—' }}</strong></span>
                                    </div>
                                </div>
                            </header>
                            <ul role="list" class="flex flex-col">
                                @foreach ($record->ciclos as $ciclo)
                                    @php($nota = $ciclo->resultado)
                                    <x-portal.fila wire:key="ciclo-{{ $nota->actaId }}" :titulo="'Ciclo '.$nota->cicloNumero.' · '.$nota->cicloNombre" :detalle="$nota->nivel->etiqueta().' · '.$nota->periodo.($ciclo->veces > 1 ? ' · llevado '.$ciclo->veces.' veces' : '')">
                                        <x-slot:fin>
                                            <span class="font-semibold">{{ $nota->promedio ?? '—' }}</span>
                                            <x-portal.insignia :color="$colorDelEstado($nota->estadoFinal)">{{ $nota->estadoFinal->etiqueta() }}</x-portal.insignia>
                                        </x-slot:fin>
                                    </x-portal.fila>
                                @endforeach
                            </ul>
                            @foreach ($record->faltantes as $nivel)
                                <p class="text-secundario">{{ $nivel->descripcion() }}</p>
                            @endforeach
                        </x-portal.tarjeta>
                    @empty
                        <x-portal.tarjeta>
                            <x-portal.vacio titulo="Aún no apruebas ningún ciclo." icono="heroicon-o-academic-cap">Aquí verás cada ciclo cuando su acta final se cierre.</x-portal.vacio>
                        </x-portal.tarjeta>
                    @endforelse
                    @if ($records !== [])
                        <p class="px-1 text-[13px] text-terciario">Informativo, sin valor oficial; para trámites solicita la constancia de notas.</p>
                    @endif
                @elseif ($reportes === null)
                    <x-portal.tarjeta>
                        <x-portal.vacio titulo="Aún no tienes reportes." icono="heroicon-o-document-chart-bar">Aparecen cuando te matriculas en un periodo.</x-portal.vacio>
                    </x-portal.tarjeta>
                @elseif ($pestana === 'horario')
                    <x-portal.hoja titulo="Horario personalizado" :periodo="$reportes->periodo">
                        <x-slot:acciones>
                            <x-portal.boton variante="secundario" :href="route('estudiante.reportes.horario', $reportes->periodoId)" target="_blank" rel="noopener" tamano="compacto">PDF<span class="sr-only"> del horario del periodo {{ $reportes->periodo }}</span></x-portal.boton>
                            <x-portal.boton variante="secundario" :href="route('estudiante.reportes.horario.imprimir', $reportes->periodoId)" target="_blank" rel="noopener" tamano="compacto">Imprimir<span class="sr-only"> el horario del periodo {{ $reportes->periodo }}</span></x-portal.boton>
                        </x-slot:acciones>

                        @if ($reportes->horario->grupos === [])
                            <p class="text-secundario">No tiene clases programadas en este periodo.</p>
                        @else
                            @include('matricula::documentos.horario', ['horario' => $reportes->horario, 'pdf' => false])
                        @endif
                    </x-portal.hoja>
                @else
                    @foreach ($reportes->datos->matriculas as $matricula)
                        @php($grupo = $reportes->datos->grupos[$matricula->grupo_id])
                        @php($avance = $reportes->avances[$matricula->id])
                        @php($asistencia = $reportes->asistencias[$matricula->id])
                        <x-portal.tarjeta wire:key="avance-{{ $matricula->id }}">
                            <header class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <x-ciln.hoja-bandera :idioma="$grupo->idioma" size="34" rot="-8" />
                                    <div class="flex flex-col leading-tight">
                                        <h2 class="text-[19px] font-semibold">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }}</h2>
                                        <span class="text-secundario">{{ $grupo->codigo }} · Prof. {{ $reportes->datos->docentes[$matricula->grupo_id] ?? 'por asignar' }}</span>
                                    </div>
                                </div>
                                <x-portal.insignia :color="$colorDelEstado($avance->estadoFinal)">{{ $avance->estadoFinal?->etiqueta() ?? 'Acta abierta' }}</x-portal.insignia>
                            </header>

                            <div class="grid gap-3 md:grid-cols-2">
                                <section aria-label="Notas de {{ $grupo->codigo }}" class="flex flex-col gap-3 rounded-interna bg-lienzo p-4">
                                    <h3 class="text-secundario">Notas</h3>
                                    @if (! $avance->disponible)
                                        <p class="text-secundario">{{ $avance->aviso }}</p>
                                    @else
                                        <ul role="list" class="flex flex-col gap-3">
                                            @foreach ($avance->componentes as $componente)
                                                <li wire:key="componente-{{ $matricula->id }}-{{ $componente->codigo }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1.5">
                                                    <span>{{ $componente->nombre }} <span class="text-terciario">· {{ $componente->grupo->etiqueta() }}</span></span>
                                                    <span class="row-span-2 text-right font-semibold tabular-nums">{{ $componente->nota ?? '—' }}</span>
                                                    <span aria-hidden="true" class="h-1.5 overflow-hidden rounded-full bg-piedra-200">
                                                        <span class="block h-full rounded-full bg-acento" style="width: {{ $componente->nota === null ? 0 : min(100, round((float) $componente->nota * 5)) }}%"></span>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <dl class="grid grid-cols-2 gap-2 border-t border-separador pt-3 text-secundario">
                                            <div><dt>Prom. habilidades</dt><dd class="font-semibold text-tinta tabular-nums">{{ $avance->promedioHabilidades ?? '—' }}</dd></div>
                                            <div><dt>Prom. exámenes</dt><dd class="font-semibold text-tinta tabular-nums">{{ $avance->promedioExamenes ?? '—' }}</dd></div>
                                        </dl>
                                        <p class="mt-auto flex items-baseline justify-between gap-2 border-t border-separador pt-3">
                                            <span class="text-secundario">{{ $avance->actaCerrada ? 'Promedio final' : 'Promedio parcial' }}</span>
                                            <span class="text-[28px] leading-none font-semibold tabular-nums">{{ $avance->promedio ?? '—' }}</span>
                                        </p>
                                    @endif
                                </section>

                                <section aria-label="Asistencia de {{ $grupo->codigo }}" class="flex flex-col gap-3 rounded-interna bg-lienzo p-4">
                                    <h3 class="text-secundario">Asistencia</h3>
                                    <p class="text-[44px] leading-none font-semibold tracking-[-0.03em] tabular-nums">{{ $asistencia->porcentajeSobreDictadas() === null ? '—' : $asistencia->porcentajeSobreDictadas().' %' }}</p>
                                    <p class="text-secundario">{{ $asistencia->dictadas }} de {{ $asistencia->programadas }} clases dictadas · {{ $asistencia->faltas }} {{ $asistencia->faltas === 1 ? 'falta' : 'faltas' }}</p>
                                    @if (max($asistencia->programadas, $asistencia->dictadas) > 0)
                                        <ol aria-hidden="true" class="grid grid-cols-[repeat(auto-fill,minmax(22px,1fr))] gap-1">
                                            @foreach ($asistencia->clases as $clase)
                                                <li title="{{ $clase->fecha->format('d/m/Y') }} · {{ $clase->efectivo->etiqueta() }}" @class(['h-3.5 rounded-[3px]', 'bg-positivo' => $clase->efectivo === EstadoAsistencia::Presente, 'bg-peligro/50' => $clase->efectivo !== EstadoAsistencia::Presente])></li>
                                            @endforeach
                                            @for ($i = $asistencia->dictadas; $i < $asistencia->programadas; $i++)
                                                <li class="h-3.5 rounded-[3px] bg-piedra-200"></li>
                                            @endfor
                                        </ol>
                                    @endif
                                    <p class="text-[13px] text-terciario">{{ $asistencia->porcentajeSobreProgramadas() === null ? '—' : $asistencia->porcentajeSobreProgramadas().' %' }} sobre las clases programadas</p>
                                </section>
                            </div>
                        </x-portal.tarjeta>
                    @endforeach
                @endif
            </div>
        </div>

        @if ($reportes !== null || $records !== [])
            <x-portal.tarjeta titulo="Descargar en PDF" class="lg:sticky lg:top-4">
                <ul role="list" class="-mt-1 flex flex-col">
                    @if ($reportes !== null)
                        <x-matricula::reportes.descarga :href="route('estudiante.reportes.boleta', $reportes->periodoId)" titulo="Boleta de notas" detalle="Todos tus cursos del periodo" :descripcion="'de la boleta de notas del periodo '.$reportes->periodo" />
                        @foreach ($reportes->datos->matriculas as $matricula)
                            @php($grupo = $reportes->datos->grupos[$matricula->grupo_id])
                            <x-matricula::reportes.descarga wire:key="descarga-avance-{{ $matricula->id }}" :href="route('estudiante.reportes.avance', $matricula)" titulo="Avance de notas" :detalle="$grupo->idioma.' '.$grupo->cicloNumero.' · por componente'" :descripcion="'del avance de notas del grupo '.$grupo->codigo" />
                            <x-matricula::reportes.descarga wire:key="descarga-asistencia-{{ $matricula->id }}" :href="route('estudiante.reportes.asistencia', $matricula)" titulo="Asistencia" :detalle="$grupo->idioma.' '.$grupo->cicloNumero.' · clase por clase'" :descripcion="'de la asistencia del grupo '.$grupo->codigo" />
                        @endforeach
                        <x-matricula::reportes.descarga :href="route('estudiante.reportes.horario', $reportes->periodoId)" titulo="Horario" detalle="Tu semana del periodo" :descripcion="'del horario del periodo '.$reportes->periodo" />
                        <li class="-mt-2 pb-3 text-[13px]">
                            <a href="{{ route('estudiante.reportes.horario.imprimir', $reportes->periodoId) }}" target="_blank" rel="noopener" class="enlace">Versión para imprimir<span class="sr-only"> del horario del periodo {{ $reportes->periodo }}</span></a>
                        </li>
                    @endif
                    @foreach ($records as $record)
                        <x-matricula::reportes.descarga wire:key="descarga-record-{{ Str::slug($record->idioma) }}" :href="route('estudiante.reportes.record', Str::slug($record->idioma))" :titulo="'Récord de '.$record->idioma" detalle="Todos los ciclos" :descripcion="'del récord de '.$record->idioma" />
                    @endforeach
                    @if ($records !== [])
                        <x-matricula::reportes.descarga :href="route('estudiante.reportes.record')" titulo="Récord completo" detalle="Todos tus idiomas" descripcion="del récord de todos los idiomas" />
                    @endif
                </ul>
            </x-portal.tarjeta>
        @endif
    </div>
</div>
@endif
</div>
