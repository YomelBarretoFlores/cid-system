<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Contracts\CiclosDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Models\Acta;

// Actas keep the language by name; one of their groups lets Academico find the language even after it was renamed.
final readonly class CiclosDelNivelDelActa
{
    public function __construct(private CiclosDelNivel $ciclosDelNivel) {}

    /**
     * @return list<int>
     */
    public function de(string $idioma, NivelCurricular $nivel): array
    {
        $grupoId = Acta::query()->where('idioma', $idioma)->orderByDesc('id')->value('grupo_id');

        return ($this->ciclosDelNivel)($idioma, $nivel, $grupoId === null ? null : (int) $grupoId);
    }
}
