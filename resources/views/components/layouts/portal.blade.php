@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? "{$title} · " : '' }}CILN UNASAM</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-ciln-800 focus:shadow">
            Saltar al contenido
        </a>

        <header class="border-b-4 border-oro-400 bg-ciln-800 text-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-3">
                <a href="{{ route('estudiante.inicio') }}" class="flex flex-col leading-tight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    <span class="text-lg font-semibold tracking-wide">CILN</span>
                    <span class="text-xs text-ciln-100">Centro de Idiomas y Lenguas Nativas · UNASAM</span>
                </a>

                @auth
                    @if (auth()->user()->hasVerifiedEmail())
                        <nav aria-label="Portal del estudiante" class="flex flex-wrap items-center gap-1 text-sm">
                            <x-portal.enlace-menu :ruta="'estudiante.inicio'">Inicio</x-portal.enlace-menu>
                            <x-portal.enlace-menu :ruta="'estudiante.pagos'">Mis pagos</x-portal.enlace-menu>
                            <x-portal.enlace-menu :ruta="'estudiante.matricula'">Matrícula</x-portal.enlace-menu>
                            <x-portal.enlace-menu :ruta="'estudiante.matriculas'">Mis matrículas</x-portal.enlace-menu>
                        </nav>
                    @endif

                    <form method="POST" action="{{ route('estudiante.salir') }}">
                        @csrf
                        <button type="submit" class="rounded-md px-3 py-2 text-sm text-ciln-100 hover:bg-ciln-700 hover:text-white focus-visible:outline-2 focus-visible:outline-white">
                            Salir
                        </button>
                    </form>
                @endauth
            </div>
        </header>

        <main id="contenido" class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-8">
            @if (session('estado'))
                <x-portal.alerta tipo="exito">{{ session('estado') }}</x-portal.alerta>
            @endif

            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-5xl px-4 pb-8 text-xs text-slate-500">
            Universidad Nacional Santiago Antúnez de Mayolo · Huaraz
        </footer>
    </body>
</html>
