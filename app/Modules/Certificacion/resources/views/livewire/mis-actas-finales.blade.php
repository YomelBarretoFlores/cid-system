@use('App\Modules\Certificacion\Enums\EstadoActa')
@use('App\Shared\Ui\Formato')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Actas finales" antetitulo="Portal docente">
        Actas que validaste, con su estado y su PDF.
    </x-portal.encabezado>

    <x-portal.tarjeta>
        @if ($actas->isEmpty())
            <x-portal.vacio titulo="Aún no has cerrado actas finales. Ciérralas desde Mis grupos, en la pestaña Acta final de cada grupo." icono="heroicon-o-document-check" />
        @else
            <ul class="flex flex-col gap-1">
                @foreach ($actas as $acta)
                    <x-portal.fila wire:key="acta-{{ $acta->id }}" :titulo="$acta->grupo_codigo.' · '.$acta->idioma.' '.$acta->ciclo_nombre"
                        :detalle="$acta->periodo.' · '.$acta->aprobados_count.' de '.$acta->notas_count.' aprobados · validada el '.Formato::fechaCorta($acta->validada_en->setTimezone('America/Lima'))">
                        <x-slot:inicio><x-ciln.hoja-bandera :idioma="$acta->idioma" size="26" /></x-slot:inicio>
                        <x-slot:fin>
                            <x-portal.insignia :color="$acta->estado === EstadoActa::Cerrada ? 'success' : 'warning'">{{ $acta->estado->etiqueta() }}</x-portal.insignia>
                            <x-portal.boton variante="secundario" tamano="compacto" :href="route('docente.acta', $acta->grupo_id)">Ver acta<span class="sr-only"> del grupo {{ $acta->grupo_codigo }}</span></x-portal.boton>
                        </x-slot:fin>
                    </x-portal.fila>
                @endforeach
            </ul>
        @endif
    </x-portal.tarjeta>
</div>
