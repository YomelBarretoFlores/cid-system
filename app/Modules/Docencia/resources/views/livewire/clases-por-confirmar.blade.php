@use('App\Modules\Docencia\Enums\ConfirmacionDeClase')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Clases por confirmar">
        Como delegado de tu grupo, confirma al final de cada clase que el docente la dictó. Si no se dictó o algo no estuvo bien, obsérvala con un comentario.
    </x-portal.encabezado>

    @if ($aviso)
        <x-portal.alerta tipo="exito">{{ $aviso }}</x-portal.alerta>
    @endif

    @if (! $clases->esDelegado)
        <x-portal.tarjeta>
            <x-portal.vacio titulo="No eres delegado de ningún grupo." icono="heroicon-o-user-group">El docente de tu grupo designa al delegado desde su panel.</x-portal.vacio>
        </x-portal.tarjeta>
    @else
        <x-portal.tarjeta titulo="Por confirmar">
            @error('confirmacion')
                <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
            @enderror

            @forelse ($clases->porConfirmar as $clase)
                <article wire:key="por-confirmar-{{ $clase->claseId }}" class="tarjeta-interna flex flex-col gap-3.5 p-4 sm:p-5">
                    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <h3 class="text-[17px] font-semibold">Clase del {{ $clase->fecha->format('d/m/Y') }} · {{ $clase->horario }}</h3>
                            <p class="text-[14px] text-terciario">{{ $clase->grupo }} · Docente: {{ $clase->docente }}</p>
                        </div>
                        <x-portal.insignia color="warning">Por confirmar</x-portal.insignia>
                    </header>

                    <dl class="flex flex-col gap-0.5">
                        <dt class="text-[13px] font-semibold text-terciario">Tema dictado</dt>
                        <dd class="text-[15px]">{{ $clase->tema ?? 'Sin tema registrado' }}</dd>
                    </dl>

                    <p class="text-[14.5px] text-secundario">{{ $clase->presentes }} de {{ $clase->estudiantes }} estudiantes presentes al cierre. Puedes responder hasta las {{ $clase->vence->format('H:i') }}.</p>

                    @if (! $clase->puedeConfirmar)
                        <x-portal.alerta tipo="aviso">No figuras presente en el llamado de cierre, así que no puedes confirmar esta clase.</x-portal.alerta>
                    @elseif ($observando === $clase->claseId)
                        <form wire:submit="enviarObservacion" class="flex flex-col gap-3">
                            <x-portal.texto-largo nombre="observacion" etiqueta="¿Qué pasó en la clase?" ayuda="Escribe entre 5 y 500 caracteres. La coordinación del CILN leerá tu comentario." />
                            <div class="flex flex-wrap gap-2">
                                <x-portal.boton accion="enviarObservacion" tamano="compacto">Enviar observación</x-portal.boton>
                                <x-portal.boton type="button" variante="secundario" tamano="compacto" wire:click="cancelar">Cancelar</x-portal.boton>
                            </div>
                        </form>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <x-portal.boton type="button" accion="confirmar" tamano="compacto" wire:click="confirmar({{ $clase->claseId }})">Sí, se dictó</x-portal.boton>
                            <x-portal.boton type="button" variante="secundario" tamano="compacto" wire:click="observar({{ $clase->claseId }})">No se dictó / observar</x-portal.boton>
                        </div>
                    @endif
                </article>
            @empty
                <x-portal.vacio titulo="No tienes clases por confirmar." icono="heroicon-o-check-circle">Cuando el docente tome el llamado de cierre de una clase, aparecerá aquí.</x-portal.vacio>
            @endforelse
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Revisadas recientemente">
            @if ($clases->revisadas === [])
                <x-portal.vacio titulo="Aún no revisas ninguna clase." icono="heroicon-o-clipboard-document-check" />
            @else
                <ul class="flex flex-col gap-1">
                    @foreach ($clases->revisadas as $clase)
                        <x-portal.fila wire:key="revisada-{{ $clase->claseId }}" :titulo="'Clase del '.$clase->fecha->format('d/m/Y').' · '.$clase->horario" :detalle="$clase->grupo.' · Docente: '.$clase->docente">
                            <x-slot:fin>
                                @if ($clase->confirmacion === ConfirmacionDeClase::Observada)
                                    <x-portal.insignia color="danger">Observada</x-portal.insignia>
                                @else
                                    <x-portal.insignia color="success">Confirmada</x-portal.insignia>
                                @endif
                            </x-slot:fin>
                            @if ($clase->observacion)
                                <p class="text-secundario">{{ $clase->observacion }}</p>
                            @endif
                        </x-portal.fila>
                    @endforeach
                </ul>
            @endif
        </x-portal.tarjeta>
    @endif
</div>
