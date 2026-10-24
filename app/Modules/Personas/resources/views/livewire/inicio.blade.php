<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Hola, {{ $ficha->nombres }}</h1>
        <p class="text-sm text-slate-600">{{ $ficha->nombreCompleto }} · {{ $ficha->documento }}</p>
    </header>

    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
        @foreach ([
            ['estudiante.pagos', 'Mis pagos', 'Sube el recibo de tu pago y revisa si el CILN ya lo validó.'],
            ['estudiante.matricula', 'Matrícula', 'Elige tu grupo y matricúlate con un pago de matrícula validado.'],
            ['estudiante.matriculas', 'Mis matrículas', 'Consulta tus grupos, horarios y aulas.'],
            ['estudiante.clasificacion', 'Examen de clasificación', 'Solicita el examen para empezar en un ciclo superior al básico.'],
        ] as [$ruta, $titulo, $descripcion])
            <li wire:key="{{ $ruta }}">
                <a href="{{ route($ruta) }}" class="flex h-full flex-col gap-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-ciln-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ciln-700">
                    <span class="font-semibold text-ciln-800">{{ $titulo }}</span>
                    <span class="text-sm text-slate-600">{{ $descripcion }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
