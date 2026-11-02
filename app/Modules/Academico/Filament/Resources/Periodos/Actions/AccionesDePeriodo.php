<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Actions;

use App\Modules\Academico\Actions\CambiarEstadoPeriodo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class AccionesDePeriodo
{
    /**
     * @return list<Action>
     */
    public static function cambiosDeEstado(): array
    {
        return [
            self::transicion('abrirMatricula', EstadoPeriodo::MatriculaAbierta, 'Abrir matrícula', 'Matrícula abierta')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->modalDescription('Los estudiantes podrán matricularse en los grupos abiertos de este periodo.'),
            self::transicion('iniciarClases', EstadoPeriodo::EnCurso, 'Iniciar clases', 'Periodo en curso')
                ->icon(Heroicon::OutlinedPlay)
                ->color('info')
                ->modalDescription(fn (Periodo $record): string => CambiarEstadoPeriodo::empiezaDespuesDeHoy($record)
                    ? "La matrícula del periodo se cerrará. Las clases de este periodo empiezan el {$record->fecha_inicio->format('d/m/Y')}: se iniciarán antes de esa fecha."
                    : 'La matrícula del periodo se cerrará.'),
            self::transicion('cerrar', EstadoPeriodo::Cerrado, 'Cerrar periodo', 'Periodo cerrado')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->modalDescription('Un periodo cerrado no puede volver a abrirse. Sus grupos cerrados pasan a Finalizado y los programados se cancelan.'),
        ];
    }

    private static function transicion(string $nombre, EstadoPeriodo $destino, string $etiqueta, string $exito): Action
    {
        return Action::make($nombre)
            ->label($etiqueta)
            ->authorize('cambiarEstado')
            ->visible(fn (Periodo $record): bool => $record->estado->puedeTransitarA($destino))
            ->requiresConfirmation()
            ->action(fn (Action $action, Periodo $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Periodo => app(CambiarEstadoPeriodo::class)($record->id, $destino, antesDelInicio: CambiarEstadoPeriodo::empiezaDespuesDeHoy($record)),
                $exito,
            ))
            ->after(fn (Periodo $record): Periodo => $record->refresh());
    }
}
