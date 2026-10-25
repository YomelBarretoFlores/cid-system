@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? "{$title} · " : '' }}CILN UNASAM</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('images/ciln/apple-touch-icon.png') }}">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-fondo font-sans text-tinta antialiased">
        <a href="#contenido" class="sr-only rounded-pildora bg-tinta px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50">
            Saltar al contenido
        </a>

        <div class="min-h-screen p-2 sm:p-4">
            <div {{ $attributes->class('min-h-[calc(100vh-1rem)] rounded-tarjeta bg-lienzo p-2 sm:min-h-[calc(100vh-2rem)] sm:rounded-marco sm:p-3.5') }}>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
