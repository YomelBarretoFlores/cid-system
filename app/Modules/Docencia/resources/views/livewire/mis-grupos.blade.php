<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Mis grupos</h1>
        <p class="text-sm text-slate-600">Los grupos que tienes a cargo en este momento.</p>
    </header>

    @if ($grupos->isEmpty())
        <x-portal.alerta>Aún no tienes grupos asignados.</x-portal.alerta>
    @else
        <ul role="list" class="flex flex-col gap-4">
            @foreach ($grupos as $grupo)
                <li wire:key="grupo-{{ $grupo->grupoId }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                    <article class="flex flex-col gap-4" aria-labelledby="grupo-{{ $grupo->grupoId }}-titulo">
                        <header class="flex flex-col gap-0.5">
                            <h2 id="grupo-{{ $grupo->grupoId }}-titulo" class="text-base font-semibold text-ciln-900">Grupo {{ $grupo->codigo }}</h2>
                            <p class="text-sm text-slate-600">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }}</p>
                        </header>

                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Periodo</dt>
                                <dd class="text-slate-700">{{ $grupo->periodo }} ({{ $grupo->periodoInicio->format('d/m/Y') }} al {{ $grupo->periodoFin->format('d/m/Y') }})</dd>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Horario</dt>
                                @forelse ($grupo->sesiones as $sesion)
                                    <dd class="text-slate-700">{{ $sesion->descripcion() }}</dd>
                                @empty
                                    <dd class="text-slate-700">Por confirmar</dd>
                                @endforelse
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Estado</dt>
                                <dd><x-portal.insignia :color="$grupo->estado->color()">{{ $grupo->estado->etiqueta() }}</x-portal.insignia></dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4 text-sm">
                            <a href="{{ route('docente.asistencia', $grupo->grupoId) }}" class="rounded-md px-3 py-2 font-medium text-ciln-700 ring-1 ring-ciln-100 hover:bg-ciln-50 focus-visible:outline-2 focus-visible:outline-ciln-600">
                                Asistencia<span class="sr-only"> del grupo {{ $grupo->codigo }}</span>
                            </a>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
