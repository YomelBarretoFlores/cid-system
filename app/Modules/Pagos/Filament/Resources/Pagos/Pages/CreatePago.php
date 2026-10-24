<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Pages;

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class CreatePago extends CreateRecord
{
    protected static string $resource = PagoResource::class;

    protected static ?string $title = 'Registrar pago';

    protected static bool $canCreateAnother = false;

    /**
     * @param  array{persona_id: int|string, concepto_id: int|string, medio_pago: string, numero_operacion: string, fecha_pago: string, monto: int|float|string, recibo: UploadedFile}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $usuario = auth()->user();
        $registro = new RegistroDePago(
            personaId: (int) $data['persona_id'],
            conceptoId: (int) $data['concepto_id'],
            medioPago: MedioPago::from($data['medio_pago']),
            numeroOperacion: $data['numero_operacion'],
            fechaPago: CarbonImmutable::parse($data['fecha_pago']),
            monto: (string) $data['monto'],
        );

        try {
            return app(RegistrarPago::class)($registro, $data['recibo'], $usuario instanceof User ? $usuario : null);
        } catch (ReglaDeNegocioIncumplida $error) {
            Notification::make()->danger()->title($error->getMessage())->send();

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Pago registrado';
    }

    protected function getRedirectUrl(): string
    {
        return PagoResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
