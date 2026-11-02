<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios;

use App\Shared\Auth\Actions\DesactivarUsuario;
use App\Shared\Auth\Actions\EnviarEnlaceDeContrasena;
use App\Shared\Auth\Actions\ReactivarUsuario;
use App\Shared\Auth\Actions\ReiniciarDobleFactor;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class AccionesDeUsuario
{
    /**
     * @return list<Action>
     */
    public static function todas(): array
    {
        return [self::desactivar(), self::reactivar(), self::reenviarEnlace(), self::reiniciarDobleFactor()];
    }

    public static function desactivar(): Action
    {
        return self::accion('desactivar')
            ->label('Desactivar')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => $record->desactivado_en === null)
            ->modalHeading('Desactivar cuenta')
            ->modalDescription('La persona no podrá ingresar al panel, al portal ni a la API, y se cerrarán sus sesiones.')
            ->modalSubmitActionLabel('Desactivar')
            ->schema([
                Textarea::make('motivo')->label('Motivo')->required()->maxLength(DesactivarUsuario::MAXIMO_DEL_MOTIVO)->rows(3),
            ])
            ->action(fn (Action $action, User $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(DesactivarUsuario::class)($record->id, is_string($data['motivo'] ?? null) ? $data['motivo'] : '', $usuario),
                'Cuenta desactivada',
            ));
    }

    public static function reactivar(): Action
    {
        return self::accion('reactivar')
            ->label('Reactivar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('success')
            ->visible(fn (User $record): bool => $record->desactivado_en !== null)
            ->requiresConfirmation()
            ->modalHeading('Reactivar cuenta')
            ->action(fn (Action $action, User $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(ReactivarUsuario::class)($record->id, $usuario),
                'Cuenta reactivada',
            ));
    }

    public static function reenviarEnlace(): Action
    {
        return self::accion('reenviarEnlace')
            ->label('Reenviar enlace para crear contraseña')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (User $record): bool => $record->desactivado_en === null)
            ->requiresConfirmation()
            ->modalDescription('Se enviará al correo de la cuenta un enlace para crear una nueva contraseña.')
            ->action(fn (Action $action, User $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(EnviarEnlaceDeContrasena::class)($record->id, $usuario),
                'Enlace enviado',
            ));
    }

    public static function reiniciarDobleFactor(): Action
    {
        return self::accion('reiniciarDobleFactor')
            ->label('Reiniciar doble factor')
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->color('gray')
            ->visible(fn (User $record): bool => $record->getRawOriginal('app_authentication_secret') !== null)
            ->requiresConfirmation()
            ->modalDescription('La persona deberá configurar de nuevo su aplicación autenticadora al ingresar. También se cerrarán sus sesiones.')
            ->action(fn (Action $action, User $record) => AccionDeNegocio::ejecutar(
                $action,
                function (User $usuario) use ($record): void {
                    GestoresDeUsuarios::autorizar($usuario);
                    app(ReiniciarDobleFactor::class)($record->email);
                },
                'Doble factor reiniciado',
            ));
    }

    private static function accion(string $nombre): Action
    {
        return Action::make($nombre)
            ->authorize('update')
            ->after(fn (User $record): User => $record->refresh());
    }
}
