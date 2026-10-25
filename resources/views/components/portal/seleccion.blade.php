@props(['nombre', 'etiqueta', 'opciones', 'ayuda' => null, 'vacio' => null, 'vivo' => false, 'banderas' => false])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    <div x-data="selectorCiln({ buscable: {{ count($opciones) > 8 ? 'true' : 'false' }} })" x-modelable="valor" {{ $vivo ? 'wire:model.live' : 'wire:model' }}="{{ $nombre }}"
        x-on:click.outside="abierto && cerrar(false)" x-on:keydown.escape.prevent.stop="abierto && cerrar()" class="relative">
        <button type="button" id="{{ $id }}" x-ref="boton" aria-haspopup="listbox" x-bind:aria-expanded="abierto" aria-controls="{{ $id }}-lista"
            @if ($conError) aria-invalid="true" @endif
            @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
            x-on:click="abierto ? cerrar() : abrir()" x-on:keydown.down.prevent="mover(1)" x-on:keydown.up.prevent="mover(-1)"
            x-on:keydown.enter.prevent="abierto ? elegir(visibles[activo]) : abrir()"
            {{ $attributes->class('control flex items-center gap-2.5 text-left') }} x-bind:class="abierto && 'border-oro-400 shadow-foco'">
            <span x-show="elegida?.querySelector('[data-bandera]')" x-html="elegida?.querySelector('[data-bandera]')?.innerHTML" class="flex shrink-0"></span>
            <span class="min-w-0 flex-1 truncate" x-bind:class="elegida ? 'text-tinta' : 'text-apagado'" x-text="elegida?.dataset.texto ?? @js($vacio ?? 'Elige una opción')">{{ $vacio ?? 'Elige una opción' }}</span>
            <x-heroicon-o-chevron-down aria-hidden="true" class="size-4 shrink-0 text-apagado transition-transform" x-bind:class="abierto && 'rotate-180'" />
        </button>

        <div x-show="abierto" x-cloak x-transition.opacity.duration.150ms
            class="absolute top-[calc(100%+6px)] left-0 z-30 flex min-w-full flex-col gap-0.5 rounded-interna bg-tarjeta p-1.5 shadow-dropdown">
            <label x-show="buscable" class="mb-1 flex items-center gap-2 rounded-[10px] bg-lienzo px-2.5 py-2 text-apagado">
                <x-heroicon-o-magnifying-glass aria-hidden="true" class="size-4" />
                <span class="sr-only">Buscar en {{ $etiqueta }}</span>
                <input type="text" x-ref="busqueda" x-model="consulta" x-on:input="activo = 0" placeholder="Buscar…" autocomplete="off"
                    x-on:keydown.down.prevent="mover(1)" x-on:keydown.up.prevent="mover(-1)" x-on:keydown.enter.prevent="elegir(visibles[activo])"
                    class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-tinta outline-none focus:ring-0">
            </label>
            <ul id="{{ $id }}-lista" x-ref="lista" role="listbox" aria-label="{{ $etiqueta }}" tabindex="-1" class="flex max-h-[250px] flex-col gap-0.5 overflow-auto">
                @if ($vacio)
                    <li role="option" data-valor="" data-texto="{{ $vacio }}" x-show="coincide($el.dataset.texto)" x-on:click="elegir($el)"
                        x-bind:aria-selected="elegida === $el" x-bind:class="esActiva($el) && 'bg-acento-claro'"
                        class="cursor-pointer rounded-[10px] px-2.5 py-[9px] text-[14.5px] text-apagado hover:bg-acento-claro">{{ $vacio }}</li>
                @endif
                @foreach ($opciones as $valor => $texto)
                    <li role="option" data-valor="{{ $valor }}" data-texto="{{ $texto }}" x-show="coincide($el.dataset.texto)" x-on:click="elegir($el)"
                        x-bind:aria-selected="elegida === $el" x-bind:class="{ 'bg-acento-claro': esActiva($el), 'bg-fila-hover font-semibold': elegida === $el }"
                        class="flex cursor-pointer items-center gap-2.5 rounded-[10px] px-2.5 py-[9px] text-[14.5px] whitespace-nowrap hover:bg-acento-claro">
                        @if ($banderas)
                            <span data-bandera class="flex shrink-0"><x-ciln.hoja-bandera :idioma="$texto" size="20" /></span>
                        @endif
                        <span class="flex-1">{{ $texto }}</span>
                        <x-heroicon-o-check aria-hidden="true" class="size-4 text-oro-600" x-show="elegida === $el" x-cloak />
                    </li>
                @endforeach
                <li x-show="visibles.length === 0" x-cloak class="px-2.5 py-3 text-sm text-apagado">Sin resultados</li>
            </ul>
        </div>
    </div>
</x-portal.control>
