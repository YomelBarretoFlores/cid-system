@props(['title' => null])
<x-layouts.base :title="$title" class="grid grid-cols-[minmax(0,1fr)] gap-3.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
    <div class="flex flex-col gap-7 rounded-tarjeta bg-tarjeta p-6 sm:p-[clamp(28px,4vw,56px)]">
        <a href="{{ route('estudiante.ingresar') }}" class="flex items-center gap-3 self-start text-tinta">
            <x-ciln.emblema class="w-[52px]" />
            <span class="flex flex-col leading-[1.1]">
                <span class="text-[17px] font-semibold">Centro de Idiomas</span>
                <span class="text-xs tracking-[0.12em] text-apagado">UNASAM</span>
            </span>
        </a>

        <main id="contenido" class="mx-auto flex w-full max-w-[460px] flex-1 flex-col justify-center gap-[22px]">
            @if (session('estado'))
                <x-portal.alerta tipo="exito">{{ session('estado') }}</x-portal.alerta>
            @endif
            @if (session('error'))
                <x-portal.alerta tipo="error">{{ session('error') }}</x-portal.alerta>
            @endif

            {{ $slot }}
        </main>

        <span class="text-[13px] text-apagado">Universidad Nacional Santiago Antúnez de Mayolo · Huaraz</span>
    </div>

    <div aria-hidden="true" class="relative hidden min-h-[520px] flex-col justify-end overflow-hidden rounded-tarjeta bg-acento-claro p-[clamp(28px,4vw,56px)] lg:flex">
        <x-ciln.emblema decorativo class="absolute top-[8%] left-[10%] w-[78%] opacity-95" />
        @foreach ([['Inglés', 6, 6, 52, -24], ['Quechua', 70, 4, 48, 18], ['Francés', 2, 34, 44, -34], ['Alemán', 80, 26, 42, 10], ['Italiano', 8, 56, 42, -14], ['Portugués', 84, 48, 44, 22]] as [$idioma, $x, $y, $tamano, $giro])
            <span class="absolute drop-shadow-[0_4px_8px_rgb(29_27_22/0.12)]" style="left: {{ $x }}%; top: {{ $y }}%">
                <x-ciln.hoja-bandera :idioma="$idioma" :size="$tamano" :rot="$giro" />
            </span>
        @endforeach
        <div class="relative flex max-w-[420px] flex-col gap-2">
            <span class="text-[clamp(24px,2.4vw,32px)] leading-[1.15] font-semibold tracking-[-0.03em]">Matrícula, pagos y certificados en un solo lugar.</span>
            <span class="text-secundario">Idiomas extranjeros y lenguas originarias en la UNASAM.</span>
        </div>
    </div>
</x-layouts.base>
