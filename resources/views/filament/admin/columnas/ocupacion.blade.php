@php
    $cupo = (int) $getRecord()->cupo_maximo;
    $matriculados = (int) $getState();
    $porcentaje = $cupo > 0 ? min(100, (int) round($matriculados / $cupo * 100)) : 0;
@endphp
<div class="flex w-full flex-col gap-1.5">
    <div class="flex justify-between gap-2 text-[13px] text-secundario">
        <span>{{ $matriculados }} de {{ $cupo }} matriculados</span>
        <span>{{ $porcentaje }} %</span>
    </div>
    <div class="h-1.5 overflow-hidden rounded-full bg-piedra-100" role="presentation">
        <div @class(['h-full rounded-full', 'bg-oro-400' => $porcentaje < 100, 'bg-positivo' => $porcentaje >= 100]) style="width: {{ $porcentaje }}%"></div>
    </div>
</div>
