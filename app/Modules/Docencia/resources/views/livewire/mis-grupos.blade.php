@use('App\Modules\Academico\Data\SesionDeClase')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Mis grupos" :antetitulo="now('America/Lima')->locale('es')->isoFormat('[Hoy es] dddd D [de] MMMM')" saludo>
        Los grupos que tienes a cargo en este momento. Si la coordinación te reasigna un grupo, deja de aparecer aquí.
    </x-portal.encabezado>

    @if ($grupos->isEmpty())
        <x-portal.tarjeta>
            <x-portal.vacio titulo="Aún no tienes grupos asignados." icono="heroicon-o-user-group">
                Cuando la coordinación te asigne un grupo, aparecerá aquí.
            </x-portal.vacio>
        </x-portal.tarjeta>
    @else
        <ul role="list" class="grid grid-cols-[minmax(0,1fr)] gap-2 sm:gap-3.5 md:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
            @foreach ($grupos as $grupo)
                <li wire:key="grupo-{{ $grupo->grupoId }}">
                    <article aria-labelledby="grupo-{{ $grupo->grupoId }}-titulo" class="flex h-full flex-col gap-4 rounded-tarjeta bg-tarjeta p-6">
                        <header class="flex items-start justify-between gap-2.5">
                            <div class="flex items-center gap-3">
                                <x-ciln.hoja-bandera :idioma="$grupo->idioma" size="44" rot="-8" />
                                <div class="flex flex-col">
                                    <h2 id="grupo-{{ $grupo->grupoId }}-titulo" class="text-[19px] font-semibold">Grupo {{ $grupo->codigo }}</h2>
                                    <span class="text-sm text-terciario">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }}</span>
                                </div>
                            </div>
                            <x-portal.insignia :color="$grupo->estado->color()">{{ $grupo->estado->etiqueta() }}</x-portal.insignia>
                        </header>

                        <dl class="grid grid-cols-2 gap-2.5 text-sm">
                            <x-portal.dato etiqueta="Periodo">{{ $grupo->periodo }} ({{ $grupo->periodoInicio->format('d/m/Y') }} al {{ $grupo->periodoFin->format('d/m/Y') }})</x-portal.dato>
                            <x-portal.dato etiqueta="Horario" class="col-span-2">
                                @forelse (SesionDeClase::compactar($grupo->sesiones) as $horario)
                                    <span class="block">{{ $horario }}</span>
                                @empty
                                    Por confirmar
                                @endforelse
                            </x-portal.dato>
                        </dl>

                        <div class="mt-auto flex gap-2">
                            <x-portal.boton :href="route('docente.asistencia', $grupo->grupoId)" tamano="compacto" class="flex-1">
                                Tomar asistencia<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                            </x-portal.boton>
                            <x-portal.boton variante="secundario" :href="route('docente.notas', $grupo->grupoId)" tamano="compacto" class="flex-1">
                                Pre-acta<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                            </x-portal.boton>
                            <x-portal.boton variante="secundario" :href="route('docente.acta', $grupo->grupoId)" tamano="compacto" class="flex-1">
                                Acta final<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                            </x-portal.boton>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
