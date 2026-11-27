<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\AsignacionDeDocente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

// As in the previous system's «Programación de horario», one screen creates the group, its schedule and its teacher.
final class CreateGrupo extends CreateRecord
{
    #[\Override]
    protected static string $resource = GrupoResource::class;

    #[\Override]
    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = AccionDeNegocio::usuario();

        try {
            return Transaccion::ejecutar(fn (): Grupo => $this->crear($data, $actor), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function crear(array $data, User $actor): Grupo
    {
        $grupo = app(GuardarGrupo::class)(GrupoForm::datos($data));

        foreach (GrupoForm::horarios($data) as $horario) {
            app(GuardarHorario::class)($grupo->id, $horario['dia'], $horario['desde'], $horario['hasta']);
        }

        if (is_numeric($data['docente_id'] ?? null)) {
            app(AsignacionDeDocente::class)($grupo->id, (int) $data['docente_id'], $actor);
        }

        return $grupo;
    }

    protected function getRedirectUrl(): string
    {
        return GrupoResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
