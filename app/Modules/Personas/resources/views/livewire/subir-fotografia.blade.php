<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Subir fotografía" :volver="route('estudiante.inicio')">
        Tu fotografía solo la ven tú y el personal del CILN.
    </x-portal.encabezado>

    <x-portal.tarjeta>
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
            <div class="flex flex-col items-center gap-3">
                @php($actual = $ficha->versionDeFoto === null ? null : route('estudiante.perfil.foto.ver', ['v' => $ficha->versionDeFoto]))
                @if ($foto && ! $errors->has('foto') && $foto->isPreviewable())
                    <x-portal.avatar :nombre="$ficha->nombres" :foto="$foto->temporaryUrl()" class="size-40 ring-4" />
                    <span class="text-[13px] text-apagado">Vista previa</span>
                @else
                    <x-portal.avatar :nombre="strtok($ficha->nombres, ' ').' '.strtok($ficha->nombreCompleto, ' ')" :foto="$actual" class="size-40 text-[40px] ring-4" />
                    <span class="text-[13px] text-apagado">{{ $actual ? 'Fotografía actual' : 'Sin fotografía' }}</span>
                @endif
            </div>

            <form wire:submit="guardar" class="flex min-w-0 flex-1 flex-col gap-3.5" novalidate>
                <x-portal.archivo nombre="foto" etiqueta="Fotografía" acepta="image/jpeg,image/png"
                    formatos="JPG o PNG · hasta 2 MB · entre 200 y 3000 píxeles por lado"
                    ayuda="Usa una foto de frente, con fondo claro y sin lentes oscuros." />
                <div class="flex flex-wrap gap-2">
                    <x-portal.boton wire:loading.attr="disabled" wire:target="guardar,foto">Guardar fotografía</x-portal.boton>
                    @if ($actual)
                        <x-portal.boton type="button" variante="secundario" wire:click="quitar" wire:confirm="¿Quitar tu fotografía?" accion="quitar">Quitar fotografía</x-portal.boton>
                    @endif
                </div>
            </form>
        </div>
    </x-portal.tarjeta>
</div>
