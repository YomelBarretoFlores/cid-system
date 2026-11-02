@props(['title' => null])
{{-- Sign-in, registration and password screens, minimal like the UNASAM SGA: one white card on the institutional blue, the CILN mark in the background. --}}
<x-layouts.base :title="$title" :marco="false" data-acceso-institucional
    class="relative flex items-center justify-center overflow-hidden bg-institucional px-4 py-8 sm:py-12">
    <x-ciln.emblema blanco decorativo class="pointer-events-none absolute top-1/2 left-[6%] hidden w-[30%] max-w-[420px] -translate-y-1/2 opacity-[0.14] lg:block" />

    <div class="relative flex w-full max-w-[440px] flex-col gap-6 rounded-tarjeta bg-tarjeta p-6 shadow-[0_24px_60px_rgb(15_23_42/0.35)] sm:p-8">
        <header class="flex flex-col items-center gap-3 text-center">
            <div class="flex items-center justify-center gap-4">
                <img src="{{ asset('images/unasam/logo.png') }}" alt="Universidad Nacional Santiago Antúnez de Mayolo" width="190" height="56" class="h-auto w-[170px]">
                <span class="h-10 w-px bg-borde" aria-hidden="true"></span>
                <x-ciln.emblema class="w-[58px]" />
            </div>
            <p class="flex flex-col leading-tight">
                <span class="text-[15px] text-secundario">Bienvenido al</span>
                <span class="text-[19px] font-semibold tracking-[-0.01em]">Sistema de Gestión Académica</span>
                <span class="text-[14px] text-apagado">Centro de Idiomas y Lenguas Nativas · UNASAM</span>
            </p>
        </header>

        <main id="contenido" class="flex flex-col gap-[18px] border-t border-separador pt-5">
            @if (session('estado'))
                <x-portal.alerta tipo="exito">{{ session('estado') }}</x-portal.alerta>
            @endif
            @if (session('error'))
                <x-portal.alerta tipo="error">{{ session('error') }}</x-portal.alerta>
            @endif

            {{ $slot }}
        </main>
    </div>
</x-layouts.base>
