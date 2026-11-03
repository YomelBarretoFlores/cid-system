@use('App\Modules\Tramites\Enums\EstadoTramite')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Trámites">
        Sigue cada solicitud etapa por etapa. Recibirás un correo en cada cambio de estado.
    </x-portal.encabezado>

    <div class="columnas">
        <x-portal.tarjeta titulo="Mis trámites">
            @forelse ($tramites as $tramite)
                <article wire:key="tramite-{{ $tramite->id }}" class="flex flex-col gap-3.5 rounded-interna bg-tarjeta-interna p-4 shadow-[inset_0_0_0_1px_var(--color-borde-interno)] sm:p-5">
                    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <h3 class="text-[17px] font-semibold">{{ $tramite->tipo_nombre }}</h3>
                            <p class="text-[14px] text-terciario">
                                {{ $tramite->codigo }} · iniciado el <time datetime="{{ $tramite->created_at->toIso8601String() }}">{{ $tramite->created_at->timezone('America/Lima')->format('d/m/Y') }}</time>
                                @if ($tramite->datos['matricula'] ?? $tramite->datos['idioma'] ?? null)
                                    · {{ $tramite->datos['matricula'] ?? $tramite->datos['idioma'] }}
                                @endif
                            </p>
                        </div>
                        <x-portal.insignia :color="$tramite->estado->color()">{{ $tramite->estado->etiqueta() }}</x-portal.insignia>
                    </header>

                    <x-portal.etapas :etapas="$tramite->etapas" :actual="$tramite->etapa_actual" :observado="$tramite->estado === EstadoTramite::Observado" :finalizado="$tramite->estado === EstadoTramite::Finalizado" />

                    @if ($tramite->documento_id !== null)
                        <x-portal.boton variante="secundario" tamano="compacto" :href="route('estudiante.documentos.pdf', $tramite->documento_id)" class="self-start">
                            <x-heroicon-o-arrow-down-tray aria-hidden="true" class="size-4" /> Descargar el documento
                        </x-portal.boton>
                    @endif

                    @if ($tramite->estado === EstadoTramite::Observado)
                        <x-portal.alerta tipo="aviso">{{ $tramite->observacion }}</x-portal.alerta>
                    @elseif ($tramite->estado === EstadoTramite::Cerrado)
                        <x-portal.alerta tipo="aviso">El CILN cerró este trámite sin atenderlo: {{ $tramite->motivo_cierre }}</x-portal.alerta>
                    @elseif ($tramite->estado === EstadoTramite::PendientePago)
                        <p class="text-[14.5px] text-secundario">
                            Falta validar el pago de la tasa ({{ $tramite->tipo_concepto?->etiqueta() }}).
                            <a href="{{ route('estudiante.pagos') }}" class="enlace">Registra tu recibo en Mis pagos</a>; el trámite continúa cuando el CILN lo valide.
                        </p>
                    @endif
                </article>
            @empty
                <x-portal.vacio titulo="Aún no tienes trámites." icono="heroicon-o-document-text">Elige un trámite para iniciarlo.</x-portal.vacio>
            @endforelse
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Nuevo trámite" :descripcion="$tipo ? null : 'Elige el trámite que necesitas.'" class="lg:sticky lg:top-[30px]">
            @if (! $tipo)
                @if ($tipos->isEmpty())
                    <x-portal.alerta>Por ahora no hay trámites disponibles.</x-portal.alerta>
                @else
                    <ul role="list" class="flex flex-col gap-2">
                        @foreach ($tipos as $disponible)
                            <li>
                                <button type="button" wire:click="elegir({{ $disponible->id }})"
                                    class="flex w-full items-center gap-3 rounded-interna bg-lienzo px-4 py-3 text-left font-medium hover:bg-ambar-fondo focus-visible:shadow-foco focus-visible:outline-none">
                                    <span aria-hidden="true" class="flex size-7 shrink-0 items-center justify-center rounded-full bg-tarjeta text-enlace">+</span>
                                    <span class="flex min-w-0 flex-col">
                                        <span>{{ $disponible->nombre }}</span>
                                        <span class="text-[13px] font-normal text-terciario">{{ $disponible->cobraTasa() ? 'Con pago de tasa' : 'Sin pago de tasa' }}</span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                <div class="flex flex-col gap-1">
                    <h3 class="text-[17px] font-semibold">{{ $tipo->nombre }}</h3>
                    @if ($tipo->requisitos)
                        <p class="text-[14.5px] text-secundario">Requisitos: {{ $tipo->requisitos }}</p>
                    @endif
                </div>

                @error('solicitud')
                    <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
                @enderror

                @if ($tipo->cobraTasa())
                    <x-portal.alerta>Este trámite tiene una tasa ({{ $tipo->tipo_concepto?->etiqueta() }}). Si ya tienes un pago validado sin usar, se aplica solo; si no, regístralo en <a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Mis pagos</a>.</x-portal.alerta>
                @endif

                <form wire:submit="solicitar" class="flex flex-col gap-3.5" novalidate>
                    @isset($opciones['matricula'])
                        @if ($opciones['matricula'] === [])
                            <x-portal.alerta tipo="aviso">No tienes matrículas activas para este trámite.</x-portal.alerta>
                        @else
                            <x-portal.seleccion nombre="matricula" etiqueta="Matrícula" :opciones="$opciones['matricula']" vacio="Elige tu matrícula" />
                        @endif
                    @endisset
                    @isset($opciones['idioma'])
                        @if ($opciones['idioma'] === [])
                            <x-portal.alerta tipo="aviso">Aún no tienes actas finales cerradas.</x-portal.alerta>
                        @else
                            <x-portal.seleccion nombre="idioma" etiqueta="Idioma" :opciones="$opciones['idioma']" vacio="Elige el idioma" banderas />
                        @endif
                    @endisset
                    @isset($opciones['nivel'])
                        @if ($opciones['nivel'] === [])
                            <x-portal.alerta tipo="aviso">Aún no completas un nivel por certificar, o ya tienes sus certificados.</x-portal.alerta>
                        @else
                            <x-portal.seleccion nombre="nivel" etiqueta="Idioma y nivel" :opciones="$opciones['nivel']" vacio="Elige el nivel" />
                        @endif
                    @endisset
                    @if ($tipo->accion_final->pideMotivo())
                        <x-portal.texto-largo nombre="motivo" etiqueta="Motivo" />
                    @endif
                    <x-portal.texto-largo nombre="comentario" etiqueta="Comentario (opcional)" filas="2" />

                    <div class="flex flex-wrap gap-2">
                        <x-portal.boton accion="solicitar" class="flex-1">Iniciar trámite</x-portal.boton>
                        <x-portal.boton type="button" variante="secundario" wire:click="cancelar">Cancelar</x-portal.boton>
                    </div>
                </form>
            @endif
        </x-portal.tarjeta>
    </div>
</div>
