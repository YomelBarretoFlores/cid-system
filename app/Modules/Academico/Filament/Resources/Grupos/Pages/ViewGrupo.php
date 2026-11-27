<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Docencia\Contracts\AsignacionDeDocente;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Data\DocenteAsignado;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewGrupo extends ViewRecord
{
    #[\Override]
    protected static string $resource = GrupoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Coordination prints the grades registered so far; the acta itself belongs to Certificacion.
            Action::make('descargarPreActa')
                ->label('Pre acta (PDF)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false)
                ->url(fn (): string => route('panel.grupos.preacta.pdf', $this->getRecord()->getKey()))
                ->openUrlInNewTab(),
            $this->asignarDocente(),
            EditAction::make(),
        ];
    }

    private function asignarDocente(): Action
    {
        return Action::make('asignarDocente')
            ->label(fn (): string => $this->docente() instanceof DocenteAsignado ? 'Cambiar docente' : 'Asignar docente')
            ->icon(Heroicon::OutlinedAcademicCap)
            ->color('gray')
            ->visible(fn (): bool => auth()->user()?->can(PermisoDocencia::Gestionar->value) === true && ! $this->grupo()->estado->esTerminal())
            ->modalDescription('Solo aparecen los docentes activos habilitados para el idioma y el nivel del grupo. El horario del grupo debe estar dentro de su disponibilidad.')
            ->schema([
                Select::make('docente_id')
                    ->label('Docente')
                    ->options(fn (): array => GrupoForm::docentesHabilitados($this->grupo()->ciclo_id))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Action $action, array $data): void {
                AccionDeNegocio::ejecutar(
                    $action,
                    fn (User $usuario) => app(AsignacionDeDocente::class)($this->grupo()->id, (int) $data['docente_id'], $usuario),
                    'Docente asignado',
                );
                $this->refreshFormData(['docente_actual']);
            });
    }

    private function docente(): ?DocenteAsignado
    {
        return app(DocenteDelGrupo::class)($this->grupo()->id);
    }

    private function grupo(): Grupo
    {
        $grupo = $this->getRecord();

        return $grupo instanceof Grupo ? $grupo : abort(404);
    }
}
