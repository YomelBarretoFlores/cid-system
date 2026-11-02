@use('App\Modules\Matricula\Enums\EstadoSolicitudClasificacion')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Examen de clasificación">
        Si ya estudiaste el idioma, solicita el examen para empezar en un nivel superior al básico.
    </x-portal.encabezado>

    <div class="columnas">
        <x-portal.tarjeta titulo="Mis solicitudes">
            @if ($solicitudes->isEmpty())
                <x-portal.vacio titulo="Aún no tienes solicitudes de examen de clasificación." icono="heroicon-o-clipboard-document-check" />
            @else
                <ul role="list" class="-mx-3.5 flex flex-col gap-1">
                    @foreach ($solicitudes as $solicitud)
                        @php($idiomaDeSolicitud = $catalogo->get($solicitud->idioma_id))
                        <x-portal.fila wire:key="solicitud-{{ $solicitud->id }}" :titulo="$idiomaDeSolicitud?->nombre">
                            <x-slot:inicio><x-ciln.hoja-bandera :idioma="$idiomaDeSolicitud?->nombre" size="30" /></x-slot:inicio>
                            <x-slot:fin>
                                <x-portal.insignia :color="match ($solicitud->estado) {
                                    EstadoSolicitudClasificacion::PendienteEvaluacion => 'warning',
                                    EstadoSolicitudClasificacion::Evaluado => 'success',
                                    EstadoSolicitudClasificacion::Expirado => 'gray',
                                }">{{ $solicitud->estado->etiqueta() }}</x-portal.insignia>
                            </x-slot:fin>
                            <p class="text-terciario">
                                Solicitada el <time datetime="{{ $solicitud->created_at->toIso8601String() }}">{{ $solicitud->created_at->timezone('America/Lima')->format('d/m/Y') }}</time>
                                @if ($solicitud->estado === EstadoSolicitudClasificacion::PendienteEvaluacion)
                                    · vence el <time datetime="{{ $solicitud->vence_en->toDateString() }}">{{ $solicitud->vence_en->format('d/m/Y') }}</time>
                                @endif
                            </p>
                            @if ($solicitud->estado === EstadoSolicitudClasificacion::Evaluado)
                                <dl class="grid gap-2 pt-1 sm:grid-cols-2">
                                    <div class="tarjeta-interna flex flex-col gap-1 p-3.5">
                                        <dt class="text-[13px] text-terciario">Puntaje</dt>
                                        <dd><span class="text-[28px] leading-none font-semibold tabular-nums">{{ $solicitud->puntaje }}</span> <span class="text-secundario">de 100</span></dd>
                                    </div>
                                    <div class="flex flex-col gap-1 rounded-interna border border-oro-300 bg-acento-claro p-3.5">
                                        <dt class="text-[13px] text-terciario">Empiezas en</dt>
                                        <dd class="text-[22px] leading-tight font-semibold">{{ $idiomaDeSolicitud?->ciclos[$solicitud->ciclo_asignado_id] ?? 'Por confirmar' }}</dd>
                                    </div>
                                </dl>
                                <p class="text-[13px] text-terciario">Examen del <time datetime="{{ $solicitud->fecha_examen?->toDateString() }}">{{ $solicitud->fecha_examen?->format('d/m/Y') }}</time></p>
                            @endif
                        </x-portal.fila>
                    @endforeach
                </ul>
            @endif
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Nueva solicitud" descripcion="Elige el idioma y el pago validado del examen de clasificación que usarás." class="lg:sticky lg:top-[30px]">
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
                <form wire:submit="solicitar" class="flex flex-col gap-3.5" novalidate>
                    <x-portal.seleccion nombre="idioma" etiqueta="Idioma" :opciones="$opcionesDeIdioma" vacio="Elige el idioma" banderas />
                    <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos del examen de clasificación validados que aún no usaste." />
                    <x-portal.boton accion="solicitar" class="mt-1 w-full">Solicitar examen</x-portal.boton>
                </form>
            @endif
        </x-portal.tarjeta>
    </div>
</div>
