@props(['nombre', 'etiqueta', 'ayuda' => null, 'min' => null, 'max' => null, 'vacio' => 'Elige una fecha'])
@php
    $id = 'campo-'.str_replace(['.', '_'], '-', $nombre);
    $conError = $errors->has($nombre);
    $descripciones = collect([$ayuda ? "{$id}-ayuda" : null, $conError ? "{$id}-error" : null])->filter()->implode(' ');
    $configuracion = ['hoy' => now('America/Lima')->toDateString(), 'min' => (string) $min, 'max' => (string) $max];
@endphp
<x-portal.control :id="$id" :etiqueta="$etiqueta" :nombre="$nombre" :ayuda="$ayuda">
    <div x-data="fechaCiln(@js($configuracion))" x-modelable="valor" wire:model="{{ $nombre }}"
        x-on:click.outside="abierto = false" x-on:keydown.escape.prevent.stop="abierto && cerrar()" class="relative">
        <button type="button" id="{{ $id }}" x-ref="boton" aria-haspopup="dialog" x-bind:aria-expanded="abierto" aria-controls="{{ $id }}-calendario"
            @if ($conError) aria-invalid="true" @endif
            @if ($descripciones) aria-describedby="{{ $descripciones }}" @endif
            x-on:click="abierto ? cerrar() : abrir()"
            {{ $attributes->class('control flex items-center gap-2.5 text-left') }} x-bind:class="abierto && 'border-acento shadow-foco'">
            <x-heroicon-o-calendar aria-hidden="true" class="size-[17px] shrink-0 text-apagado" />
            <span class="flex-1 whitespace-nowrap" x-bind:class="valor ? 'text-tinta' : 'text-apagado'" x-text="etiqueta || @js($vacio)">{{ $vacio }}</span>
        </button>

        <div id="{{ $id }}-calendario" role="dialog" aria-label="Calendario de {{ $etiqueta }}" x-show="abierto" x-cloak x-transition.opacity.duration.150ms
            x-bind:class="alDerecha ? 'right-0' : 'left-0'" class="absolute top-[calc(100%+6px)] z-30 flex w-[300px] max-w-[calc(100vw-2rem)] flex-col gap-2.5 rounded-[18px] bg-tarjeta p-3.5 shadow-dropdown">
            <div class="flex items-center justify-between">
                <button type="button" x-on:click="desplazar(-1)" class="flex size-8 items-center justify-center rounded-[10px] bg-lienzo hover:bg-piedra-200 focus-visible:shadow-foco focus-visible:outline-none">
                    <x-heroicon-o-chevron-left aria-hidden="true" class="size-3.5" /><span class="sr-only">Anterior</span>
                </button>
                <button type="button" x-on:click="vista = vista === 'dias' ? 'anios' : 'dias'" class="rounded-[10px] px-2 py-1 text-[15px] font-semibold hover:bg-lienzo focus-visible:shadow-foco focus-visible:outline-none"
                    x-text="vista === 'dias' ? tituloMes : `${anios[0]} – ${anios[11]}`" x-bind:aria-label="vista === 'dias' ? 'Elegir año' : 'Volver a los días'"></button>
                <button type="button" x-on:click="desplazar(1)" class="flex size-8 items-center justify-center rounded-[10px] bg-lienzo hover:bg-piedra-200 focus-visible:shadow-foco focus-visible:outline-none">
                    <x-heroicon-o-chevron-right aria-hidden="true" class="size-3.5" /><span class="sr-only">Siguiente</span>
                </button>
            </div>

            <div x-show="vista === 'dias'" class="flex flex-col gap-2.5">
                <div aria-hidden="true" class="grid grid-cols-7 gap-0.5 text-center text-xs text-apagado">
                    <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="grid grid-cols-7 gap-0.5">
                    <template x-for="dia in dias" x-bind:key="dia.valor">
                        <button type="button" x-on:click="elegir(dia.valor)" x-bind:disabled="dia.bloqueado" x-text="dia.numero" x-bind:aria-label="dia.valor"
                            x-bind:aria-pressed="dia.valor === valor"
                            class="h-9 rounded-[10px] text-sm focus-visible:shadow-foco focus-visible:outline-none disabled:cursor-not-allowed"
                            x-bind:class="{
                                'bg-tinta font-semibold text-invertido': dia.valor === valor,
                                'font-semibold shadow-[inset_0_0_0_1.5px_var(--color-acento)]': dia.valor === hoy && dia.valor !== valor,
                                'text-piedra-300': dia.bloqueado,
                                'text-piedra-400': dia.fuera && ! dia.bloqueado && dia.valor !== valor,
                                'hover:bg-acento-claro': ! dia.bloqueado && dia.valor !== valor,
                            }"></button>
                    </template>
                </div>
            </div>

            <div x-show="vista === 'anios'" x-cloak class="grid grid-cols-3 gap-1">
                {{-- Not "anio": the loop variable would shadow the component's anio and elegirAnio() would write to it. --}}
                <template x-for="opcion in anios" x-bind:key="opcion">
                    <button type="button" x-on:click="elegirAnio(opcion)" x-text="opcion"
                        class="h-10 rounded-[10px] text-sm hover:bg-acento-claro focus-visible:shadow-foco focus-visible:outline-none"
                        x-bind:class="opcion === Number(String(valor ?? '').slice(0, 4)) && 'bg-tinta font-semibold text-invertido hover:bg-tinta'"></button>
                </template>
            </div>

            <div class="flex justify-between border-t border-separador pt-1.5">
                <button type="button" x-on:click="elegir('')" class="text-sm text-apagado hover:text-tinta">Limpiar</button>
                <button type="button" x-on:click="elegir(hoy)" x-bind:disabled="bloqueado(hoy)" class="enlace text-sm font-semibold disabled:opacity-40">Hoy</button>
            </div>
        </div>
    </div>
</x-portal.control>
