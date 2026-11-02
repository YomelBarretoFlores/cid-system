@use('App\Modules\Pagos\Enums\EstadoPago')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Mis pagos">
        Registra tus pagos con la foto o el PDF de tu boleta y lleva la boleta original al CILN para validarla. Aquí verás si fueron validados.
    </x-portal.encabezado>

    <div class="columnas">
        <x-portal.tarjeta titulo="Pagos registrados">
            @if ($pagos->isEmpty())
                <x-portal.vacio titulo="Todavía no registraste pagos." icono="heroicon-o-banknotes">
                    Cuando registres un pago con su recibo, aparecerá aquí con su estado.
                </x-portal.vacio>
            @else
                <ul class="-mx-3.5 flex flex-col gap-1" role="list">
                    @foreach ($pagos as $pago)
                        <x-portal.fila wire:key="pago-{{ $pago->ulid }}" :titulo="$pago->concepto->nombre"
                            :detalle="$pago->numero_operacion.' · '.$pago->medioDePago->nombre.' · '.$pago->fecha_pago->format('d/m/Y')">
                            <x-slot:inicio><x-heroicon-o-banknotes aria-hidden="true" class="size-[22px] text-oro-600" /></x-slot:inicio>
                            <x-slot:fin>
                                <span class="font-semibold whitespace-nowrap">S/ {{ $pago->monto }}</span>
                                <x-portal.insignia :color="$pago->estado->color()">{{ $pago->estado->etiqueta() }}</x-portal.insignia>
                            </x-slot:fin>
                            @if ($pago->estado === EstadoPago::Pendiente)
                                <p class="text-secundario">Acércate al CILN con tu boleta original para validarla.</p>
                            @endif
                            @if ($pago->motivo_rechazo)
                                <p class="text-rojo-texto">Motivo del rechazo: {{ $pago->motivo_rechazo }}</p>
                            @endif
                            <a href="{{ route('estudiante.pagos.recibo', $pago) }}" target="_blank" rel="noopener" class="enlace self-start">
                                Ver recibo<span class="sr-only"> de la operación {{ $pago->numero_operacion }}</span>
                            </a>
                        </x-portal.fila>
                    @endforeach
                </ul>
            @endif
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Registrar un pago" class="lg:sticky lg:top-[30px]">
            <form wire:submit="registrar" class="flex flex-col gap-3.5" novalidate>
                @error('registro')
                    <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
                @enderror

                <x-portal.seleccion nombre="concepto_id" etiqueta="Concepto" :opciones="$conceptos" vacio="Elige el concepto que pagaste" vivo />

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-portal.seleccion nombre="medio_pago_id" etiqueta="Medio de pago" :opciones="$mediosDePago" vacio="Elige cómo pagaste" />
                    <x-portal.fecha nombre="fecha_pago" etiqueta="Fecha de pago" :max="$hoy" />
                </div>

                <x-portal.campo nombre="numero_operacion" etiqueta="N.° de recibo u operación" autocomplete="off" maxlength="40" placeholder="Ej.: 00482193" required />
                <x-portal.campo nombre="monto" etiqueta="Monto pagado (S/)" inputmode="decimal" autocomplete="off"
                    ayuda="Al elegir el concepto te proponemos la tarifa vigente para ti. Corrígelo si pagaste otro monto." required />
                <x-portal.archivo nombre="recibo" etiqueta="Recibo escaneado" acepta="image/jpeg,image/png,application/pdf"
                    formatos="JPG, PNG o PDF · hasta 5 MB" />

                <x-portal.boton class="mt-1 w-full" wire:loading.attr="disabled" wire:target="registrar,recibo">Registrar pago</x-portal.boton>
            </form>
        </x-portal.tarjeta>
    </div>
</div>
