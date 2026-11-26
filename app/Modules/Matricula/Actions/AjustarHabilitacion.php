<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Data\RecordDelIdioma;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Places a student in any cycle of a language (continuing students of the previous system, corrections); going back to a passed cycle must be confirmed.
final readonly class AjustarHabilitacion
{
    public function __construct(
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private RecordDeNotas $recordDeNotas,
        private CiclosDeGrupos $ciclosDeGrupos,
    ) {}

    public function __invoke(int $personaId, int $idiomaId, int $cicloId, string $motivo, User $usuario, bool $confirmarDescenso = false): Habilitacion
    {
        throw_unless($usuario->can(PermisoMatricula::Gestionar->value), ReglaDeNegocioIncumplida::class, 'No tienes permiso para ajustar el ciclo habilitado.');

        $motivo = trim($motivo);
        throw_unless(mb_strlen($motivo) >= 5 && mb_strlen($motivo) <= 500, ReglaDeNegocioIncumplida::class, 'Indica el motivo del ajuste, entre 5 y 500 caracteres.');

        $idioma = collect(($this->catalogoDeIdiomas)())->first(fn (IdiomaDelCatalogo $idioma): bool => $idioma->id === $idiomaId)
            ?? throw new ReglaDeNegocioIncumplida('El idioma no existe.');
        throw_unless(isset($idioma->ciclos[$cicloId]), ReglaDeNegocioIncumplida::class, "El ciclo elegido no pertenece a {$idioma->nombre}.");

        // Same person lock as enrollment and placement, so the enabled cycle never changes under an enrollment in progress.
        return Transaccion::ejecutar(function () use ($personaId, $idioma, $cicloId, $motivo, $usuario, $confirmarDescenso): Habilitacion {
            MovimientoDeMatricula::bloquearPersona($personaId);

            if (! $confirmarDescenso) {
                $this->asegurarQueNoBaja($personaId, $idioma, $cicloId);
            }

            return Habilitacion::query()->updateOrCreate(
                ['persona_id' => $personaId, 'idioma_id' => $idioma->id],
                ['ciclo_id' => $cicloId, 'origen' => OrigenHabilitacion::AjusteAdministrativo, 'motivo' => $motivo, 'actualizado_por' => $usuario->id],
            );
        });
    }

    private function asegurarQueNoBaja(int $personaId, IdiomaDelCatalogo $idioma, int $cicloId): void
    {
        $orden = array_keys($idioma->ciclos);
        $aprobado = $this->ultimoCicloAprobado($personaId, $idioma);

        if ($aprobado === null || array_search($cicloId, $orden, true) > array_search($aprobado, $orden, true)) {
            return;
        }

        $ciclo = lcfirst($idioma->ciclos[$aprobado]);

        throw new ReglaDeNegocioIncumplida("El estudiante ya aprobó el {$ciclo} de {$idioma->nombre}. Para habilitarle ese ciclo o uno anterior hay que confirmarlo.");
    }

    private function ultimoCicloAprobado(int $personaId, IdiomaDelCatalogo $idioma): ?int
    {
        $record = collect(($this->recordDeNotas)($personaId))->first(fn (RecordDelIdioma $record): bool => $record->idioma === $idioma->nombre);

        if (! $record instanceof RecordDelIdioma) {
            return null;
        }

        $matriculas = collect($record->ciclos)
            ->filter(fn (CicloDelRecord $ciclo): bool => $ciclo->resultado->estadoFinal === EstadoFinal::Aprobado && $ciclo->resultado->matriculaId !== null)
            ->map(fn (CicloDelRecord $ciclo): int => (int) $ciclo->resultado->matriculaId);
        $grupos = array_values(array_unique(array_map(intval(...), Matricula::query()->whereKey($matriculas->all())->pluck('grupo_id')->all())));
        $orden = array_keys($idioma->ciclos);

        return collect(($this->ciclosDeGrupos)($grupos))
            ->map(fn (GrupoData $grupo): int => $grupo->cicloId)
            ->filter(fn (int $ciclo): bool => in_array($ciclo, $orden, true))
            ->sortBy(fn (int $ciclo): int|false => array_search($ciclo, $orden, true))
            ->last();
    }
}
