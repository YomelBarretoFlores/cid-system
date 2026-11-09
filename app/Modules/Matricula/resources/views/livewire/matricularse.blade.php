@php
    $conPago = $opcionesDePago !== [];
    $puedeMatricularse = $cumple['periodo'] && $cumple['pago'] && $cumple['grupos'];
    $yaMatriculado = ! $puedeMatricularse && ! $cumple['unicidad'];
    $oferta = $ofertaPorIdioma->flatten(1);
    $resumen = [
        'Periodo' => $oferta->pluck('periodo')->unique()->implode(', ') ?: '—',
        'Ciclo que te corresponde' => $oferta->map(fn ($grupo): string => "{$grupo->idioma}: ciclo {$grupo->cicloNumero}")->unique()->implode(', ') ?: '—',
        'Pagos disponibles' => count($opcionesDePago),
        'Vacantes en tu ciclo' => $oferta->sum('vacantesDisponibles'),
    ];
@endphp
<div class="flex flex-col gap-3 sm:gap-4" x-data="{ eleccion: '' }">
    <x-portal.encabezado titulo="Matrícula">Revisa tus requisitos, elige tu grupo y confirma tu matrícula del periodo.</x-portal.encabezado>

    @error('matricula')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    <section class="grid gap-6 rounded-tarjeta border border-borde bg-tarjeta p-5 sm:px-7 lg:grid-cols-[minmax(0,1fr)_240px]" aria-labelledby="titulo-estado">
        <div class="flex min-w-0 flex-col gap-3">
            <div class="flex flex-col gap-1">
                <h2 id="titulo-estado" @class(['text-[21px] font-semibold', 'text-positivo' => $puedeMatricularse || $yaMatriculado, 'text-peligro' => ! $puedeMatricularse && ! $yaMatriculado])>
                    @if ($puedeMatricularse)
                        Puedes matricularte este periodo
                    @elseif ($yaMatriculado)
                        Ya estás matriculado este periodo
                    @else
                        No puedes matricularte este periodo
                    @endif
                </h2>
                <p class="text-[14.5px] text-secundario">
                    @if ($puedeMatricularse)
                        Elige el pago y el grupo, y confirma tu matrícula.
                    @elseif ($yaMatriculado)
                        Tu grupo, horario y documentos están en <a href="{{ route('estudiante.matriculas') }}" wire:navigate class="enlace">Mis matrículas</a>.
                    @else
                        Cumple los requisitos pendientes para matricularte.
                    @endif
                </p>
            </div>

            <ul class="flex flex-col border-t border-separador" aria-label="Requisitos para matricularte">
                @foreach ($requisitos as $requisito)
                    <li wire:key="requisito-{{ $loop->index }}" class="flex gap-3 border-b border-separador py-3">
                        @if ($requisito->cumplido)
                            <x-heroicon-s-check-circle aria-hidden="true" class="size-5 shrink-0 text-positivo" />
                        @else
                            <x-heroicon-s-x-circle aria-hidden="true" class="size-5 shrink-0 text-peligro" />
                        @endif
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="font-semibold">{{ $requisito->titulo }}<span class="sr-only">: {{ $requisito->cumplido ? 'Cumplido' : 'Pendiente' }}</span></span>
                            @foreach ($requisito->detalle as $linea)
                                <span class="text-[14px] text-secundario">{{ $linea }}</span>
                            @endforeach
                            @if ($requisito->enlace !== null && $requisito->textoDelEnlace !== null)
                                <a href="{{ $requisito->enlace }}" class="enlace text-[14px]">{{ $requisito->textoDelEnlace }}</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <dl class="flex flex-col gap-2 self-start">
            @foreach ($resumen as $etiqueta => $valor)
                <div class="flex flex-col rounded-control border border-borde px-3.5 py-2.5">
                    <dt class="text-[13px] text-apagado">{{ $etiqueta }}</dt>
                    <dd class="font-semibold">{{ $valor }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    @if ($cumple['periodo'] && ! $yaMatriculado)
        <form wire:submit="matricularme" class="flex flex-col gap-3 sm:gap-4" novalidate>
            <x-portal.tarjeta titulo="Pago que se usará" class="border border-borde">
                @if ($conPago)
                    <div class="max-w-xl">
                        <x-portal.seleccion nombre="pago" etiqueta="Pago de matrícula" :opciones="$opcionesDePago" vacio="Elige tu pago" />
                    </div>
                @else
                    <x-portal.alerta tipo="aviso">
                        Aún no tienes un pago de matrícula validado.
                        <a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Registra tu pago</a> y espera a que el CILN lo valide.
                    </x-portal.alerta>
                @endif
            </x-portal.tarjeta>

            <section class="flex flex-col gap-4 rounded-tarjeta border border-borde bg-tarjeta p-5 sm:px-7" wire:poll.15s.visible aria-labelledby="titulo-grupos">
                <h2 id="titulo-grupos" class="text-[21px] font-semibold">Elige tu grupo</h2>

                @forelse ($ofertaPorIdioma as $idioma => $grupos)
                    <fieldset wire:key="idioma-{{ $idioma }}" class="flex min-w-0 flex-col gap-2" @error('grupo') aria-describedby="campo-grupo-error" @enderror>
                        <legend class="mb-2 font-semibold">{{ $idioma }}</legend>
                        <div class="overflow-x-auto">
                            <table class="tabla-portal">
                                <thead>
                                    <tr>
                                        <th scope="col">Elegir</th>
                                        <th scope="col">Grupo</th>
                                        <th scope="col">Ciclo</th>
                                        <th scope="col">Horario</th>
                                        <th scope="col">Modalidad</th>
                                        <th scope="col">Aula</th>
                                        <th scope="col">Vacantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grupos as $grupo)
                                        @php($sinVacantes = $grupo->vacantesDisponibles < 1)
                                        <tr wire:key="grupo-{{ $grupo->id }}" @class(['has-checked:bg-acento-claro', 'text-apagado' => $sinVacantes])>
                                            <td>
                                                <input type="radio" name="grupo" value="{{ $grupo->id }}" wire:model="grupo" aria-label="Elegir el grupo {{ $grupo->codigo }}" @disabled($sinVacantes)
                                                    x-on:change="eleccion = @js("{$grupo->codigo} · {$idioma}, ciclo {$grupo->cicloNumero}")" class="casilla size-4 focus:outline-none">
                                            </td>
                                            <td class="font-semibold whitespace-nowrap">{{ $grupo->codigo }}</td>
                                            <td class="whitespace-nowrap">Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel }}</td>
                                            <td class="whitespace-nowrap">
                                                @forelse ($grupo->horarioCompacto() as $horario)
                                                    <span class="block">{{ $horario }}</span>
                                                @empty
                                                    Por confirmar
                                                @endforelse
                                            </td>
                                            <td>{{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</td>
                                            <td>{{ $grupo->aula ?? ($grupo->enlaceVirtual !== null ? 'Virtual' : 'Por asignar') }}</td>
                                            <td id="grupo-{{ $grupo->id }}-vacantes" aria-live="polite" class="whitespace-nowrap tabular-nums">{{ $sinVacantes ? 'Sin vacantes' : $grupo->vacantesDisponibles }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                @empty
                    <x-portal.vacio titulo="Por ahora no hay grupos abiertos para tu ciclo." icono="heroicon-o-calendar-days">
                        Vuelve a revisar durante el periodo de matrícula.
                    </x-portal.vacio>
                @endforelse

                @error('grupo')
                    <p id="campo-grupo-error" class="text-[13px] font-medium text-peligro">{{ $message }}</p>
                @enderror
            </section>

            @if ($conPago && $ofertaPorIdioma->isNotEmpty())
                <div class="flex flex-col gap-3 rounded-tarjeta border border-borde bg-tarjeta p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                    <p class="text-secundario" aria-live="polite">
                        Grupo elegido: <span class="font-semibold text-tinta" x-text="eleccion || 'ninguno'">ninguno</span>
                    </p>
                    <x-portal.boton accion="matricularme">Matricularme</x-portal.boton>
                </div>
            @endif
        </form>
    @endif
</div>
