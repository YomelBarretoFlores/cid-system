@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('Illuminate\Support\Str')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Reportes">Tu avance de notas, asistencia, boleta, horario y récord por periodo. Cada reporte se abre en PDF en una pestaña nueva.</x-portal.encabezado>

    @if ($reportes === null)
        <x-portal.tarjeta>
            <x-portal.vacio titulo="Aún no tienes reportes." icono="heroicon-o-document-chart-bar">Aparecen cuando te matriculas en un periodo.</x-portal.vacio>
        </x-portal.tarjeta>
    @else
        <x-portal.tarjeta>
            <x-portal.seleccion nombre="periodo" etiqueta="Periodo" :opciones="$periodos" vivo class="sm:max-w-sm" />
        </x-portal.tarjeta>

        @foreach ($reportes->datos->matriculas as $matricula)
            @php($grupo = $reportes->datos->grupos[$matricula->grupo_id])
            @php($avance = $reportes->avances[$matricula->id])
            @php($asistencia = $reportes->asistencias[$matricula->id])
            <div wire:key="reportes-{{ $matricula->id }}" class="grid gap-2 sm:gap-3.5 lg:grid-cols-2">
                <x-portal.tarjeta titulo="Avance de notas" :descripcion="$grupo->codigo.' · '.$grupo->idioma.' · Ciclo '.$grupo->cicloNumero">
                    <x-slot:acciones>
                        <x-matricula::reportes.pdf :href="route('estudiante.reportes.avance', $matricula)" :descripcion="'del avance de notas del grupo '.$grupo->codigo" />
                    </x-slot:acciones>

                    @if (! $avance->disponible)
                        <p class="text-secundario">{{ $avance->aviso }}</p>
                    @else
                        <ul role="list" class="flex flex-col">
                            @foreach ($avance->componentes as $componente)
                                <x-portal.fila wire:key="componente-{{ $matricula->id }}-{{ $componente->codigo }}" :titulo="$componente->nombre" :detalle="'Peso '.$componente->peso.' %'">
                                    <x-slot:fin><span class="font-semibold">{{ $componente->nota ?? '—' }}</span></x-slot:fin>
                                </x-portal.fila>
                            @endforeach
                        </ul>
                        <div class="flex flex-wrap items-center justify-between gap-2 px-3.5">
                            <span class="text-secundario">{{ $avance->actaCerrada ? 'Promedio final' : 'Promedio parcial' }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-xl font-semibold">{{ $avance->promedio ?? '—' }}</span>
                                @if ($avance->estadoFinal)
                                    <x-portal.insignia :color="$avance->estadoFinal === EstadoFinal::Aprobado ? 'success' : 'danger'">{{ $avance->estadoFinal->etiqueta() }}</x-portal.insignia>
                                @else
                                    <x-portal.insignia color="warning">Acta abierta</x-portal.insignia>
                                @endif
                            </span>
                        </div>
                    @endif
                </x-portal.tarjeta>

                <x-portal.tarjeta titulo="Asistencia" :descripcion="$grupo->codigo.' · '.$grupo->idioma.' · Ciclo '.$grupo->cicloNumero">
                    <x-slot:acciones>
                        <x-matricula::reportes.pdf :href="route('estudiante.reportes.asistencia', $matricula)" :descripcion="'de la asistencia del grupo '.$grupo->codigo" />
                    </x-slot:acciones>

                    <dl class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
                        <x-portal.dato etiqueta="Clases dictadas">{{ $asistencia->dictadas }} de {{ $asistencia->programadas }} programadas</x-portal.dato>
                        <x-portal.dato etiqueta="Presente · Ausente">{{ $asistencia->presentes }} · {{ $asistencia->faltas }}</x-portal.dato>
                        <x-portal.dato etiqueta="Sobre clases dictadas">{{ $asistencia->porcentajeSobreDictadas() === null ? '—' : $asistencia->porcentajeSobreDictadas().' %' }}</x-portal.dato>
                        <x-portal.dato etiqueta="Sobre clases programadas">{{ $asistencia->porcentajeSobreProgramadas() === null ? '—' : $asistencia->porcentajeSobreProgramadas().' %' }}</x-portal.dato>
                    </dl>
                </x-portal.tarjeta>
            </div>
        @endforeach

        <x-portal.tarjeta titulo="Boleta de notas" :descripcion="'Periodo '.$reportes->periodo">
            <x-slot:acciones>
                <x-matricula::reportes.pdf :href="route('estudiante.reportes.boleta', $reportes->periodoId)" :descripcion="'de la boleta de notas del periodo '.$reportes->periodo" />
            </x-slot:acciones>

            <ul role="list" class="flex flex-col">
                @foreach ($reportes->boleta as $fila)
                    <x-portal.fila wire:key="boleta-{{ $fila->matriculaId }}" :titulo="$fila->grupoCodigo.' · '.$fila->idioma" :detalle="$fila->cicloNombre.' · '.$fila->horasLectivas.' horas lectivas'">
                        <x-slot:fin>
                            <span class="font-semibold">{{ $fila->promedio ?? '—' }}</span>
                            <x-portal.insignia :color="$fila->actaCerrada() ? ($fila->estadoFinal === EstadoFinal::Aprobado ? 'success' : 'danger') : 'warning'">{{ $fila->estado() }}</x-portal.insignia>
                        </x-slot:fin>
                    </x-portal.fila>
                @endforeach
            </ul>
        </x-portal.tarjeta>

        <x-portal.hoja titulo="Horario personalizado" :periodo="$reportes->periodo">
            <x-slot:acciones>
                <x-portal.boton variante="secundario" :href="route('estudiante.reportes.horario', $reportes->periodoId)" target="_blank" rel="noopener" tamano="compacto">PDF<span class="sr-only"> del horario del periodo {{ $reportes->periodo }}</span></x-portal.boton>
                <x-portal.boton variante="secundario" :href="route('estudiante.reportes.horario.imprimir', $reportes->periodoId)" target="_blank" rel="noopener" tamano="compacto">Imprimir<span class="sr-only"> el horario del periodo {{ $reportes->periodo }}</span></x-portal.boton>
            </x-slot:acciones>

            @if ($reportes->horario->grupos === [])
                <p class="text-secundario">No tiene clases programadas en este periodo.</p>
            @else
                @include('matricula::documentos.horario', ['horario' => $reportes->horario, 'pdf' => false])
            @endif
        </x-portal.hoja>
    @endif

    @if ($records !== [])
        <x-portal.tarjeta titulo="Récord por idioma" descripcion="Informativo, sin valor oficial; para trámites solicita la constancia de notas.">
            <x-slot:acciones>
                <x-matricula::reportes.pdf :href="route('estudiante.reportes.record')" descripcion="del récord de todos los idiomas">Récord completo (PDF)</x-matricula::reportes.pdf>
            </x-slot:acciones>

            <ul role="list" class="flex flex-col">
                @foreach ($records as $record)
                    <x-portal.fila wire:key="record-{{ $record->idioma }}" :titulo="$record->idioma" :detalle="'Promedio de ciclos aprobados: '.($record->promedio ?? '—')">
                        <x-slot:fin>
                            <x-matricula::reportes.pdf :href="route('estudiante.reportes.record', Str::slug($record->idioma))" :descripcion="'del récord de '.$record->idioma" />
                        </x-slot:fin>
                        @foreach ($record->faltantes as $nivel)
                            <span>{{ $nivel->descripcion() }}</span>
                        @endforeach
                    </x-portal.fila>
                @endforeach
            </ul>
        </x-portal.tarjeta>
    @endif
</div>
