@use('App\Modules\Academico\Data\SesionDeClase')
<x-filament-panels::page>
    @if ($grupos === [])
        <x-filament::empty-state heading="Aún no tienes grupos asignados." description="Cuando la coordinación te asigne un grupo, aparecerá aquí." icon="heroicon-o-user-group" />
    @else
        <ul role="list" class="grid grid-cols-[minmax(0,1fr)] gap-4 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
            @foreach ($grupos as $grupo)
                <li wire:key="grupo-{{ $grupo->grupoId }}">
                    <x-filament::section class="h-full">
                        <article aria-labelledby="grupo-{{ $grupo->grupoId }}-titulo" class="flex h-full flex-col gap-4">
                            <header class="flex items-start justify-between gap-2.5">
                                <div class="flex items-center gap-3">
                                    <x-ciln.hoja-bandera :idioma="$grupo->idioma" size="44" rot="-8" />
                                    <div class="flex flex-col">
                                        <h2 id="grupo-{{ $grupo->grupoId }}-titulo" class="text-[17px] font-semibold text-tinta">Grupo {{ $grupo->codigo }}</h2>
                                        <span class="text-sm text-terciario">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }}</span>
                                    </div>
                                </div>
                                <span class="shrink-0"><x-filament::badge :color="$grupo->estado->color()">{{ $grupo->estado->etiqueta() }}</x-filament::badge></span>
                            </header>

                            <dl class="grid gap-2.5 text-sm">
                                <div>
                                    <dt class="text-apagado">Periodo</dt>
                                    <dd class="text-tinta">{{ $grupo->periodo }} ({{ $grupo->periodoInicio->format('d/m/Y') }} al {{ $grupo->periodoFin->format('d/m/Y') }})</dd>
                                </div>
                                <div>
                                    <dt class="text-apagado">Horario</dt>
                                    <dd class="text-tinta">
                                        @forelse (SesionDeClase::compactar($grupo->sesiones) as $horario)
                                            <span class="block">{{ $horario }}</span>
                                        @empty
                                            Por confirmar
                                        @endforelse
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-auto flex flex-wrap gap-2">
                                <x-filament::button tag="a" :href="route('filament.docente.pages.grupos.asistencia', $grupo->grupoId)" size="sm" class="flex-1">
                                    Tomar asistencia<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                                </x-filament::button>
                                <x-filament::button tag="a" :href="route('filament.docente.pages.grupos.notas', $grupo->grupoId)" size="sm" color="gray" outlined class="flex-1">
                                    Pre-acta<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                                </x-filament::button>
                                <x-filament::button tag="a" :href="route('filament.docente.pages.grupos.acta', $grupo->grupoId)" size="sm" color="gray" outlined class="flex-1">
                                    Acta final<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                                </x-filament::button>
                            </div>
                        </article>
                    </x-filament::section>
                </li>
            @endforeach
        </ul>
    @endif
</x-filament-panels::page>
