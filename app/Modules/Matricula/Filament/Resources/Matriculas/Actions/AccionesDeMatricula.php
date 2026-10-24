<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Actions;

use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Matricula\Actions\RetirarMatricula;
use App\Modules\Matricula\Actions\TrasladarMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Auth\Models\User;
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
