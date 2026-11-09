<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Registro de pagos">
        Ve todos los pagos que has registrado y registra tus recibos.
        <x-slot:acciones>
            <x-portal.boton type="button" tamano="compacto" wire:click="$toggle('registrando')"
                aria-controls="registro-de-recibo" aria-expanded="{{ $registrando ? 'true' : 'false' }}">
                Registrar recibo
            </x-portal.boton>
        </x-slot:acciones>
    </x-portal.encabezado>

    @if ($registrando)
        <x-portal.tarjeta id="registro-de-recibo" titulo="Registrar un recibo">
            <form wire:submit="registrar" class="flex flex-col gap-3.5" novalidate>
                @error('registro')
                    <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
                @enderror

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <x-portal.seleccion nombre="concepto_id" etiqueta="Concepto" :opciones="$conceptos" vacio="Elige el concepto que pagaste" vivo />
                    <x-portal.seleccion nombre="medio_pago_id" etiqueta="Medio de pago" :opciones="$mediosDePago" vacio="Elige cómo pagaste" />
                    <x-portal.fecha nombre="fecha_pago" etiqueta="Fecha de pago" :max="$hoy" />
                    <x-portal.campo nombre="numero_operacion" etiqueta="N.° de recibo u operación" autocomplete="off" maxlength="40" placeholder="Ej.: 00482193" required />
                    <x-portal.campo nombre="monto" etiqueta="Monto pagado (S/)" inputmode="decimal" autocomplete="off"
                        ayuda="Se completa con la tarifa vigente del concepto." required />
                    <x-portal.archivo nombre="recibo" etiqueta="Recibo escaneado" acepta="image/jpeg,image/png,application/pdf"
                        formatos="JPG, PNG o PDF · hasta 5 MB" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-portal.boton tamano="compacto" wire:loading.attr="disabled" wire:target="registrar,recibo">Registrar recibo</x-portal.boton>
                    <x-portal.boton type="button" variante="secundario" tamano="compacto" wire:click="cancelarRegistro">Cancelar</x-portal.boton>
                </div>
            </form>
        </x-portal.tarjeta>
    @endif

    <x-portal.tarjeta titulo="Recibos registrados"
        descripcion="Mientras un pago esté pendiente, lleva la boleta original al CILN para validarla.">
        @if ($pagos->isEmpty())
            <x-portal.vacio titulo="Todavía no registraste pagos." icono="heroicon-o-banknotes">
                Cuando registres un recibo, aparecerá aquí con su estado.
            </x-portal.vacio>
        @else
            <x-portal.alerta>
                Has registrado pagos por S/ {{ $resumen['total'] }}. Validados: S/ {{ $resumen['validados'] }} · Por validar: S/ {{ $resumen['porValidar'] }} · Recibos rechazados: {{ $resumen['rechazados'] }}
            </x-portal.alerta>

            <div class="overflow-x-auto">
                <table class="tabla-portal">
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Concepto</th>
                            <th scope="col">N.° de operación</th>
                            <th scope="col">Medio</th>
                            <th scope="col">Fecha</th>
                            <th scope="col" class="text-right!">Monto</th>
                            <th scope="col">Recibo</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pagos as $pago)
                            <tr wire:key="pago-{{ $pago->ulid }}">
                                <td class="text-terciario tabular-nums">{{ $loop->iteration }}</td>
                                <td class="min-w-48">
                                    {{ $pago->concepto->nombre }}
                                    @if ($pago->motivo_rechazo)
                                        <p class="mt-0.5 text-[13px] text-rojo-texto">Motivo del rechazo: {{ $pago->motivo_rechazo }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap tabular-nums">{{ $pago->numero_operacion }}</td>
                                <td class="whitespace-nowrap">{{ $pago->medioDePago->nombre }}</td>
                                <td class="whitespace-nowrap tabular-nums">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                <td class="text-right whitespace-nowrap tabular-nums">S/ {{ $pago->monto }}</td>
                                <td>
                                    <a href="{{ route('estudiante.pagos.recibo', $pago) }}" target="_blank" rel="noopener" class="enlace">
                                        Ver<span class="sr-only"> el recibo de la operación {{ $pago->numero_operacion }}</span>
                                    </a>
                                </td>
                                <td><x-portal.insignia :color="$pago->estado->color()">{{ $pago->estado->etiqueta() }}</x-portal.insignia></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-portal.tarjeta>
</div>
