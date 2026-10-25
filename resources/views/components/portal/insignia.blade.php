@props(['color' => 'gray'])
<span {{ $attributes->class([
    'inline-flex items-center rounded-pildora px-3 py-[3px] text-[13px] font-medium whitespace-nowrap',
    'bg-ambar-fondo text-ambar-texto' => $color === 'warning',
    'bg-verde-fondo text-verde-texto' => $color === 'success',
    'bg-rojo-fondo text-rojo-texto' => $color === 'danger',
    'bg-azul-fondo text-azul-texto' => $color === 'info',
    'bg-gris-fondo text-gris-texto' => $color === 'gray',
]) }}>{{ $slot }}</span>
