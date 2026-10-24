@use('App\Modules\Matricula\Enums\EstadoSolicitudClasificacion')
<div class="flex flex-col gap-8">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Examen de clasificación</h1>
        <p class="text-sm text-slate-600">Si ya estudiaste el idioma, solicita el examen para empezar en un nivel superior al básico.</p>
    </header>

    <section aria-labelledby="titulo-solicitudes" class="flex flex-col gap-4">
        <h2 id="titulo-solicitudes" class="text-lg font-semibold text-ciln-900">Mis solicitudes</h2>

        @if ($solicitudes->isEmpty())
            <x-portal.alerta>Aún no tienes solicitudes de examen de clasificación.</x-portal.alerta>
        @else
            <ul role="list" class="flex flex-col gap-4">
                @foreach ($solicitudes as $solicitud)
                    @php($idiomaDeSolicitud = $catalogo->get($solicitud->idioma_id))
                    <li wire:key="solicitud-{{ $solicitud->id }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                        <article class="flex flex-col gap-4" aria-labelledby="solicitud-{{ $solicitud->id }}-titulo">
                            <header class="flex flex-wrap items-start justify-between gap-3">
                                <h3 id="solicitud-{{ $solicitud->id }}-titulo" class="text-base font-semibold text-ciln-900">{{ $idiomaDeSolicitud?->nombre }}</h3>
                                <x-portal.insignia :color="match ($solicitud->estado) {
                                    EstadoSolicitudClasificacion::PendienteEvaluacion => 'warning',
                                    EstadoSolicitudClasificacion::Evaluado => 'success',
                                    EstadoSolicitudClasificacion::Expirado => 'gray',
                                }">{{ $solicitud->estado->etiqueta() }}</x-portal.insignia>
                            </header>

                            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                <div class="flex flex-col gap-0.5">
                                    <dt class="font-medium text-slate-800">Solicitada el</dt>
                                    <dd class="text-slate-700">
                                        <time datetime="{{ $solicitud->created_at->toIso8601String() }}">{{ $solicitud->created_at->timezone('America/Lima')->format('d/m/Y') }}</time>
                                    </dd>
                                </div>
                                @if ($solicitud->estado === EstadoSolicitudClasificacion::PendienteEvaluacion)
                                    <div class="flex flex-col gap-0.5">
                                        <dt class="font-medium text-slate-800">Vence el</dt>
                                        <dd class="text-slate-700">
                                            <time datetime="{{ $solicitud->vence_en->toDateString() }}">{{ $solicitud->vence_en->format('d/m/Y') }}</time>
                                        </dd>
                                    </div>
                                @endif
                                @if ($solicitud->estado === EstadoSolicitudClasificacion::Evaluado)
                                    <div class="flex flex-col gap-0.5">
                                        <dt class="font-medium text-slate-800">Fecha del examen</dt>
                                        <dd class="text-slate-700">
                                            <time datetime="{{ $solicitud->fecha_examen?->toDateString() }}">{{ $solicitud->fecha_examen?->format('d/m/Y') }}</time>
                                        </dd>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <dt class="font-medium text-slate-800">Puntaje</dt>
                                        <dd class="text-slate-700">{{ $solicitud->puntaje }} de 100</dd>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <dt class="font-medium text-slate-800">Nivel asignado</dt>
                                        <dd class="text-slate-700">{{ $idiomaDeSolicitud?->ciclos[$solicitud->ciclo_asignado_id] ?? 'Por confirmar' }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="titulo-solicitar" class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <h2 id="titulo-solicitar" class="text-lg font-semibold text-ciln-900">Solicitar examen</h2>
            <p class="text-sm text-slate-600">Elige el idioma y el pago validado del examen de clasificación que usarás.</p>
        </div>

        @error('solicitud')
            <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
        @enderror

        @if ($opcionesDePago === [])
            <x-portal.alerta tipo="aviso">
                Aún no tienes un pago del examen de clasificación validado y sin usar.
                <a href="{{ route('estudiante.pagos') }}" class="font-medium underline underline-offset-2 hover:text-amber-950">Registra tu pago</a> y espera a que el CILN lo valide.
            </x-portal.alerta>
        @elseif ($opcionesDeIdioma === [])
            <x-portal.alerta>Por ahora no hay idiomas disponibles para el examen de clasificación.</x-portal.alerta>
        @else
            <form wire:submit="solicitar" class="flex flex-col gap-5 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6" novalidate>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-portal.seleccion nombre="idioma" etiqueta="Idioma" :opciones="$opcionesDeIdioma" vacio="Elige el idioma" />
                    <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos del examen de clasificación validados que aún no usaste." />
                </div>

                <x-portal.boton accion="solicitar" class="self-start">Solicitar examen</x-portal.boton>
            </form>
        @endif
    </section>
</div>
