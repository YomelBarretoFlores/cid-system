<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Mis pagos</h1>
        <p class="text-sm text-slate-600">Registra tus pagos con el recibo escaneado. El CILN los revisa y aquí verás si fueron validados.</p>
    </header>

    <section aria-labelledby="titulo-registrar" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <h2 id="titulo-registrar" class="mb-6 text-lg font-semibold text-ciln-900">Registrar un pago</h2>

        <form wire:submit="registrar" class="flex flex-col gap-6" novalidate>
            @error('registro')
                <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
            @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="campo-concepto-id" class="text-sm font-medium text-slate-800">Concepto</label>
                    <select id="campo-concepto-id" wire:model.live="concepto_id"
                        @error('concepto_id') aria-invalid="true" aria-describedby="campo-concepto-id-error" @enderror
                        @class([
                            'block w-full rounded-md border bg-white px-3 py-2 text-base text-slate-900 shadow-xs focus:outline-2 focus:outline-offset-0 sm:text-sm',
                            'border-red-600 focus:outline-red-600' => $errors->has('concepto_id'),
                            'border-slate-300 focus:outline-ciln-600' => ! $errors->has('concepto_id'),
                        ])>
                        <option value="">Elige el concepto que pagaste</option>
                        @foreach ($conceptos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('concepto_id')
                        <p id="campo-concepto-id-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <x-portal.seleccion nombre="medio_pago" etiqueta="Medio de pago" :opciones="$mediosDePago" vacio="Elige cómo pagaste" />
                <x-portal.campo nombre="numero_operacion" etiqueta="N.° de recibo u operación" autocomplete="off" maxlength="40" required />
                <x-portal.campo nombre="fecha_pago" etiqueta="Fecha de pago" tipo="date" :max="$hoy" required />
                <x-portal.campo nombre="monto" etiqueta="Monto pagado (S/)" inputmode="decimal" autocomplete="off"
                    ayuda="Al elegir el concepto te proponemos la tarifa vigente para ti. Corrígelo si pagaste otro monto." required />

                <div class="sm:col-span-2">
                    <x-portal.archivo nombre="recibo" etiqueta="Recibo escaneado" acepta="image/jpeg,image/png,application/pdf"
                        ayuda="Una foto JPG o PNG, o un PDF, de hasta 5 MB." />
                </div>
            </div>

            <x-portal.boton class="self-start" wire:loading.attr="disabled" wire:target="registrar,recibo">Registrar pago</x-portal.boton>
        </form>
    </section>

    <section aria-labelledby="titulo-pagos" class="flex flex-col gap-4">
        <h2 id="titulo-pagos" class="text-lg font-semibold text-ciln-900">Pagos registrados</h2>

        @if ($pagos->isEmpty())
            <x-portal.alerta>Todavía no registraste pagos.</x-portal.alerta>
        @else
            <ul class="flex flex-col gap-3 sm:hidden">
                @foreach ($pagos as $pago)
                    <li wire:key="tarjeta-{{ $pago->ulid }}" class="flex flex-col gap-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium text-slate-900">{{ $pago->concepto->nombre }}</p>
                            <x-portal.insignia :color="$pago->estado->color()">{{ $pago->estado->etiqueta() }}</x-portal.insignia>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                            <dt class="text-slate-600">Monto</dt>
                            <dd class="text-right font-medium text-slate-900">S/ {{ $pago->monto }}</dd>
                            <dt class="text-slate-600">Fecha de pago</dt>
                            <dd class="text-right text-slate-900">{{ $pago->fecha_pago->format('d/m/Y') }}</dd>
                            <dt class="text-slate-600">Medio</dt>
                            <dd class="text-right text-slate-900">{{ $pago->medio_pago->etiqueta() }}</dd>
                            <dt class="text-slate-600">Operación</dt>
                            <dd class="text-right break-all text-slate-900">{{ $pago->numero_operacion }}</dd>
                        </dl>
                        @if ($pago->motivo_rechazo)
                            <p class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-900">Motivo del rechazo: {{ $pago->motivo_rechazo }}</p>
                        @endif
                        <a href="{{ route('estudiante.pagos.recibo', $pago) }}" target="_blank" rel="noopener"
                            class="self-start text-sm font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ciln-700">
                            Ver recibo<span class="sr-only"> de la operación {{ $pago->numero_operacion }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="hidden overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200 sm:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Pagos registrados, del más reciente al más antiguo</caption>
                    <thead class="bg-slate-50 text-left text-slate-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Concepto</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Operación</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Monto</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                            <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Recibo</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($pagos as $pago)
                            <tr wire:key="fila-{{ $pago->ulid }}" class="align-top">
                                <td class="px-4 py-3 text-slate-900">{{ $pago->concepto->nombre }}</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <span class="block">{{ $pago->numero_operacion }}</span>
                                    <span class="block text-xs text-slate-600">{{ $pago->medio_pago->etiqueta() }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-900">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-medium text-slate-900">S/ {{ $pago->monto }}</td>
                                <td class="px-4 py-3">
                                    <x-portal.insignia :color="$pago->estado->color()">{{ $pago->estado->etiqueta() }}</x-portal.insignia>
                                    @if ($pago->motivo_rechazo)
                                        <p class="mt-1 max-w-xs text-xs text-red-800">Motivo: {{ $pago->motivo_rechazo }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('estudiante.pagos.recibo', $pago) }}" target="_blank" rel="noopener"
                                        class="font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ciln-700">
                                        Ver recibo<span class="sr-only"> de la operación {{ $pago->numero_operacion }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
