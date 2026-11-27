<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Docencia\Contracts\DocentesHabilitados;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Data\FichaDeEstudiante;

final readonly class ListarDocentesHabilitados implements DocentesHabilitados
{
    public function __construct(private FichasDePersonas $fichasDePersonas) {}

    public function __invoke(int $idiomaId, NivelCurricular $nivel): array
    {
        $docentes = Docente::query()
            ->where('estado', EstadoDocente::Activo)
            ->whereHas('idiomas', fn ($idioma) => $idioma->where('idioma_id', $idiomaId)->whereIn('nivel_maximo', $this->nivelesQueCubren($nivel)))
            ->get(['id', 'codigo', 'persona_id']);
        $fichas = ($this->fichasDePersonas)(array_values($docentes->pluck('persona_id')->unique()->all()));

        return $docentes
            ->mapWithKeys(fn (Docente $docente): array => [$docente->id => $this->etiqueta($docente, $fichas[$docente->persona_id] ?? null)])
            ->sort()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function nivelesQueCubren(NivelCurricular $nivel): array
    {
        return array_values(array_map(
            fn (NivelCurricular $otro): string => $otro->value,
            array_filter(NivelCurricular::cases(), fn (NivelCurricular $otro): bool => $otro->orden() >= $nivel->orden()),
        ));
    }

    private function etiqueta(Docente $docente, ?FichaDeEstudiante $ficha): string
    {
        return $ficha instanceof FichaDeEstudiante ? "{$ficha->nombreCompleto} · {$docente->codigo}" : $docente->codigo;
    }
}
