@use('App\Modules\Matricula\Enums\EstadoMatricula')
<div class="flex flex-col gap-3 sm:gap-4">
    <x-portal.encabezado titulo="Mis matrículas">Tus matrículas por periodo, de la más reciente a la más antigua.</x-portal.encabezado>

    @if ($matriculas->isEmpty())
        <x-portal.tarjeta class="border border-borde">
            <x-portal.vacio titulo="Aún no tienes matrículas." icono="heroicon-o-academic-cap">
                <a href="{{ route('estudiante.matricula') }}" class="enlace">Matricúlate aquí</a>.
            </x-portal.vacio>
        </x-portal.tarjeta>
    @else
        @foreach ($matriculas->groupBy(fn ($matricula) => $grupos[$matricula->grupo_id]->periodoId) as $delPeriodo)
            @php($periodo = $grupos[$delPeriodo->first()->grupo_id]->periodo)
            <section wire:key="periodo-{{ $grupos[$delPeriodo->first()->grupo_id]->periodoId }}" aria-labelledby="periodo-{{ $loop->index }}-titulo"
                class="flex min-w-0 flex-col gap-4 rounded-tarjeta border border-borde bg-tarjeta p-5 sm:px-7">
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="periodo-{{ $loop->index }}-titulo" class="text-lg font-semibold">Periodo {{ $periodo }}</h2>
                    <x-portal.boton variante="secundario" :href="route('estudiante.matriculas.ficha', $delPeriodo->first())" target="_blank" rel="noopener" tamano="compacto">
                        <x-heroicon-o-document-arrow-down aria-hidden="true" class="size-4" />
                        Ficha de matrícula (PDF)<span class="sr-only"> del periodo {{ $periodo }}</span>
                    </x-portal.boton>
                </header>

                <div class="overflow-x-auto">
                    <table class="tabla-portal">
                        <thead>
                            <tr>
                                <th scope="col">Idioma y ciclo</th>
                                <th scope="col">Grupo</th>
                                <th scope="col">Horario</th>
                                <th scope="col">Aula</th>
                                <th scope="col">Docente</th>
                                <th scope="col">Condición</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Matriculado el</th>
                                <th scope="col">Documentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($delPeriodo as $matricula)
                                @php($grupo = $grupos[$matricula->grupo_id])
                                <tr wire:key="matricula-{{ $matricula->id }}">
                                    <td>
                                        <span class="block font-semibold">{{ $grupo->idioma }}</span>
                                        <span class="block whitespace-nowrap text-[13px] text-terciario">{{ $grupo->cicloNumeroHasta === null ? "Ciclo {$grupo->cicloNumero}" : "Ciclos {$grupo->cicloNumero} y {$grupo->cicloNumeroHasta}" }} · {{ $grupo->nivel }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">{{ $grupo->codigo }}</td>
                                    <td class="whitespace-nowrap">
                                        @forelse ($grupo->horarioCompacto() as $horario)
                                            <span class="block">{{ $horario }}</span>
                                        @empty
                                            Por confirmar
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($grupo->aula !== null)
                                            {{ $grupo->aula }}
                                        @elseif ($grupo->enlaceVirtual !== null)
                                            <a href="{{ $grupo->enlaceVirtual }}" target="_blank" rel="noopener noreferrer" class="enlace whitespace-nowrap">Clase virtual</a>
                                        @else
                                            Por asignar
                                        @endif
                                    </td>
                                    <td>{{ $docentes[$matricula->grupo_id] ?? 'Por asignar' }}</td>
                                    <td>{{ $matricula->condicion->etiqueta() }}</td>
                                    <td><x-portal.insignia :color="$matricula->estado === EstadoMatricula::Activa ? 'success' : 'gray'">{{ $matricula->estado->etiqueta() }}</x-portal.insignia></td>
                                    <td class="whitespace-nowrap tabular-nums">
                                        <time datetime="{{ $matricula->matriculado_en->toIso8601String() }}">{{ $matricula->matriculado_en->timezone('America/Lima')->format('d/m/Y H:i') }}</time>
                                    </td>
                                    <td>
                                        @if ($matricula->estado === EstadoMatricula::Activa)
                                            <a href="{{ route('estudiante.matriculas.constancia', $matricula) }}" target="_blank" rel="noopener" class="enlace whitespace-nowrap">Constancia (PDF)<span class="sr-only"> de inscripción del grupo {{ $grupo->codigo }}</span></a>
                                        @else
                                            <span class="text-apagado">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    @endif
</div>
