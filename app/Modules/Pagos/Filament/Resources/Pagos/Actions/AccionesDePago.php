<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Actions;

use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Support\AlertasDePago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AccionesDePago
{
    public static function descargarRecibo(): Action
    {
        return Action::make('descargarRecibo')
            ->label('Descargar recibo')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize('view')
            ->action(fn (Action $action, Pago $record): ?StreamedResponse => self::recibo($action, $record));
    }

    /**
     * @return list<Action>
     */
    public static function revision(): array
    {
        return [self::validar(), self::rechazar(), self::anular()];
    }

    public static function validar(): Action
    {
        return self::transicion('validar', EstadoPago::Validado)
            ->label('Validar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->modalHeading('Validar pago')
            ->modalDescription(fn (Pago $record): string => implode(' ', ['Valide solo con la boleta original en la mano.', ...AlertasDePago::de($record)]))
            ->modalSubmitActionLabel('Validar')
            ->schema([
                Checkbox::make('boleta_original')
                    ->label('Tengo la boleta original y coincide con el número, la fecha y el monto registrados')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Valide el pago solo con la boleta original en la mano.']),
                Textarea::make('justificacion_monto')
                    ->label('Justificación del monto distinto de la tarifa')
                    ->visible(fn (Pago $record): bool => AlertasDePago::diferenciaDeMonto($record) !== null)
                    ->required()
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(fn (Action $action, Pago $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Pago => app(ValidarPago::class)(
                    $record->id,
                    $usuario,
                    boletaOriginalVerificada: ($data['boleta_original'] ?? false) === true,
                    justificacionMonto: is_string($data['justificacion_monto'] ?? null) ? $data['justificacion_monto'] : null,
                ),
                'Pago validado',
            ));
    }

    public static function rechazar(): Action
    {
        return self::transicion('rechazar', EstadoPago::Rechazado)
            ->label('Rechazar')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->schema([self::motivo('Motivo del rechazo')])
            ->action(fn (Action $action, Pago $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Pago => app(RechazarPago::class)($record->id, $usuario, $data['motivo']),
                'Pago rechazado',
            ));
    }

    public static function anular(): Action
    {
        return self::transicion('anular', EstadoPago::Anulado)
            ->label('Anular')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('gray')
            ->schema([self::motivo('Motivo de la anulación')])
            ->action(fn (Action $action, Pago $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Pago => app(AnularPago::class)($record->id, $usuario, $data['motivo']),
                'Pago anulado',
            ));
    }

    private static function recibo(Action $action, Pago $record): ?StreamedResponse
    {
        $disco = Storage::disk('local');

        if (! $disco->exists($record->recibo_path)) {
            Notification::make()->danger()->title('No se encontró el archivo del recibo.')->send();
            $action->halt();

            return null;
        }

        $extension = pathinfo($record->recibo_path, PATHINFO_EXTENSION);

        return $disco->download($record->recibo_path, 'recibo-'.Str::slug($record->numero_operacion).".{$extension}");
    }

    private static function transicion(string $nombre, EstadoPago $destino): Action
    {
        return Action::make($nombre)
            ->authorize($nombre)
            ->visible(fn (Pago $record): bool => $record->estado->puedeTransitarA($destino))
            ->after(fn (Pago $record): Pago => $record->refresh());
    }

    private static function motivo(string $etiqueta): Textarea
    {
        return Textarea::make('motivo')->label($etiqueta)->required()->maxLength(500)->rows(3);
    }
}
