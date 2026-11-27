<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Actions\CrearCuentaDelPortal;
use App\Modules\Personas\Actions\DarDeBajaCuentaDelPortal;
use App\Modules\Personas\Actions\ReactivarCuentaDeLaPersona;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\MotivoDeBaja;
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
            Action::make('darDeBajaLaCuenta')
                ->label('Dar de baja la cuenta')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->visible(fn (Persona $record): bool => $this->puedeGestionar() && $this->cuentaActiva($record) === true)
                ->modalDescription('La persona ya no podrá ingresar al portal. No se borra nada y se puede reactivar después.')
                ->schema([MotivoDeBaja::campo()])
                ->action(fn (Action $action, Persona $record, array $data) => AccionDeNegocio::ejecutar(
                    $action,
                    fn (User $usuario) => app(DarDeBajaCuentaDelPortal::class)($record->id, (string) $data['motivo'], $usuario),
                    'Cuenta dada de baja',
                )),
            Action::make('reactivarLaCuenta')
                ->label('Reactivar la cuenta')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->visible(fn (Persona $record): bool => $this->puedeGestionar() && $this->cuentaActiva($record) === false)
                ->requiresConfirmation()
                ->modalDescription('La persona podrá volver a ingresar al portal con su cuenta.')
                ->action(fn (Action $action, Persona $record) => AccionDeNegocio::ejecutar(
                    $action,
                    fn (User $usuario) => app(ReactivarCuentaDeLaPersona::class)($record->id, $usuario),
                    'Cuenta reactivada',
                )),
            EditAction::make(),
        ];
    }

    private function puedeGestionar(): bool
    {
        return auth()->user()?->can(PermisoPersona::Gestionar->value) === true;
    }

    // Null when the record has no portal account.
    private function cuentaActiva(Persona $persona): ?bool
    {
        $cuenta = $persona->user_id === null ? null : User::query()->find($persona->user_id, ['id', 'desactivado_en']);

        if ($cuenta === null) {
            return null;
        }

        return $cuenta->desactivado_en === null;
    }
}
