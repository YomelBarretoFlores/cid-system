<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Hola, {{ $ficha->nombres }}" :antetitulo="$ficha->nombreCompleto.' · '.$ficha->documento" saludo>
        Desde aquí registras tus pagos, te matriculas y revisas tus grupos.
    </x-portal.encabezado>

    <ul class="grid grid-cols-[minmax(0,1fr)] gap-2 sm:grid-cols-2 sm:gap-3.5 xl:grid-cols-4" role="list">
        @foreach ([
            ['estudiante.pagos', 'Mis pagos', 'Sube el recibo de tu pago y revisa si el CILN ya lo validó.', 'heroicon-o-banknotes'],
            ['estudiante.matricula', 'Matrícula', 'Elige tu grupo y matricúlate con un pago de matrícula validado.', 'heroicon-o-pencil-square'],
            ['estudiante.matriculas', 'Mis matrículas', 'Consulta tus grupos, horarios y aulas.', 'heroicon-o-academic-cap'],
            ['estudiante.clasificacion', 'Examen de clasificación', 'Solicita el examen para empezar en un ciclo superior al básico.', 'heroicon-o-clipboard-document-check'],
            ...($esDocente ? [['docente.grupos', 'Mis grupos', 'Registra la asistencia y las notas parciales de tus grupos.', 'heroicon-o-user-group']] : []),
        ] as [$ruta, $titulo, $descripcion, $icono])
            <li wire:key="{{ $ruta }}">
                <a href="{{ route($ruta) }}" class="group flex h-full flex-col gap-3 rounded-tarjeta bg-tarjeta p-6 transition-shadow hover:shadow-[inset_0_0_0_2px_var(--color-oro-300)] focus-visible:shadow-foco focus-visible:outline-none">
                    <span class="flex size-11 items-center justify-center rounded-control bg-acento-claro">
                        <x-dynamic-component :component="$icono" aria-hidden="true" class="size-[22px] text-oro-600" />
                    </span>
                    <span class="flex items-center justify-between gap-2 text-[17px] font-semibold">
                        {{ $titulo }}
                        <x-heroicon-o-arrow-right aria-hidden="true" class="size-4 text-apagado transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <span class="text-[14.5px] text-terciario">{{ $descripcion }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
