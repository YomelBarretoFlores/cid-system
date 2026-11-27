<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CuentasDocentes;

use App\Modules\Docencia\Actions\CambiarContrasenaDelDocente;
use App\Modules\Docencia\Actions\DarAccesoAlDocente;
use App\Modules\Docencia\Actions\DarDeBajaCuentaDelDocente;
use App\Modules\Docencia\Actions\ReactivarCuentaDelDocente;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages\ListCuentasDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ContrasenaDeOficina;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\MotivoDeBaja;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TablaDeCuentasDocentes
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('persona_id')
                    ->label('Docente')
                    ->formatStateUsing(fn (int $state, ListCuentasDocentes $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('usuario')->label('Usuario')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): ?string => $livewire->cuentaDe($record)?->usuario)
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('estado_de_cuenta')->label('Cuenta')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): string => self::estado($record, $livewire)),
                TextColumn::make('correo')->label('Correo de la cuenta')->color('gray')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): ?string => $livewire->cuentaDe($record)?->correo)
                    ->placeholder('Sin correo'),
            ])
            ->recordActions([
                self::darAcceso(),
                self::cambiarContrasena(),
                self::darDeBaja(),
                self::reactivar(),
            ]);
    }

    private static function estado(Docente $docente, ListCuentasDocentes $livewire): string
    {
        $cuenta = $livewire->cuentaDe($docente);

        return match (true) {
            ! $cuenta instanceof CuentaDelPortal => 'Sin cuenta',
            $cuenta->desactivada => 'Dada de baja',
            default => 'Activa',
        };
    }

    /**
     * @return list<TextInput>
     */
    private static function camposDeContrasena(): array
    {
        return [
            TextInput::make('contrasena')->label('Contraseña')->password()->revealable()->required()
                ->rules(ContrasenaDeOficina::reglas())
                ->helperText('Al menos 8 caracteres, con letras y números. Entrégala al docente por escrito.'),
            TextInput::make('contrasena_confirmation')->label('Repite la contraseña')->password()->revealable()->required(),
        ];
    }

    private static function darAcceso(): Action
    {
        return Action::make('crearCuenta')
            ->label(fn (Docente $record, ListCuentasDocentes $livewire): string => $livewire->cuentaDe($record) instanceof CuentaDelPortal ? 'Asignar usuario' : 'Crear cuenta')
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(fn (Docente $record, ListCuentasDocentes $livewire): bool => $livewire->cuentaDe($record)?->usuario === null)
            ->modalDescription('Verifica antes el documento de identidad del docente. Su usuario será su número de documento.')
            ->schema(self::camposDeContrasena())
            ->action(function (Action $action, Docente $record, ListCuentasDocentes $livewire, array $data): void {
                try {
                    $cuenta = app(DarAccesoAlDocente::class)($record->id, (string) $data['contrasena'], AccionDeNegocio::usuario());
                } catch (ReglaDeNegocioIncumplida $error) {
                    AccionDeNegocio::notificarError($error);
                    $action->halt();

                    return;
                }

                $livewire->olvidarCuentas();
                Notification::make()->success()->title("Cuenta creada. Usuario: {$cuenta->usuario}")->send();
            });
    }

    private static function cambiarContrasena(): Action
    {
        return Action::make('cambiarContrasena')
            ->label('Cambiar contraseña')
            ->icon(Heroicon::OutlinedKey)
            ->color('gray')
            ->visible(fn (Docente $record, ListCuentasDocentes $livewire): bool => $livewire->cuentaDe($record) instanceof CuentaDelPortal)
            ->modalDescription('Verifica antes el documento de identidad del docente. Se cerrarán las sesiones que tenga abiertas.')
            ->schema(self::camposDeContrasena())
            ->action(fn (Action $action, Docente $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn ($usuario) => app(CambiarContrasenaDelDocente::class)($record->id, (string) $data['contrasena'], $usuario),
                'Contraseña cambiada',
            ));
    }

    private static function darDeBaja(): Action
    {
        return Action::make('darDeBaja')
            ->label('Dar de baja')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (Docente $record, ListCuentasDocentes $livewire): bool => $livewire->cuentaDe($record)?->desactivada === false)
            ->modalDescription('El docente ya no podrá ingresar. No se borra nada y se puede reactivar después.')
            ->schema([MotivoDeBaja::campo()])
            ->action(function (Action $action, Docente $record, ListCuentasDocentes $livewire, array $data): void {
                AccionDeNegocio::ejecutar(
                    $action,
                    fn ($usuario) => app(DarDeBajaCuentaDelDocente::class)($record->id, (string) $data['motivo'], $usuario),
                    'Cuenta dada de baja',
                );
                $livewire->olvidarCuentas();
            });
    }

    private static function reactivar(): Action
    {
        return Action::make('reactivar')
            ->label('Reactivar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (Docente $record, ListCuentasDocentes $livewire): bool => $livewire->cuentaDe($record)?->desactivada === true)
            ->requiresConfirmation()
            ->modalDescription('El docente podrá volver a ingresar con su usuario y su contraseña.')
            ->action(function (Action $action, Docente $record, ListCuentasDocentes $livewire): void {
                AccionDeNegocio::ejecutar(
                    $action,
                    fn ($usuario) => app(ReactivarCuentaDelDocente::class)($record->id, $usuario),
                    'Cuenta reactivada',
                );
                $livewire->olvidarCuentas();
            });
    }
}
