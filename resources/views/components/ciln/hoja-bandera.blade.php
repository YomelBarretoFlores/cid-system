@use('App\Shared\Ui\BanderaDeIdioma')
@use('App\Shared\Ui\BanderasDeIdiomas')
@props(['idioma' => null, 'bandera' => null, 'size' => 28, 'forma' => 'hoja', 'rot' => 0])
@php
    $bandera = match (true) {
        $bandera instanceof BanderaDeIdioma => $bandera,
        filled($bandera) => BanderaDeIdioma::from((string) $bandera),
        filled($idioma) => app(BanderasDeIdiomas::class)->para((string) $idioma),
        default => BanderaDeIdioma::Neutra,
    };
    $ancho = (int) $size;
    $alto = match ($forma) {
        'circulo' => $ancho,
        'pill' => (int) round($ancho * 0.66),
        default => (int) round($ancho * 0.62),
    };
    $radio = match ($forma) {
        'circulo' => '50%',
        'pill' => round($alto * 0.28).'px',
        default => '100% 0 100% 0 / 100% 0 100% 0',
    };
    $franjas = match ($bandera) {
        BanderaDeIdioma::Francia => ['vertical', ['#0055A4', '#EEF1F5', '#EF4135']],
        BanderaDeIdioma::Italia => ['vertical', ['#009246', '#EEF1F5', '#CE2B37']],
        BanderaDeIdioma::Alemania => ['horizontal', ['#111111', '#DD0000', '#FFCE00']],
        BanderaDeIdioma::Rusia => ['horizontal', ['#FFFFFF', '#0039A6', '#D52B1E']],
        BanderaDeIdioma::Espana => ['horizontal', ['#AA151B', '#F1BF00', '#F1BF00', '#AA151B']],
        BanderaDeIdioma::EstadosUnidos => ['horizontal', ['#B22234', '#FFFFFF', '#B22234', '#FFFFFF', '#B22234', '#FFFFFF', '#B22234']],
        default => null,
    };
    $wiphala = ['#E30613', '#F39200', '#FFED00', '#FFFFFF', '#009640', '#0069B4', '#6F2C91'];
    $estrella = collect(range(0, 9))->map(function (int $punta) use ($ancho, $alto): string {
        $radioPunta = $punta % 2 === 0 ? $alto * 0.2 : $alto * 0.08;
        $angulo = deg2rad(-90 + $punta * 36);

        return round($ancho * 0.24 + $radioPunta * cos($angulo), 2).','.round($alto * 0.32 + $radioPunta * sin($angulo), 2);
    })->implode(' ');
@endphp
<span aria-hidden="true"
    {{ $attributes->class('relative inline-block shrink-0 overflow-hidden align-middle') }}
    style="width: {{ $ancho }}px; height: {{ $alto }}px; border-radius: {{ $radio }}; transform: rotate({{ (int) $rot }}deg)">
    <svg class="absolute inset-0 size-full" viewBox="0 0 {{ $ancho }} {{ $alto }}" preserveAspectRatio="none">
        @if ($franjas !== null)
            @foreach ($franjas[1] as $indice => $color)
                @if ($franjas[0] === 'vertical')
                    <rect x="{{ $ancho / count($franjas[1]) * $indice }}" y="0" width="{{ $ancho / count($franjas[1]) + 0.5 }}" height="{{ $alto }}" fill="{{ $color }}" />
                @else
                    <rect x="0" y="{{ $alto / count($franjas[1]) * $indice }}" width="{{ $ancho }}" height="{{ $alto / count($franjas[1]) + 0.5 }}" fill="{{ $color }}" />
                @endif
            @endforeach
            @if ($bandera === BanderaDeIdioma::EstadosUnidos)
                <rect x="0" y="0" width="{{ $ancho * 0.46 }}" height="{{ $alto * 0.54 }}" fill="#3C3B6E" />
                @foreach ([[0.1, 0.16], [0.23, 0.16], [0.36, 0.16], [0.1, 0.38], [0.23, 0.38], [0.36, 0.38]] as [$x, $y])
                    <circle cx="{{ $ancho * $x }}" cy="{{ $alto * $y }}" r="{{ max(0.6, $ancho / 56) }}" fill="#FFFFFF" />
                @endforeach
            @endif
        @elseif ($bandera === BanderaDeIdioma::ReinoUnido)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#012169" />
            <path d="M0,0 L{{ $ancho }},{{ $alto }} M{{ $ancho }},0 L0,{{ $alto }}" stroke="#FFFFFF" stroke-width="{{ $alto * 0.2 }}" />
            <path d="M0,0 L{{ $ancho }},{{ $alto }} M{{ $ancho }},0 L0,{{ $alto }}" stroke="#C8102E" stroke-width="{{ $alto * 0.07 }}" />
            <rect x="{{ $ancho * 0.42 }}" y="0" width="{{ $ancho * 0.16 }}" height="{{ $alto }}" fill="#FFFFFF" />
            <rect x="0" y="{{ $alto * 0.36 }}" width="{{ $ancho }}" height="{{ $alto * 0.28 }}" fill="#FFFFFF" />
            <rect x="{{ $ancho * 0.45 }}" y="0" width="{{ $ancho * 0.1 }}" height="{{ $alto }}" fill="#C8102E" />
            <rect x="0" y="{{ $alto * 0.41 }}" width="{{ $ancho }}" height="{{ $alto * 0.18 }}" fill="#C8102E" />
        @elseif ($bandera === BanderaDeIdioma::Brasil)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#009C3B" />
            <polygon points="{{ $ancho / 2 }},{{ $alto * 0.14 }} {{ $ancho * 0.88 }},{{ $alto / 2 }} {{ $ancho / 2 }},{{ $alto * 0.86 }} {{ $ancho * 0.12 }},{{ $alto / 2 }}" fill="#FFDF00" />
            <circle cx="{{ $ancho / 2 }}" cy="{{ $alto / 2 }}" r="{{ $alto * 0.21 }}" fill="#002776" />
        @elseif ($bandera === BanderaDeIdioma::Portugal)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#FF0000" />
            <rect width="{{ $ancho * 0.4 }}" height="{{ $alto }}" fill="#006600" />
            <circle cx="{{ $ancho * 0.4 }}" cy="{{ $alto / 2 }}" r="{{ $alto * 0.22 }}" fill="#FFCC00" />
        @elseif ($bandera === BanderaDeIdioma::China)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#DE2910" />
            <polygon points="{{ $estrella }}" fill="#FFDE00" />
        @elseif ($bandera === BanderaDeIdioma::Japon)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#FFFFFF" />
            <circle cx="{{ $ancho / 2 }}" cy="{{ $alto / 2 }}" r="{{ $alto * 0.3 }}" fill="#BC002D" />
        @elseif ($bandera === BanderaDeIdioma::CoreaDelSur)
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#FFFFFF" />
            <path d="M{{ $ancho / 2 - $alto * 0.26 }},{{ $alto / 2 }} A{{ $alto * 0.26 }},{{ $alto * 0.26 }} 0 0 1 {{ $ancho / 2 + $alto * 0.26 }},{{ $alto / 2 }} Z" fill="#CD2E3A" />
            <path d="M{{ $ancho / 2 - $alto * 0.26 }},{{ $alto / 2 }} A{{ $alto * 0.26 }},{{ $alto * 0.26 }} 0 0 0 {{ $ancho / 2 + $alto * 0.26 }},{{ $alto / 2 }} Z" fill="#0047A0" />
        @elseif ($bandera === BanderaDeIdioma::Wiphala)
            @for ($celda = 0; $celda < 49; $celda++)
                <rect x="{{ $ancho / 7 * ($celda % 7) }}" y="{{ $alto / 7 * intdiv($celda, 7) }}" width="{{ $ancho / 7 + 0.5 }}" height="{{ $alto / 7 + 0.5 }}"
                    fill="{{ $wiphala[(($celda % 7) + (6 - intdiv($celda, 7))) % 7] }}" />
            @endfor
        @else
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="var(--color-acento)" />
        @endif
    </svg>
    <span class="pointer-events-none absolute inset-0 rounded-[inherit] shadow-[inset_0_0_0_1px_rgb(15_30_51/0.2)]"></span>
    @if ($forma === 'hoja' && $ancho >= 22)
        <span class="absolute inset-x-[8%] top-1/2 -mt-[0.75px] h-[1.5px] -rotate-[24deg] bg-white/85"></span>
    @endif
</span>
