@props(['title' => null])
{{-- Sign-in, registration and password screens: the public website's header, colors and footer around one white card, so leaving the home for the sign-in keeps the same look. --}}
<x-layouts.base :title="$title" :marco="false" data-acceso-institucional class="tema-publico flex flex-col bg-papel text-marino-900">
    <x-publico.cabecera />

    <div class="flex flex-1 justify-center px-4 pt-10 pb-24 sm:px-6 sm:pt-16">
        <div class="flex w-full max-w-[460px] flex-col gap-6 self-start rounded-tarjeta border border-marino-900/12 border-t-4 border-t-real-600 bg-white p-6 sm:p-8">
            <header class="flex flex-col items-center gap-4 text-center">
                <div class="flex items-center justify-center gap-4">
                    <img src="{{ asset('images/unasam/logo.png') }}" alt="Universidad Nacional Santiago Antúnez de Mayolo" width="190" height="56" class="h-auto w-[160px]">
                    <span class="h-10 w-px bg-borde" aria-hidden="true"></span>
                    <x-ciln.emblema class="w-[56px]" />
                </div>
                <p class="flex flex-col gap-0.5 leading-tight">
                    <span class="text-[15px] text-marino-800/75">Bienvenido al</span>
                    <span class="font-display text-[22px] font-semibold">Sistema de Gestión Académica</span>
                    <span class="text-[14px] text-marino-800/75">Centro de Idiomas y Lenguas Nativas · UNASAM</span>
                </p>
            </header>

            <main id="contenido" class="flex flex-col gap-[18px] border-t border-separador pt-6">
                @if (session('estado'))
                    <x-portal.alerta tipo="exito">{{ session('estado') }}</x-portal.alerta>
                @endif
                @if (session('error'))
                    <x-portal.alerta tipo="error">{{ session('error') }}</x-portal.alerta>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-publico.pie :verificar="false" />
</x-layouts.base>
