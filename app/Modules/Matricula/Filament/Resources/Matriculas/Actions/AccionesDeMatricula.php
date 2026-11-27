<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Actions;

use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Matricula\Actions\MatricularEnVentanilla;
use App\Modules\Matricula\Actions\RetirarMatricula;
use App\Modules\Matricula\Actions\TrasladarMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Schemas\MatriculaEnVentanillaForm;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\FormularioDePersona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class AccionesDeMatricula
{
    public static function descargarFicha(): Action
    {
        return Action::make('descargarFicha')
            ->label('Ficha de matrícula')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->authorize('view')
            ->url(fn (Matricula $record): string => route('panel.matriculas.ficha', $record))
            ->openUrlInNewTab();
    }

    public static function matricularEnVentanilla(): Action
    {
        return Action::make('matricularEnVentanilla')
            ->label('Matricular en ventanilla')
            ->icon(Heroicon::OutlinedUserPlus)
            ->authorize(fn (): bool => auth()->user()?->can(PermisoMatricula::Gestionar->value) ?? false)
            ->modalDescription('Matricula a un estudiante en un grupo con su pago validado o, mientras la configuración de pagos lo permita, con un pago hecho antes del sistema.')
            ->modalSubmitActionLabel('Matricular')
            ->schema(MatriculaEnVentanillaForm::componentes())
            ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Matricula => Transaccion::ejecutar(fn (): Matricula => self::matricular($data, $usuario), reintentar: false),
                'Matrícula registrada',
            ));
    }

    // A student new to the system is registered in the same transaction, so a refused enrollment leaves no person behind.
    /**
     * @param  array<string, mixed>  $data
     */
    private static function matricular(array $data, User $usuario): Matricula
    {
        $data['persona_id'] = app(FormularioDePersona::class)->persona($data, $usuario);

        return app(MatricularEnVentanilla::class)(
            $data['persona_id'],
            (int) $data['grupo_id'],
            MatriculaEnVentanillaForm::pagoId($data),
            MatriculaEnVentanillaForm::pagoAnterior($data),
            $usuario,
        );
    }

    /**
     * @return list<Action>
     */
    public static function movimientos(): array
    {
        return [self::trasladar(), self::retirar()];
    }

    public static function trasladar(): Action
    {
        return self::movimiento('trasladar')
            ->label('Trasladar')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->schema([
                Select::make('grupo_destino_id')
                    ->label('Grupo de destino')
                    ->helperText('Grupos del mismo periodo y ciclo, abiertos o cerrados, con vacantes.')
                    ->options(fn (Matricula $record): array => app(GruposParaTraslado::class)($record->grupo_id))
                    ->required(),
                self::motivo('Motivo del traslado'),
            ])
            ->action(fn (Action $action, Matricula $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Matricula => app(TrasladarMatricula::class)($record->id, (int) $data['grupo_destino_id'], $data['motivo'], $usuario),
                'Matrícula trasladada',
            ));
    }

    public static function retirar(): Action
    {
        return self::movimiento('retirar')
            ->label('Retirar')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->modalDescription('El retiro oficial libera la vacante del grupo. No se puede deshacer.')
            ->schema([self::motivo('Motivo del retiro')])
            ->action(fn (Action $action, Matricula $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Matricula => app(RetirarMatricula::class)($record->id, $data['motivo'], $usuario),
                'Matrícula retirada',
            ));
    }

    private static function movimiento(string $nombre): Action
    {
        return Action::make($nombre)
            ->authorize($nombre)
            ->visible(fn (Matricula $record): bool => $record->estado === EstadoMatricula::Activa)
            ->after(fn (Matricula $record): Matricula => $record->refresh());
    }

    private static function motivo(string $etiqueta): Textarea
    {
        return Textarea::make('motivo')->label($etiqueta)->required()->maxLength(500)->rows(3);
    }
}
