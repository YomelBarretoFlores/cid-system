<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\ActaPrevia;

final readonly class PrepararActaDelGrupo
{
    public function __construct(private ActaPrevia $actaPrevia) {}

    public function __invoke(int $grupoId): ActaDelGrupo
    {
        $acta = Acta::query()->with('notas')->where('grupo_id', $grupoId)->first();

        return $acta === null ? $this->actaPrevia->armar($grupoId) : ActaDelGrupo::desde($acta);
    }
}
