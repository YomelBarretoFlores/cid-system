@use('App\Modules\Certificacion\Enums\EstadoActa')
@use('App\Modules\Academico\Data\SesionDeClase')
@use('App\Modules\Docencia\Enums\EstadoSilabo')
<x-filament-panels::page>
    @if ($grupos === [])
        <x-filament::empty-state heading="Aún no tienes grupos asignados." description="Cuando la coordinación te asigne un grupo, aparecerá aquí." icon="heroicon-o-user-group" />
    @else
        <ul role="list" class="grid grid-cols-[minmax(0,1fr)] gap-4 md:grid-cols-[repeat(auto-fill,minmax(330px,1fr))]">
            @foreach ($grupos as $resumen)
                @php
                    $grupo = $resumen->grupo;
                    [$siguiente, $rutaSiguiente] = $resumen->siguientePaso();
                    $pasos = [
                        ['Sílabo', EstadoSilabo::etiquetaDe($resumen->silabo), $resumen->silaboListo(), 'filament.docente.pages.grupos.silabo'],
                        ['Asistencia', "{$resumen->dictadas} de {$resumen->programadas} clases".($resumen->esHoy ? ' · hoy' : ''), $resumen->programadas > 0 && $resumen->dictadas >= $resumen->programadas, 'filament.docente.pages.grupos.asistencia'],
                        ['Notas', $resumen->notasEsperadas === 0 ? 'Sin estudiantes o sin esquema' : "{$resumen->notasRegistradas} de {$resumen->notasEsperadas} notas", $resumen->notasCompletas(), 'filament.docente.pages.grupos.notas'],
                        ['Acta final', $resumen->detalleDelActa(), $resumen->acta === EstadoActa::Cerrada, 'filament.docente.pages.grupos.acta'],
                    ];
                @endphp
                <li wire:key="grupo-{{ $grupo->grupoId }}">
                    <x-filament::section class="h-full">
                        <article aria-labelledby="grupo-{{ $grupo->grupoId }}-titulo" class="flex h-full flex-col gap-4">
                            <header class="flex items-start justify-between gap-2.5">
                                <div class="flex items-center gap-3">
                                    <x-ciln.hoja-bandera :idioma="$grupo->idioma" size="40" rot="-8" />
                                    <div class="flex flex-col">
                                        <h2 id="grupo-{{ $grupo->grupoId }}-titulo" class="text-[17px] font-semibold text-tinta">Grupo {{ $grupo->codigo }}</h2>
                                        <span class="text-sm text-terciario">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }}</span>
                                    </div>
                                </div>
                                <span class="shrink-0"><x-filament::badge :color="$grupo->estado->color()">{{ $grupo->estado->etiqueta() }}</x-filament::badge></span>
                            </header>

                            <div class="flex flex-col gap-1 text-sm">
                                <span class="text-apagado">{{ $grupo->periodo }} ({{ $grupo->periodoInicio->format('d/m/Y') }} al {{ $grupo->periodoFin->format('d/m/Y') }})</span>
                                <span class="text-tinta">
                                    @forelse (SesionDeClase::compactar($grupo->sesiones) as $horario)
                                        <span class="block">{{ $horario }}</span>
                                    @empty
                                        Horario por confirmar
                                    @endforelse
                                </span>
                            </div>

                            <p @class(['flex items-center gap-2 rounded-interna px-3 py-2 text-sm', 'bg-acento-claro font-medium text-tinta' => $resumen->esHoy, 'bg-lienzo text-secundario' => ! $resumen->esHoy])>
                                <x-heroicon-o-calendar aria-hidden="true" class="size-4 shrink-0" />{{ $resumen->proximaClase() }}
                            </p>

                            <ol class="flex flex-col gap-1" aria-label="Avance del grupo {{ $grupo->codigo }}">
                                @foreach ($pasos as [$paso, $detalle, $listo, $ruta])
                                    @php($actual = $ruta === $rutaSiguiente && ! $listo)
                                    <li>
                                        <a href="{{ route($ruta, $grupo->grupoId) }}" @class(['flex items-center gap-3 rounded-interna px-2.5 py-2 hover:bg-hover-suave focus-visible:shadow-foco focus-visible:outline-none', 'bg-acento-claro' => $actual])>
                                            <span @class(['flex size-6 shrink-0 items-center justify-center rounded-full',
                                                'bg-positivo text-white' => $listo, 'bg-tinta' => ! $listo && $actual, 'border-2 border-piedra-300' => ! $listo && ! $actual])>
                                                @if ($listo)<x-heroicon-o-check aria-hidden="true" class="size-3.5 stroke-[2.5]" />@endif
                                            </span>
                                            <span class="flex flex-1 flex-col leading-tight">
                                                <span @class(['text-[15px] text-tinta', 'font-semibold' => $actual])>{{ $paso }}</span>
                                                <span class="text-[13px] text-terciario">{{ $detalle }}</span>
                                            </span>
                                            <x-heroicon-o-arrow-right aria-hidden="true" @class(['size-4', 'text-oro-700' => $actual, 'text-piedra-300' => ! $actual]) />
                                        </a>
                                    </li>
                                @endforeach
                            </ol>

                            <x-filament::button tag="a" :href="route($rutaSiguiente, $grupo->grupoId)" class="mt-auto w-full">
                                {{ $siguiente }}<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                            </x-filament::button>
                        </article>
                    </x-filament::section>
                </li>
            @endforeach
        </ul>
    @endif
</x-filament-panels::page>
