<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Matricula\Contracts\ProgramasDeLaPersona;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\Eloquent\Builder;

// Who may request a type: the programs it is open to and the trámite that must be finished first.
final readonly class AccesoAlTipo
{
    public function __construct(private ProgramasDeLaPersona $programasDeLaPersona, private CatalogoDeProgramas $catalogo) {}

    /**
     * @return array<int, string> the student's programs the type is open to
     */
    public function programasDisponibles(TipoTramite $tipo, int $personaId): array
    {
        $propios = ($this->programasDeLaPersona)($personaId);
        $permitidos = $this->permitidos($tipo);

        return $permitidos === [] ? $propios : array_intersect_key($propios, array_flip($permitidos));
    }

    public function puedeSolicitar(TipoTramite $tipo, int $personaId): bool
    {
        return $this->permitidos($tipo) === [] || $this->programasDisponibles($tipo, $personaId) !== [];
    }

    public function asegurarPrograma(TipoTramite $tipo, int $personaId): void
    {
        if ($this->puedeSolicitar($tipo, $personaId)) {
            return;
        }

        $nombres = collect(($this->catalogo)())
            ->filter(fn (ProgramaDelCatalogo $programa): bool => in_array($programa->id, $this->permitidos($tipo), true))
            ->map(fn (ProgramaDelCatalogo $programa): string => $programa->nombre)
            ->implode(', ');

        throw new ReglaDeNegocioIncumplida(count($this->permitidos($tipo)) === 1
            ? "Este trámite solo está disponible para el programa {$nombres}."
            : "Este trámite solo está disponible para los programas {$nombres}.");
    }

    /**
     * @param  array<string, int|string>  $guardados
     */
    public function asegurarPrevio(TipoTramite $tipo, int $personaId, array $guardados): void
    {
        $previo = $tipo->tipoPrevio;

        if (! $previo instanceof TipoTramite) {
            return;
        }

        // A previous trámite without a level (requested before levels existed) covers every level.
        $completado = Tramite::query()
            ->where(['persona_id' => $personaId, 'tipo_tramite_id' => $previo->id, 'estado' => EstadoTramite::Finalizado])
            ->when(isset($guardados['idioma']), fn (Builder $query): Builder => $query->where('datos->idioma', $guardados['idioma']))
            ->when(isset($guardados['nivel']), fn (Builder $query): Builder => $query->where(
                fn (Builder $nivel): Builder => $nivel->whereNull('datos->nivel')->orWhere('datos->nivel', $guardados['nivel']),
            ))
            ->exists();

        throw_unless($completado, ReglaDeNegocioIncumplida::class, "Primero debes completar el trámite «{$previo->nombre}»{$this->asunto($tipo)}.");
    }

    private function asunto(TipoTramite $tipo): string
    {
        $claves = array_column($tipo->campos, 'clave');

        return match (true) {
            in_array('idioma', $claves, true) && in_array('nivel', $claves, true) => ' para este idioma y nivel',
            in_array('idioma', $claves, true) => ' para este idioma',
            default => '',
        };
    }

    /**
     * @return list<int>
     */
    private function permitidos(TipoTramite $tipo): array
    {
        return array_values(array_map(intval(...), $tipo->programas->pluck('programa_id')->all()));
    }
}
