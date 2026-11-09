@props(['title' => null, 'indexable' => true, 'marco' => true, 'tema' => null, 'modo' => null])
<!DOCTYPE html>
<html lang="es"@if ($modo === 'oscuro') class="oscuro"@endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if ($modo === 'sistema')
            {{-- Before the styles load, so a dark device never sees the light portal first; wire:navigate copies the server's <html> attributes, so it reapplies on every swap. --}}
            <script data-tema-del-sistema>
                (() => {
                    const oscuro = matchMedia('(prefers-color-scheme: dark)');
                    const aplicar = () => document.documentElement.classList.toggle('oscuro', oscuro.matches);
                    aplicar();
                    oscuro.addEventListener('change', aplicar);
                    document.addEventListener('livewire:navigating', (evento) => evento.detail.onSwap(aplicar));
                })();
            </script>
        @endif
        @unless ($indexable)
            <meta name="robots" content="noindex, nofollow">
        @endunless
        <title>{{ $title ? "{$title} · " : '' }}CILN UNASAM</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('images/ciln/apple-touch-icon.png') }}">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class(['min-h-screen bg-fondo font-sans text-tinta antialiased', $tema])>
        <a href="#contenido" class="sr-only rounded-pildora bg-tinta px-4 py-2 text-invertido focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50">
            Saltar al contenido
        </a>

        @if ($marco)
            <div class="min-h-screen p-2 sm:p-4">
                <div {{ $attributes->class('min-h-[calc(100vh-1rem)] rounded-tarjeta bg-lienzo p-2 sm:min-h-[calc(100vh-2rem)] sm:rounded-marco sm:p-3.5') }}>
                    {{ $slot }}
                </div>
            </div>
        @else
            <div {{ $attributes->class('min-h-screen') }}>{{ $slot }}</div>
        @endif
    </body>
</html>
