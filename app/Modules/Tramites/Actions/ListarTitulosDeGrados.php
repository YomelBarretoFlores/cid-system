<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Contracts\TitulosDeGrados;
use App\Modules\Tramites\Models\GradoFut;

final readonly class ListarTitulosDeGrados implements TitulosDeGrados
{
    public function __invoke(): array
    {
        /** @var array<string, string> */
        return GradoFut::query()->orderBy('orden')->orderBy('nombre')->pluck('titulo_otorgado', 'nombre')->all();
    }
}
