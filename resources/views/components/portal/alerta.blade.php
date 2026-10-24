@props(['tipo' => 'info'])
<div role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->class([
        'rounded-md border px-4 py-3 text-sm',
        'border-green-300 bg-green-50 text-green-900' => $tipo === 'exito',
        'border-red-300 bg-red-50 text-red-900' => $tipo === 'error',
        'border-amber-300 bg-amber-50 text-amber-900' => $tipo === 'aviso',
        'border-ciln-100 bg-ciln-50 text-ciln-900' => $tipo === 'info',
    ]) }}>
    {{ $slot }}
</div>
