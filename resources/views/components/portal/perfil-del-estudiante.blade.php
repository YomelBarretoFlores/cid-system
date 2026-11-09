@props(['ficha', 'lateral' => true, 'esDocente' => false])
<div class="flex flex-col gap-2 sm:gap-3.5">
    <section aria-labelledby="titulo-perfil" class="flex flex-col gap-5 rounded-tarjeta bg-tarjeta p-5 sm:flex-row sm:items-center sm:px-7 sm:py-7">
        <x-portal.avatar :nombre="strtok($ficha->nombres, ' ').' '.strtok($ficha->nombreCompleto, ' ')"
            :foto="$ficha->versionDeFoto === null ? null : route('estudiante.perfil.foto.ver', ['v' => $ficha->versionDeFoto])" class="size-24 text-[28px] ring-4 sm:size-28" />
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <h1 id="titulo-perfil" class="text-[clamp(22px,2.4vw,30px)] leading-tight font-semibold tracking-[-0.01em] text-balance">{{ mb_strtoupper(str_replace(',', '', $ficha->nombreCompleto)) }}</h1>
            <span class="text-secundario">{{ $ficha->documento }}</span>
            <span class="text-[14px] text-apagado">Centro de Idiomas y Lenguas Nativas · UNASAM</span>
        </div>
        @isset($acciones)
            <div class="flex flex-wrap gap-2 sm:self-start">{{ $acciones }}</div>
        @endisset
    </section>

    @if ($lateral)
        <div class="grid items-start gap-2 sm:gap-3.5 xl:grid-cols-[280px_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
                <x-portal.tarjeta titulo="Datos personales">
                    <dl class="flex flex-col gap-3 text-[14.5px]">
                        @foreach ([['Documento', $ficha->documento], ['Correo', $ficha->correo], ['Celular', $ficha->celular], ['Dirección', $ficha->direccion], ['Fecha de nacimiento', $ficha->fechaNacimiento]] as [$etiqueta, $valor])
                            <div class="flex flex-col">
                                <dt class="text-[13px] text-apagado">{{ $etiqueta }}</dt>
                                <dd class="break-words">{{ $valor ?? 'Sin registrar' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-portal.tarjeta>
                <x-portal.tarjeta titulo="Estado">
                    <div class="flex flex-wrap gap-2">
                        <x-portal.insignia color="success">Activo</x-portal.insignia>
                        @if ($ficha->vinculo)
                            <x-portal.insignia color="info">{{ $ficha->vinculo }}</x-portal.insignia>
                        @endif
                        @if ($esDocente)
                            <x-portal.insignia>Docente</x-portal.insignia>
                        @endif
                    </div>
                    @if ($esDocente)
                        <p class="text-[14.5px] text-secundario">Registra la asistencia y las notas parciales de tus grupos.</p>
                        <a href="{{ route('filament.docente.pages.mis-grupos') }}" class="enlace text-[14.5px]">Ir a mis grupos</a>
                    @endif
                </x-portal.tarjeta>
            </div>
            <div class="flex min-w-0 flex-col gap-2 sm:gap-3.5">{{ $slot }}</div>
        </div>
    @else
        {{ $slot }}
    @endif
</div>
