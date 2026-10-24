<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Matrícula</h1>
        <p class="text-sm text-slate-600">Elige un grupo del ciclo que te corresponde y el pago de matrícula validado que usarás.</p>
    </header>

    @error('matricula')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    <form wire:submit="matricularme" class="flex flex-col gap-8" novalidate>
        <section wire:poll.15s.visible aria-labelledby="titulo-oferta" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 id="titulo-oferta" class="text-lg font-semibold text-ciln-900">Grupos disponibles</h2>
                <p class="text-xs text-slate-600">Las vacantes se actualizan automáticamente cada 15 segundos.</p>
            </div>

            @forelse ($ofertaPorIdioma as $idioma => $grupos)
                <fieldset wire:key="idioma-{{ $idioma }}" class="flex flex-col gap-3" @error('grupo') aria-describedby="campo-grupo-error" @enderror>
                    <legend class="mb-3 text-base font-semibold text-slate-800">{{ $idioma }}</legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($grupos as $grupo)
                            @php($sinVacantes = $grupo->vacantesDisponibles < 1)
                            <label wire:key="grupo-{{ $grupo->id }}" @class([
                                'flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 has-checked:ring-2 has-checked:ring-ciln-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ciln-600',
                                'cursor-pointer hover:ring-ciln-300' => ! $sinVacantes,
                                'cursor-not-allowed opacity-70' => $sinVacantes,
                            ])>
                                <span class="flex items-start justify-between gap-3">
                                    <span class="flex items-center gap-2">
                                        <input type="radio" name="grupo" value="{{ $grupo->id }}" wire:model="grupo" @disabled($sinVacantes)
                                            aria-describedby="grupo-{{ $grupo->id }}-detalle grupo-{{ $grupo->id }}-vacantes"
                                            class="size-4 border-slate-300 text-ciln-700 focus:outline-none">
                                        <span class="font-semibold text-slate-900">Grupo {{ $grupo->codigo }}</span>
                                    </span>
                                    <span id="grupo-{{ $grupo->id }}-vacantes" aria-live="polite" aria-atomic="true">
                                        <x-portal.insignia :color="$sinVacantes ? 'danger' : ($grupo->vacantesDisponibles <= 3 ? 'warning' : 'success')">
                                            {{ match (true) { $sinVacantes => 'Sin vacantes', $grupo->vacantesDisponibles === 1 => '1 vacante', default => "{$grupo->vacantesDisponibles} vacantes" } }}
                                        </x-portal.insignia>
                                    </span>
                                </span>

                                <span id="grupo-{{ $grupo->id }}-detalle" class="flex flex-col gap-1 text-sm text-slate-700">
                                    <span>Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</span>
                                    <span>Periodo {{ $grupo->periodo }}</span>
                                    @forelse ($grupo->horarios as $horario)
                                        <span>{{ $horario }}</span>
                                    @empty
                                        <span>Horario por confirmar</span>
                                    @endforelse
                                    <span>{{ $grupo->aula !== null ? "Aula {$grupo->aula}" : ($grupo->enlaceVirtual !== null ? 'Clases virtuales' : 'Aula por asignar') }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @empty
                <x-portal.alerta>Por ahora no hay grupos abiertos para tu ciclo. Vuelve a revisar durante el periodo de matrícula.</x-portal.alerta>
            @endforelse

            @error('grupo')
                <p id="campo-grupo-error" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </section>

        <section aria-labelledby="titulo-pago" class="flex flex-col gap-3">
            <h2 id="titulo-pago" class="text-lg font-semibold text-ciln-900">Pago de matrícula</h2>

            @if ($opcionesDePago === [])
                <x-portal.alerta tipo="aviso">
                    Aún no tienes un pago de matrícula validado.
                    <a href="{{ route('estudiante.pagos') }}" class="font-medium underline underline-offset-2 hover:text-amber-950">Registra tu pago</a> y espera a que el CILN lo valide.
                </x-portal.alerta>
            @else
                <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos de matrícula validados que aún no usaste." />
            @endif
        </section>

        @if ($opcionesDePago !== [] && $ofertaPorIdioma->isNotEmpty())
            <x-portal.boton accion="matricularme" class="self-start">Matricularme</x-portal.boton>
        @endif
    </form>
</div>
