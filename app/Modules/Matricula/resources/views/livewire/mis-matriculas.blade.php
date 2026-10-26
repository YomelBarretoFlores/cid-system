@use('App\Modules\Matricula\Enums\EstadoMatricula')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Mis matrículas">Tus matrículas por periodo, de la más reciente a la más antigua. Cada periodo tiene su ficha de matrícula.</x-portal.encabezado>

    @if ($matriculas->isEmpty())
        <x-portal.tarjeta>
            <x-portal.vacio titulo="Aún no tienes matrículas." icono="heroicon-o-academic-cap">
                <a href="{{ route('estudiante.matricula') }}" class="enlace">Matricúlate aquí</a>.
            </x-portal.vacio>
        </x-portal.tarjeta>
    @else
        @foreach ($matriculas->groupBy(fn ($matricula) => $grupos[$matricula->grupo_id]->periodoId) as $delPeriodo)
            @php($periodo = $grupos[$delPeriodo->first()->grupo_id]->periodo)
            <section wire:key="periodo-{{ $grupos[$delPeriodo->first()->grupo_id]->periodoId }}" aria-label="Periodo {{ $periodo }}" class="flex flex-col gap-2 sm:gap-3.5">
                <div class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <h2 class="text-lg font-semibold">Periodo {{ $periodo }}</h2>
                    <x-portal.boton variante="secundario" :href="route('estudiante.matriculas.ficha', $delPeriodo->first())" target="_blank" rel="noopener" tamano="compacto">
                        <x-heroicon-o-document-arrow-down aria-hidden="true" class="size-4" />
                        Ficha de matrícula (PDF)<span class="sr-only"> del periodo {{ $periodo }}</span>
                    </x-portal.boton>
                </div>
                <ul role="list" class="flex flex-col gap-2 sm:gap-3.5">
                    @foreach ($delPeriodo as $matricula)
                        @php($grupo = $grupos[$matricula->grupo_id])
                        <li wire:key="matricula-{{ $matricula->id }}">
                            <article aria-labelledby="matricula-{{ $matricula->id }}-titulo"
                                class="grid items-center gap-5 rounded-tarjeta bg-tarjeta p-5 sm:px-7 sm:py-[26px] md:grid-cols-[auto_minmax(0,1fr)_auto]">
                                <x-ciln.hoja-bandera :idioma="$grupo->idioma" size="64" rot="-10" class="max-md:hidden" />

                                <div class="flex flex-col gap-3">
                                    <header class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                        <h3 id="matricula-{{ $matricula->id }}-titulo" class="text-[22px] font-semibold">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }}</h3>
                                        <span class="text-[13.5px] text-apagado">Grupo {{ $grupo->codigo }} · {{ $grupo->nivel }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</span>
                                    </header>

                                    <dl class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
                                        <x-portal.dato etiqueta="Horario">
                                            @forelse ($grupo->horarioCompacto() as $horario)
                                                <span class="block">{{ $horario }}</span>
                                            @empty
                                                Por confirmar
                                            @endforelse
                                        </x-portal.dato>
                                        <x-portal.dato :etiqueta="$grupo->aula !== null || $grupo->enlaceVirtual === null ? 'Aula' : 'Clase virtual'">
                                            @if ($grupo->aula !== null)
                                                {{ $grupo->aula }}
                                            @elseif ($grupo->enlaceVirtual !== null)
                                                <a href="{{ $grupo->enlaceVirtual }}" target="_blank" rel="noopener noreferrer" class="enlace">Entrar a la clase virtual</a>
                                            @else
                                                Por asignar
                                            @endif
                                        </x-portal.dato>
                                        <x-portal.dato etiqueta="Estado">
                                            <x-portal.insignia :color="$matricula->estado === EstadoMatricula::Activa ? 'success' : 'gray'">{{ $matricula->estado->etiqueta() }}</x-portal.insignia>
                                        </x-portal.dato>
                                        <x-portal.dato etiqueta="Docente">{{ $docentes[$matricula->grupo_id] ?? 'Por asignar' }}</x-portal.dato>
                                        <x-portal.dato etiqueta="Condición">{{ $matricula->condicion->etiqueta() }}</x-portal.dato>
                                            <x-portal.dato etiqueta="Matriculado el">
                                            <time datetime="{{ $matricula->matriculado_en->toIso8601String() }}">{{ $matricula->matriculado_en->timezone('America/Lima')->format('d/m/Y H:i') }}</time>
                                        </x-portal.dato>
                                    </dl>
                                </div>

                                <div class="flex flex-col gap-2">
                                    @if ($matricula->estado === EstadoMatricula::Activa)
                                        <x-portal.boton variante="secundario" :href="route('estudiante.matriculas.constancia', $matricula)" target="_blank" rel="noopener" tamano="compacto">
                                            <x-heroicon-o-document-check aria-hidden="true" class="size-4" />
                                            Constancia de inscripción (PDF)<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                                        </x-portal.boton>
                                    @endif
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</div>
