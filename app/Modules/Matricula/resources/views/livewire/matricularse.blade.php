@php
    $conPago = $opcionesDePago !== [];
    // The shift of a group is the one of its first session: before noon, before six in the evening, or night; a group without sessions has none yet.
    $turno = function ($grupo): string {
        $hora = collect($grupo->sesiones)->min(fn ($sesion): string => $sesion->horaInicio);

        return match (true) { $hora === null => 'sin-horario', $hora < '12:00' => 'manana', $hora < '18:00' => 'tarde', default => 'noche' };
    };
    $turnos = ['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'];
    $etiquetasDeTurno = [...$turnos, 'sin-horario' => 'Horario por confirmar'];
@endphp
<div class="flex flex-col gap-2 pb-28 sm:gap-3.5" x-data="{ turno: 'todos', eleccion: '' }">
    <x-portal.encabezado titulo="Matrícula">Elige un grupo del ciclo que te corresponde y el pago de matrícula validado que usarás.</x-portal.encabezado>

    <ol class="grid gap-2 rounded-tarjeta bg-tarjeta p-4 sm:grid-cols-3 sm:p-5" aria-label="Pasos de tu matrícula">
        @foreach ([['Pago validado', $conPago ? 'hecho' : 'actual'], ['Elige tu grupo', $conPago ? 'actual' : 'pendiente'], ['Confirma', 'pendiente']] as [$paso, $estado])
            <li class="flex items-center gap-3">
                <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-positivo text-white' => $estado === 'hecho', 'bg-tinta text-white' => $estado === 'actual', 'bg-lienzo text-apagado' => $estado === 'pendiente'])>
                    @if ($estado === 'hecho')<x-heroicon-o-check aria-hidden="true" class="size-4 stroke-[2.5]" />@else{{ $loop->iteration }}@endif
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-[12.5px] text-apagado">Paso {{ $loop->iteration }}</span>
                    <span @class(['font-semibold', 'text-apagado' => $estado === 'pendiente'])>{{ $paso }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    @error('matricula')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    <details wire:ignore.self class="group rounded-tarjeta bg-tarjeta p-5 sm:px-7" @if (collect($requisitos)->contains(fn ($requisito): bool => ! $requisito->cumplido)) open @endif>
        <summary class="flex cursor-pointer items-center justify-between gap-3 font-semibold">
            Requisitos para matricularte
            <x-heroicon-o-chevron-down aria-hidden="true" class="size-4 text-apagado transition-transform group-open:rotate-180" />
        </summary>
        <p class="mt-1 text-[14px] text-secundario">El sistema comprueba lo mismo al confirmar tu matrícula.</p>
        <ul class="mt-3 flex flex-col gap-1">
            @foreach ($requisitos as $requisito)
                <x-portal.fila wire:key="requisito-{{ $loop->index }}" :titulo="$requisito->titulo">
                    <x-slot:inicio>
                        @if ($requisito->cumplido)
                            <x-heroicon-o-check-circle aria-hidden="true" class="size-6 text-verde-texto" />
                        @else
                            <x-heroicon-o-exclamation-circle aria-hidden="true" class="size-6 text-ambar-texto" />
                        @endif
                    </x-slot:inicio>
                    <x-slot:fin>
                        <x-portal.insignia :color="$requisito->cumplido ? 'success' : 'warning'">{{ $requisito->cumplido ? 'Cumplido' : 'Pendiente' }}</x-portal.insignia>
                    </x-slot:fin>
                    @foreach ($requisito->detalle as $linea)
                        <span>{{ $linea }}</span>
                    @endforeach
                    @if ($requisito->enlace !== null && ($requisito->textoDelEnlace !== null))
                        <a href="{{ $requisito->enlace }}" class="font-semibold underline underline-offset-2">{{ $requisito->textoDelEnlace }}</a>
                    @endif
                </x-portal.fila>
            @endforeach
        </ul>
    </details>

    <form wire:submit="matricularme" class="flex flex-col gap-2 sm:gap-3.5" novalidate>
        <section class="flex flex-col gap-3 rounded-tarjeta bg-tarjeta p-5 sm:flex-row sm:items-end sm:px-7">
            <x-heroicon-o-banknotes aria-hidden="true" class="hidden size-6 text-oro-700 sm:mb-3 sm:block" />
            @if ($conPago)
                <div class="flex-1">
                    <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos de matrícula validados que aún no usaste." />
                </div>
            @else
                <x-portal.alerta tipo="aviso" class="flex-1">
                    Aún no tienes un pago de matrícula validado.
                    <a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Registra tu pago</a> y espera a que el CILN lo valide.
                </x-portal.alerta>
            @endif
        </section>

        <section class="flex flex-col gap-4 rounded-tarjeta bg-tarjeta p-5 sm:px-7 sm:py-[26px]" wire:poll.15s.visible aria-labelledby="titulo-grupos">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="titulo-grupos" class="sr-only">Grupos disponibles</h2>
                <div class="flex flex-wrap gap-1 rounded-pildora bg-lienzo p-1" role="group" aria-label="Filtrar por turno">
                    @foreach (['todos' => 'Todos', ...$turnos] as $valor => $etiqueta)
                        <button type="button" x-on:click="turno = '{{ $valor }}'" x-bind:aria-pressed="turno === '{{ $valor }}'"
                            x-bind:class="turno === '{{ $valor }}' ? 'bg-tarjeta shadow-[0_1px_2px_rgb(29_27_22/0.1)] font-semibold' : 'text-secundario'"
                            class="rounded-pildora px-4 py-1.5 text-[14.5px] focus-visible:shadow-foco focus-visible:outline-none">{{ $etiqueta }}</button>
                    @endforeach
                </div>
                <span class="flex items-center gap-2 text-[13.5px] text-secundario"><span aria-hidden="true" class="size-2 rounded-full bg-positivo"></span>Vacantes en vivo</span>
            </header>

            @forelse ($ofertaPorIdioma as $idioma => $grupos)
                <fieldset wire:key="idioma-{{ $idioma }}" class="flex flex-col gap-3" @error('grupo') aria-describedby="campo-grupo-error" @enderror>
                    <legend class="mb-1 flex items-center gap-2.5 font-semibold"><x-ciln.hoja-bandera :idioma="$idioma" size="28" />{{ $idioma }}</legend>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($grupos as $grupo)
                            @php($sinVacantes = $grupo->vacantesDisponibles < 1)
                            @php($deTurno = $turno($grupo))
                            <label wire:key="grupo-{{ $grupo->id }}" data-turno="{{ $deTurno }}" x-show="turno === 'todos' || turno === '{{ $deTurno }}'" @class([
                                'flex flex-col gap-2 rounded-[18px] p-[18px] shadow-[inset_0_0_0_1px_var(--color-borde-interno)] has-checked:bg-acento-claro has-checked:shadow-[inset_0_0_0_2px_var(--color-oro-400)] has-focus-visible:shadow-foco',
                                'cursor-pointer bg-tarjeta-interna hover:bg-fila-hover' => ! $sinVacantes,
                                'cursor-not-allowed bg-tarjeta-interna opacity-60' => $sinVacantes,
                            ])>
                                <span class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-[13.5px] text-terciario">
                                        <input type="radio" name="grupo" value="{{ $grupo->id }}" wire:model="grupo" @disabled($sinVacantes) x-on:change="eleccion = @js($grupo->codigo)"
                                            aria-describedby="grupo-{{ $grupo->id }}-detalle grupo-{{ $grupo->id }}-vacantes" class="casilla size-4 focus:outline-none">
                                        {{ $grupo->codigo }} · {{ $etiquetasDeTurno[$deTurno] }}
                                    </span>
                                    <span id="grupo-{{ $grupo->id }}-vacantes" aria-live="polite" aria-atomic="true">
                                        <x-portal.insignia :color="$sinVacantes ? 'danger' : ($grupo->vacantesDisponibles <= 3 ? 'warning' : 'success')">
                                            {{ match (true) { $sinVacantes => 'Sin vacantes', $grupo->vacantesDisponibles === 1 => '1 vacante', default => "{$grupo->vacantesDisponibles} vacantes" } }}
                                        </x-portal.insignia>
                                    </span>
                                </span>
                                <span id="grupo-{{ $grupo->id }}-detalle" class="flex flex-col gap-0.5">
                                    @forelse ($grupo->horarioCompacto() as $horario)
                                        <span class="text-[17px] font-semibold">{{ $horario }}</span>
                                    @empty
                                        <span class="text-[17px] font-semibold">Horario por confirmar</span>
                                    @endforelse
                                    <span class="text-[13.5px] text-terciario">Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }} · Periodo {{ $grupo->periodo }}</span>
                                    <span class="text-[13.5px] text-terciario">{{ $grupo->aula !== null ? "Aula {$grupo->aula}" : ($grupo->enlaceVirtual !== null ? 'Clases virtuales' : 'Aula por asignar') }}</span>
                                </span>
                            </label>
                        @endforeach
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

        <div class="sticky bottom-3 z-10 flex flex-col gap-3 rounded-tarjeta bg-tinta p-4 text-white shadow-[0_12px_30px_rgb(29_27_22/0.3)] sm:flex-row sm:items-center sm:px-6">
            <span class="flex flex-1 flex-col">
                <span class="text-[13px] text-white/70">Tu elección</span>
                <span class="font-semibold" x-text="eleccion ? 'Grupo ' + eleccion : 'Elige un grupo de la lista'">Elige un grupo de la lista</span>
            </span>
            @if ($conPago && $ofertaPorIdioma->isNotEmpty())
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-pildora bg-acento px-6 py-3 font-semibold text-oro-950 hover:bg-oro-300 focus-visible:shadow-foco focus-visible:outline-none disabled:opacity-60">
                    Matricularme <x-heroicon-o-arrow-right aria-hidden="true" class="size-4" />
                </button>
            @endif
        </div>
    </form>
</div>
