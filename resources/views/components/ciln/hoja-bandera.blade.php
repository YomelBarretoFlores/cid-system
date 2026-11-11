@use('App\Shared\Ui\BanderaDeIdioma')
@use('App\Shared\Ui\BanderasDeIdiomas')
@props(['idioma' => null, 'bandera' => null, 'size' => 28, 'forma' => 'circulo', 'rot' => 0])
@php
    $bandera = match (true) {
        $bandera instanceof BanderaDeIdioma => $bandera,
        filled($bandera) => BanderaDeIdioma::tryFrom((string) $bandera) ?? BanderaDeIdioma::Neutra,
        filled($idioma) => app(BanderasDeIdiomas::class)->para((string) $idioma),
        default => BanderaDeIdioma::Neutra,
    };
    // A circle fills its whole box, so it is drawn smaller to weigh the same as the leaf next to a text.
    $ancho = $forma === 'circulo' ? (int) round((int) $size * 0.8) : (int) $size;
    $alto = match ($forma) {
        'circulo' => $ancho,
        'pill', 'rect' => (int) round($ancho * 0.66),
        default => (int) round($ancho * 0.62),
    };
    $radio = match ($forma) {
        'circulo' => '50%',
        'pill' => round($alto * 0.28).'px',
        'rect' => '2px',
        default => '100% 0 100% 0 / 100% 0 100% 0',
    };
@endphp
<span aria-hidden="true"
    {{ $attributes->class('relative inline-block shrink-0 overflow-hidden align-middle') }}
    style="width: {{ $ancho }}px; height: {{ $alto }}px; border-radius: {{ $radio }}; transform: rotate({{ (int) $rot }}deg)">
    <x-ciln.dibujo-bandera :bandera="$bandera" :ancho="$ancho" :alto="$alto" class="absolute inset-0 size-full" />
    <span class="pointer-events-none absolute inset-0 rounded-[inherit] shadow-[inset_0_0_0_1px_rgb(15_30_51/0.2)]"></span>
    @if ($forma === 'hoja' && $ancho >= 22)
        <span class="absolute inset-x-[8%] top-1/2 -mt-[0.75px] h-[1.5px] -rotate-[24deg] bg-white/85"></span>
    @endif
</span>
