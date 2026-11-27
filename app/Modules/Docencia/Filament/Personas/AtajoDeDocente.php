<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Personas;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Models\Docente;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

// On the person page: open the teacher record, or start registering the person as a teacher.
final class AtajoDeDocente
{
    public static function para(int $personaId): ?Action
    {
        $usuario = auth()->user();
        $docente = Docente::query()->where('persona_id', $personaId)->first(['id', 'ulid']);

        if ($docente !== null) {
            return $usuario?->can(PermisoDocencia::Ver->value) === true
                ? Action::make('verDocente')->label('Ver ficha de docente')->icon(Heroicon::OutlinedAcademicCap)->color('gray')
                    ->url(DocenteResource::getUrl('view', ['record' => $docente]))
                : null;
        }

        return $usuario?->can(PermisoDocencia::Gestionar->value) === true
            ? Action::make('registrarComoDocente')->label('Registrar como docente')->icon(Heroicon::OutlinedAcademicCap)->color('gray')
                ->url(DocenteResource::getUrl('create', ['persona' => $personaId]))
            : null;
    }
}
