@props(['titulo', 'columnas', 'alineadas' => []])
<section {{ $attributes->class('flex min-w-0 flex-col gap-2 rounded-tarjeta bg-tarjeta px-7 py-6') }}>
    <h2 class="mb-1 text-xl font-semibold">{{ $titulo }}</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-[14.5px]">
            <thead>
                <tr class="text-[13px] text-apagado">
                    @foreach ($columnas as $indice => $columna)
                        <th scope="col" @class(['pb-2 font-normal', 'pr-3' => ! $loop->last, 'text-right' => in_array($indice, $alineadas, true)])>{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
</section>
