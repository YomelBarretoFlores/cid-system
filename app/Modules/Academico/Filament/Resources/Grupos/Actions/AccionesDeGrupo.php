<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Actions;

use App\Modules\Academico\Actions\CambiarEstadoGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class AccionesDeGrupo
{
    /**
     * @return list<Action>
     */
    public static function estados(): array
    {
        return [
            self::transicion('abrir', EstadoGrupo::Abierto, 'Grupo abierto')
                ->label(fn (Grupo $record): string => $record->estado === EstadoGrupo::Cerrado ? 'Reabrir' : 'Abrir')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success'),
            self::transicion('cerrar', EstadoGrupo::Cerrado, 'Grupo cerrado')
                ->label('Cerrar')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning'),
            self::transicion('finalizar', EstadoGrupo::Finalizado, 'Grupo finalizado')
                ->label('Finalizar')
                ->icon(Heroicon::OutlinedFlag)
                ->color('gray'),
            self::transicion('cancelar', EstadoGrupo::Cancelado, 'Grupo cancelado')
                ->label('Cancelar grupo')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->modalDescription('Solo se cancela un grupo sin estudiantes matriculados.'),
        ];
    }

    private static function transicion(string $nombre, EstadoGrupo $destino, string $exito): Action
    {
        return Action::make($nombre)
            ->authorize('cambiarEstado')
            ->visible(fn (Grupo $record): bool => $record->estado->puedeTransitarA($destino))
            ->requiresConfirmation()
            ->action(fn (Action $action, Grupo $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Grupo => app(CambiarEstadoGrupo::class)($record->id, $destino),
                $exito,
            ))
            ->after(fn (Grupo $record): Grupo => $record->refresh());
    }
}
