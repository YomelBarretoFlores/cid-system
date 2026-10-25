@props(['tipo' => 'info'])
<div role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->class([
        'rounded-control px-[14px] py-3 text-[14.5px]',
        'bg-verde-fondo text-verde-texto' => $tipo === 'exito',
        'bg-rojo-fondo text-rojo-texto' => $tipo === 'error',
        'bg-ambar-fondo text-ambar-texto' => $tipo === 'aviso',
        'bg-azul-fondo text-azul-texto' => $tipo === 'info',
    ]) }}>
    {{ $slot }}
</div>
