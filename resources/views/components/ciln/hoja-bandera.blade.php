@props(['idioma' => null, 'size' => 28, 'forma' => 'hoja', 'rot' => 0])
@php
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
    $franjas = match (mb_strtolower((string) $idioma)) {
        'francés' => ['vertical', ['#0055A4', '#EEF1F5', '#EF4135']],
        'italiano' => ['vertical', ['#009246', '#EEF1F5', '#CE2B37']],
        'alemán' => ['horizontal', ['#111111', '#DD0000', '#FFCE00']],
        'inglés' => ['horizontal', ['#B22234', '#FFFFFF', '#B22234', '#FFFFFF', '#B22234', '#FFFFFF', '#B22234']],
        default => null,
    };
    $bandera = match (mb_strtolower((string) $idioma)) {
        'portugués' => 'brasil',
        'quechua' => 'wiphala',
        default => $franjas === null ? 'neutra' : 'franjas',
    };
    $wiphala = ['#E30613', '#F39200', '#FFED00', '#FFFFFF', '#009640', '#0069B4', '#6F2C91'];
@endphp
<span aria-hidden="true"
    {{ $attributes->class('relative inline-block shrink-0 overflow-hidden align-middle') }}
    style="width: {{ $ancho }}px; height: {{ $alto }}px; border-radius: {{ $radio }}; transform: rotate({{ (int) $rot }}deg)">
    <svg class="absolute inset-0 size-full" viewBox="0 0 {{ $ancho }} {{ $alto }}" preserveAspectRatio="none">
        @if ($bandera === 'franjas')
            @foreach ($franjas[1] as $indice => $color)
                @if ($franjas[0] === 'vertical')
                    <rect x="{{ $ancho / count($franjas[1]) * $indice }}" y="0" width="{{ $ancho / count($franjas[1]) + 0.5 }}" height="{{ $alto }}" fill="{{ $color }}" />
                @else
                    <rect x="0" y="{{ $alto / count($franjas[1]) * $indice }}" width="{{ $ancho }}" height="{{ $alto / count($franjas[1]) + 0.5 }}" fill="{{ $color }}" />
                @endif
            @endforeach
            @if (mb_strtolower((string) $idioma) === 'inglés')
                <rect x="0" y="0" width="{{ $ancho * 0.46 }}" height="{{ $alto * 0.54 }}" fill="#3C3B6E" />
                @foreach ([[0.1, 0.16], [0.23, 0.16], [0.36, 0.16], [0.1, 0.38], [0.23, 0.38], [0.36, 0.38]] as [$x, $y])
                    <circle cx="{{ $ancho * $x }}" cy="{{ $alto * $y }}" r="{{ max(0.6, $ancho / 56) }}" fill="#FFFFFF" />
                @endforeach
            @endif
        @elseif ($bandera === 'brasil')
            <rect width="{{ $ancho }}" height="{{ $alto }}" fill="#009C3B" />
            <polygon points="{{ $ancho / 2 }},{{ $alto * 0.14 }} {{ $ancho * 0.88 }},{{ $alto / 2 }} {{ $ancho / 2 }},{{ $alto * 0.86 }} {{ $ancho * 0.12 }},{{ $alto / 2 }}" fill="#FFDF00" />
            <circle cx="{{ $ancho / 2 }}" cy="{{ $alto / 2 }}" r="{{ $alto * 0.21 }}" fill="#002776" />
        @elseif ($bandera === 'wiphala')
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
