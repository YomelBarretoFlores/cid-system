@use('App\Modules\Tramites\Enums\EstadoTramite')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Trámites">
        Inicia un trámite y sigue su avance. Recibirás un correo en cada cambio de estado.
    </x-portal.encabezado>

    <x-portal.tarjeta titulo="Mis trámites">
        @if ($tramites->isEmpty())
            <p class="text-terciario">Aún no tienes trámites. Elige uno en «Nuevo trámite» para iniciarlo.</p>
        @else
            <div class="overflow-x-auto">
                <table class="tabla-portal min-w-[760px]">
                    <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Trámite</th>
                            <th scope="col">Iniciado el</th>
                            <th scope="col">Etapa actual</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Documento</th>
                        </tr>
                    </thead>
                    @foreach ($tramites as $tramite)
                        <tbody wire:key="tramite-{{ $tramite->id }}">
                            <tr class="[&>td]:border-b-0!">
                                <td class="font-medium whitespace-nowrap tabular-nums">{{ $tramite->codigo }}</td>
                                <td>
                                    {{ $tramite->tipo_nombre }}
                                    @if ($tramite->datos['matricula'] ?? $tramite->datos['idioma'] ?? null)
                                        <span class="block text-[13px] text-terciario">{{ $tramite->datos['matricula'] ?? $tramite->datos['idioma'] }}</span>
                                    @endif
                                </td>
                                <td class="tabular-nums"><time datetime="{{ $tramite->created_at->toIso8601String() }}">{{ $tramite->created_at->timezone('America/Lima')->format('d/m/Y') }}</time></td>
                                <td>{{ $tramite->etapas[$tramite->etapa_actual]['nombre'] ?? '—' }}</td>
                                <td><x-portal.insignia :color="$tramite->estado->color()">{{ $tramite->estado->etiqueta() }}</x-portal.insignia></td>
                                <td>
                                    @if ($tramite->documento_id !== null)
                                        <a href="{{ route('estudiante.documentos.pdf', $tramite->documento_id) }}" class="enlace">Descargar</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="pt-0 text-[13.5px] text-secundario">
                                    @if ($tramite->estado === EstadoTramite::Observado)
                                        <p class="mb-1.5">Observación: {{ $tramite->observacion }}</p>
                                    @elseif ($tramite->estado === EstadoTramite::Cerrado)
                                        <p class="mb-1.5">El CILN cerró este trámite sin atenderlo: {{ $tramite->motivo_cierre }}</p>
                                    @elseif ($tramite->estado === EstadoTramite::PendientePago)
                                        <p class="mb-1.5">
                                            Falta validar el pago de la tasa ({{ $tramite->tipo_concepto?->etiqueta() }}).
                                            <a href="{{ route('estudiante.pagos') }}" class="enlace">Registra tu recibo en Mis pagos</a>; el trámite continúa cuando el CILN lo valide.
                                        </p>
                                    @endif
                                    <details>
                                        <summary class="cursor-pointer text-enlace hover:text-enlace-hover">Ver etapas</summary>
                                        <x-portal.etapas class="max-w-[640px] pt-2.5" :etapas="$tramite->etapas" :actual="$tramite->etapa_actual" :observado="$tramite->estado === EstadoTramite::Observado" :finalizado="$tramite->estado === EstadoTramite::Finalizado" />
                                    </details>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </x-portal.tarjeta>

    <x-portal.tarjeta titulo="Nuevo trámite" :descripcion="$tipo ? null : 'Elige el trámite que necesitas.'">
        @if (! $tipo)
            @if ($tipos->isEmpty())
                <x-portal.alerta>Por ahora no hay trámites disponibles.</x-portal.alerta>
            @else
                <div class="overflow-x-auto">
                    <table class="tabla-portal min-w-[520px]">
                        <thead>
                            <tr>
                                <th scope="col">Trámite</th>
                                <th scope="col">Tasa</th>
                                <th scope="col"><span class="sr-only">Acción</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tipos as $disponible)
                                <tr wire:key="tipo-{{ $disponible->id }}">
                                    <td>{{ $disponible->nombre }}</td>
                                    <td class="text-secundario">{{ $disponible->cobraTasa() ? $disponible->tipo_concepto?->etiqueta() : 'Sin tasa' }}</td>
                                    <td class="text-right">
                                        <x-portal.boton type="button" variante="secundario" tamano="compacto" wire:click="elegir({{ $disponible->id }})" aria-label="Elegir {{ $disponible->nombre }}">Elegir</x-portal.boton>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="flex max-w-[640px] flex-col gap-3.5">
                <div class="flex flex-col gap-1">
                    <h3 class="text-[17px] font-semibold">{{ $tipo->nombre }}</h3>
                    @if ($tipo->requisitos)
                        <p class="text-secundario">Requisitos: {{ $tipo->requisitos }}</p>
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
                            <x-portal.seleccion nombre="idioma" etiqueta="Idioma" :opciones="$opciones['idioma']" vacio="Elige el idioma" />
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
                        <x-portal.boton accion="solicitar">Iniciar trámite</x-portal.boton>
                        <x-portal.boton type="button" variante="secundario" wire:click="cancelar">Cancelar</x-portal.boton>
                    </div>
                </form>
            </div>
        @endif
    </x-portal.tarjeta>
</div>
