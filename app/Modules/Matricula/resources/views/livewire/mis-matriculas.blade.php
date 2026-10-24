@use('App\Modules\Matricula\Enums\EstadoMatricula')
<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Mis matrículas</h1>
        <p class="text-sm text-slate-600">Tus matrículas, de la más reciente a la más antigua.</p>
    </header>

    @if ($matriculas->isEmpty())
        <x-portal.alerta>
            Aún no tienes matrículas.
            <a href="{{ route('estudiante.matricula') }}" class="font-medium underline underline-offset-2 hover:text-ciln-700">Matricúlate aquí</a>.
        </x-portal.alerta>
    @else
        <ul role="list" class="flex flex-col gap-4">
            @foreach ($matriculas as $matricula)
                @php($grupo = $grupos[$matricula->grupo_id])
                <li wire:key="matricula-{{ $matricula->id }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                    <article class="flex flex-col gap-4" aria-labelledby="matricula-{{ $matricula->id }}-titulo">
                        <header class="flex flex-col gap-0.5">
                            <h2 id="matricula-{{ $matricula->id }}-titulo" class="text-base font-semibold text-ciln-900">Grupo {{ $grupo->codigo }}</h2>
                            <p class="text-sm text-slate-600">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}</p>
                        </header>

                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Horario</dt>
                                @forelse ($grupo->horarios as $horario)
                                    <dd class="text-slate-700">{{ $horario }}</dd>
                                @empty
                                    <dd class="text-slate-700">Por confirmar</dd>
                                @endforelse
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">{{ $grupo->aula !== null || $grupo->enlaceVirtual === null ? 'Aula' : 'Clase virtual' }}</dt>
                                <dd class="text-slate-700">
                                    @if ($grupo->aula !== null)
                                        {{ $grupo->aula }}
                                    @elseif ($grupo->enlaceVirtual !== null)
                                        <a href="{{ $grupo->enlaceVirtual }}" target="_blank" rel="noopener noreferrer" class="font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900 focus-visible:outline-2 focus-visible:outline-ciln-600">Entrar a la clase virtual</a>
                                    @else
                                        Por asignar
                                    @endif
                                </dd>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Periodo</dt>
                                <dd class="text-slate-700">{{ $grupo->periodo }}</dd>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Estado</dt>
                                <dd><x-portal.insignia :color="$matricula->estado === EstadoMatricula::Activa ? 'success' : 'gray'">{{ $matricula->estado->etiqueta() }}</x-portal.insignia></dd>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Condición</dt>
                                <dd class="text-slate-700">{{ $matricula->condicion->etiqueta() }}</dd>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <dt class="font-medium text-slate-800">Matriculado el</dt>
                                <dd class="text-slate-700">
                                    <time datetime="{{ $matricula->matriculado_en->toIso8601String() }}">{{ $matricula->matriculado_en->timezone('America/Lima')->format('d/m/Y H:i') }}</time>
                                </dd>
                            </div>
                        </dl>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
