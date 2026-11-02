<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CiclosDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use Illuminate\Database\Eloquent\Builder;

// Grade sheets keep the language by name, so the lookup is by name too.
final readonly class ListarCiclosDelNivel implements CiclosDelNivel
{
    public function __invoke(string $idioma, NivelCurricular $nivel): array
    {
        return array_values(Ciclo::query()
            ->whereHas('idioma', fn (Builder $idiomas): Builder => $idiomas->where('nombre', $idioma))
            ->where('nivel', $nivel)
            ->where('activo', true)
            ->orderBy('numero')
            ->pluck('numero')
            ->map(intval(...))
            ->all());
    }
}
