@use('App\Modules\Matricula\Enums\EstadoSolicitudClasificacion')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Examen de clasificación">
        Solicita el examen si ya estudiaste el idioma y quieres empezar en un ciclo superior al básico.
    </x-portal.encabezado>

    <x-portal.tarjeta titulo="Mis solicitudes">
        <p class="text-secundario">
            El examen ubica tu nivel para matricularte en el ciclo que te corresponde. Ríndelo en el CILN antes de la fecha de la columna «Rendir hasta»; después, la solicitud vence.
        </p>

        @if ($solicitudes->isEmpty())
            <p class="border-t border-separador pt-4 text-terciario">Aún no tienes solicitudes de examen de clasificación.</p>
        @else
            <div class="overflow-x-auto">
                <table class="tabla-portal min-w-[760px]">
                    <thead>
                        <tr>
                            <th scope="col">Idioma</th>
                            <th scope="col">Solicitado el</th>
                            <th scope="col">Rendir hasta</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Fecha del examen</th>
                            <th scope="col">Puntaje</th>
                            <th scope="col">Empiezas en</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($solicitudes as $solicitud)
                            @php($idiomaDeSolicitud = $catalogo->get($solicitud->idioma_id))
                            @php($evaluada = $solicitud->estado === EstadoSolicitudClasificacion::Evaluado)
                            <tr wire:key="solicitud-{{ $solicitud->id }}">
                                <td class="font-medium">{{ $idiomaDeSolicitud?->nombre }}</td>
                                <td class="tabular-nums"><time datetime="{{ $solicitud->created_at->toIso8601String() }}">{{ $solicitud->created_at->timezone('America/Lima')->format('d/m/Y') }}</time></td>
                                <td class="tabular-nums">
                                    @if ($solicitud->estado === EstadoSolicitudClasificacion::PendienteEvaluacion)
                                        <time datetime="{{ $solicitud->vence_en->toDateString() }}">{{ $solicitud->vence_en->format('d/m/Y') }}</time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-portal.insignia :color="$solicitud->estado->color()">{{ $solicitud->estado->etiqueta() }}</x-portal.insignia></td>
                                <td class="tabular-nums">
                                    @if ($evaluada && $solicitud->fecha_examen)
                                        <time datetime="{{ $solicitud->fecha_examen->toDateString() }}">{{ $solicitud->fecha_examen->format('d/m/Y') }}</time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="tabular-nums">{{ $evaluada && $solicitud->puntaje !== null ? "{$solicitud->puntaje} de 100" : '—' }}</td>
                                <td>{{ $evaluada ? ($idiomaDeSolicitud?->ciclos[$solicitud->ciclo_asignado_id] ?? '—') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="Nueva solicitud" descripcion="Elige el idioma y el pago validado del examen de clasificación.">
        @error('solicitud')
            <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
        @enderror

        @if ($opcionesDePago === [])
            <x-portal.alerta tipo="aviso">
                Aún no tienes un pago del examen de clasificación validado y sin usar.
                <a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Registra tu pago</a> y espera a que el CILN lo valide.
            </x-portal.alerta>
        @elseif ($opcionesDeIdioma === [])
            <x-portal.alerta>Por ahora no hay idiomas disponibles para el examen de clasificación.</x-portal.alerta>
        @else
            <form wire:submit="solicitar" class="flex max-w-[560px] flex-col gap-3.5" novalidate>
                <x-portal.seleccion nombre="idioma" etiqueta="Idioma" :opciones="$opcionesDeIdioma" vacio="Elige el idioma" />
                <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos validados que aún no usaste." />
                <x-portal.boton accion="solicitar" class="mt-1 self-start">Solicitar examen</x-portal.boton>
            </form>
        @endif
    </x-portal.tarjeta>
</div>
