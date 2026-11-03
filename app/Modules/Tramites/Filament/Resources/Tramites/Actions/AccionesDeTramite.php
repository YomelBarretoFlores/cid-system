<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites\Actions;

use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\CerrarTramiteSinAtender;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\ReanudarTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class AccionesDeTramite
{
    public static function avanzar(): Action
    {
        return Action::make('avanzar')
            ->label(fn (Tramite $record): string => 'Avanzar a «'.($record->etapas[$record->etapa_actual + 1]['nombre'] ?? '').'»')
            ->icon(Heroicon::OutlinedArrowRight)
            ->authorize('gestionar')
            ->visible(fn (Tramite $record): bool => self::puedeAvanzar($record) && ! self::siguienteEsLaUltima($record))
            ->requiresConfirmation()
            ->modalDescription('El estudiante recibirá un correo con el cambio.')
            ->action(fn (Action $action, Tramite $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(AvanzarTramite::class)($record->id, $usuario),
                'Trámite avanzado',
            ))
            ->after(fn (Tramite $record): Tramite => $record->refresh());
    }

    public static function finalizar(): Action
    {
        return Action::make('finalizar')
            ->label(fn (Tramite $record): string => 'Finalizar: «'.($record->etapas[count($record->etapas) - 1]['nombre'] ?? '').'»')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('gestionar')
            ->visible(fn (Tramite $record): bool => self::puedeAvanzar($record) && self::siguienteEsLaUltima($record))
            ->modalDescription(fn (Tramite $record): string => "Al finalizar se ejecuta: {$record->accion_final->etiqueta()}. El trámite queda cerrado y el estudiante recibe un correo.")
            ->schema(fn (Tramite $record): array => $record->accion_final === AccionFinal::Traslado ? [
                Select::make('grupo_destino')->label('Grupo de destino')->options(self::gruposDeDestino($record))->required(),
            ] : [])
            ->action(fn (Action $action, Tramite $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(FinalizarTramite::class)($record->id, $usuario, isset($data['grupo_destino']) ? (int) $data['grupo_destino'] : null),
                'Trámite finalizado',
            ))
            ->after(fn (Tramite $record): Tramite => $record->refresh());
    }

    public static function observar(): Action
    {
        return Action::make('observar')
            ->label('Observar')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('warning')
            ->authorize('gestionar')
            ->visible(fn (Tramite $record): bool => in_array($record->estado, [EstadoTramite::EnProceso, EstadoTramite::PendientePago], true))
            ->modalDescription('El estudiante verá la observación en su portal y la recibirá por correo.')
            ->schema([self::motivo('Observación')])
            ->action(fn (Action $action, Tramite $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(ObservarTramite::class)($record->id, $usuario, (string) $data['motivo']),
                'Trámite observado',
            ))
            ->after(fn (Tramite $record): Tramite => $record->refresh());
    }

    public static function reanudar(): Action
    {
        return Action::make('reanudar')
            ->label('Levantar la observación')
            ->icon(Heroicon::OutlinedArrowPath)
            ->authorize('gestionar')
            ->visible(fn (Tramite $record): bool => $record->estado === EstadoTramite::Observado)
            ->requiresConfirmation()
            ->modalDescription('El trámite continúa desde su etapa actual; si la tasa sigue sin pagar, vuelve a esperar el pago.')
            ->action(fn (Action $action, Tramite $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(ReanudarTramite::class)($record->id, $usuario),
                'Observación levantada',
            ))
            ->after(fn (Tramite $record): Tramite => $record->refresh());
    }

    public static function cerrar(): Action
    {
        return Action::make('cerrar')
            ->label('Cerrar sin atender')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('gestionar')
            ->visible(fn (Tramite $record): bool => ! $record->estado->terminado())
            ->modalHeading('Cerrar el trámite sin atenderlo')
            ->modalDescription(fn (Tramite $record): string => "No se ejecuta «{$record->accion_final->etiqueta()}». El trámite no se elimina: queda cerrado en el historial y el estudiante recibe un correo con el motivo. Si tenía un pago aplicado, ese pago no se devuelve ni se usa en otro trámite.")
            ->schema([self::motivo('Motivo del cierre')])
            ->action(fn (Action $action, Tramite $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(CerrarTramiteSinAtender::class)($record->id, $usuario, (string) $data['motivo']),
                'Trámite cerrado sin atender',
            ))
            ->after(fn (Tramite $record): Tramite => $record->refresh());
    }

    private static function motivo(string $etiqueta): Textarea
    {
        return Textarea::make('motivo')
            ->label($etiqueta)
            ->required()
            ->minLength(5)
            ->maxLength(500)
            ->rows(3)
            ->live(debounce: 400)
            ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 500');
    }

    private static function puedeAvanzar(Tramite $record): bool
    {
        return $record->estado === EstadoTramite::EnProceso && $record->condicionDeLaSiguiente() === CondicionDeEtapa::AccionDelPersonal;
    }

    private static function siguienteEsLaUltima(Tramite $record): bool
    {
        return $record->etapa_actual + 1 === count($record->etapas) - 1;
    }

    /**
     * @return array<int, string>
     */
    private static function gruposDeDestino(Tramite $record): array
    {
        $matricula = app(DocumentoDeMatriculaPorId::class)((int) ($record->datos['matricula_id'] ?? 0));

        return $matricula === null ? [] : app(GruposParaTraslado::class)($matricula->grupo->id);
    }
}
