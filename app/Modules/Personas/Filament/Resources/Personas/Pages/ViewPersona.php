<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Actions\CrearCuentaDelPortal;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewPersona extends ViewRecord
{
    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crearCuentaDelPortal')
                ->label('Crear cuenta del portal')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (Persona $record): bool => $record->user_id === null && auth()->user()?->can(PermisoPersona::Gestionar->value) === true)
                ->requiresConfirmation()
                ->modalDescription('Verifica antes el documento de identidad de la persona. Se creará su cuenta con el correo personal registrado y se le enviará un enlace para crear su contraseña.')
                ->action(fn (Action $action, Persona $record) => AccionDeNegocio::ejecutar(
                    $action,
                    fn (User $usuario) => app(CrearCuentaDelPortal::class)($record->id, $usuario),
                    'Cuenta creada y enlace enviado',
                )),
            EditAction::make(),
        ];
    }
}
