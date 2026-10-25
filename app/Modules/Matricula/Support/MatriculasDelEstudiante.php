<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Models\Matricula;
use Illuminate\Support\Collection;

final readonly class MatriculasDelEstudiante
{
    /**
     * @param  Collection<int, Matricula>  $matriculas  newest first
     * @param  array<int, FichaDeGrupo>  $grupos  by group id
     * @param  array<int, string>  $docentes  teacher name by group id
     */
    public function __construct(
        public Collection $matriculas,
        public array $grupos,
        public array $docentes,
    ) {}
}
