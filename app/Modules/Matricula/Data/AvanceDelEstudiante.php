<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Pagos\Data\ResumenDePagos;

final readonly class AvanceDelEstudiante
{
    /**
     * @param  array<int, list<ClaseDeLaSemana>>  $horario  classes of the active groups by weekday, 1 (Monday) to 7
     * @param  list<AvanceEnIdioma>  $idiomas  the latest enrollment per language, active ones first
     * @param  ?string  $matriculaActual  route key of the current active enrollment, for its documents
     */
    public function __construct(
        public PasoDelEstudiante $paso,
        public ?FichaDeGrupo $grupoActual,
        public array $horario,
        public array $idiomas,
        public ResumenDePagos $pagos,
        public int $pagosParaMatricula,
        public ?string $matriculaActual,
    ) {}
}
