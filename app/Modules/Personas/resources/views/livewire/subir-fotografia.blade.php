@php($actual = $ficha->versionDeFoto === null ? null : route('estudiante.perfil.foto.ver', ['v' => $ficha->versionDeFoto]))
@php($vistaPrevia = $foto && ! $errors->has('foto') && $foto->isPreviewable() ? $foto->temporaryUrl() : null)
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Fotografía personal" :volver="route('estudiante.inicio')">
        Sube y administra tu fotografía personal para tu perfil del CILN. Solo la ven tú y el personal del CILN.
    </x-portal.encabezado>

    <div class="grid items-start gap-2 sm:gap-3.5 xl:grid-cols-[minmax(0,1fr)_400px]">
        <x-portal.tarjeta titulo="Subir foto">
            <div class="flex flex-col gap-6 md:flex-row md:items-start">
                <figure class="flex shrink-0 flex-col items-center gap-2 self-center md:self-start">
                    <div class="flex aspect-[3/4] w-44 items-end justify-center overflow-hidden rounded-interna bg-gris-fondo">
                        @if ($vistaPrevia ?? $actual)
                            <img src="{{ $vistaPrevia ?? $actual }}" alt="" class="size-full object-cover">
                        @else
                            <x-heroicon-s-user aria-hidden="true" class="size-40 translate-y-6 text-borde" />
                        @endif
                    </div>
                    <figcaption class="text-[13px] text-apagado">{{ $vistaPrevia ? 'Vista previa' : ($actual ? 'Fotografía actual' : 'Sin fotografía') }}</figcaption>
                </figure>

                <form wire:submit="guardar" class="flex min-w-0 flex-1 flex-col gap-3.5" novalidate>
                    <x-portal.archivo nombre="foto" etiqueta="Sube tu fotografía aquí" acepta="image/jpeg,image/png"
                        :formatos="'JPG o PNG · hasta '.$parametros->fotoMaximoMb.' MB · entre '.$parametros->fotoLadoMinimoPx.' y '.$parametros->fotoLadoMaximoPx.' píxeles por lado'" />
                    <div class="flex flex-wrap gap-2">
                        <x-portal.boton wire:loading.attr="disabled" wire:target="guardar,foto">Guardar fotografía</x-portal.boton>
                        @if ($actual)
                            <x-portal.boton type="button" variante="secundario" wire:click="quitar" wire:confirm="¿Quitar tu fotografía?" accion="quitar">Quitar fotografía</x-portal.boton>
                        @endif
                    </div>
                </form>
            </div>
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Especificaciones">
            <x-portal.alerta>Para que tu fotografía sirva en tu perfil y en tus trámites del CILN, debe cumplir estas especificaciones:</x-portal.alerta>
            <section aria-labelledby="titulo-requisitos" class="flex flex-col overflow-hidden rounded-interna border border-borde-interno">
                <h2 id="titulo-requisitos" class="bg-marino-700 px-4 py-2.5 font-semibold text-white">Requisitos</h2>
                <ul class="flex flex-col divide-y divide-separador text-[14.5px]">
                    @foreach (['Imagen a color con fondo blanco.', 'Tomada de frente.', 'Sin gafas ni lentes oscuros, salvo personas con discapacidad visual.', 'Sin gorra ni sombrero.', 'El rostro desde los hombros, sin mostrar medio cuerpo.', "JPG o PNG, de hasta {$parametros->fotoMaximoMb} MB y entre {$parametros->fotoLadoMinimoPx} y {$parametros->fotoLadoMaximoPx} píxeles por lado."] as $requisito)
                        <li class="px-4 py-2.5">{{ $requisito }}</li>
                    @endforeach
                </ul>
            </section>
        </x-portal.tarjeta>
    </div>
</div>
