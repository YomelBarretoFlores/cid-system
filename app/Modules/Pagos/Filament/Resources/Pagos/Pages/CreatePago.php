<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Pages;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class CreatePago extends CreateRecord
{
    #[\Override]
    protected static string $resource = PagoResource::class;

    #[\Override]
    protected static ?string $title = 'Registrar pago';

    #[\Override]
    protected static bool $canCreateAnother = false;

    // The person page opens this form with the payer already chosen.
    protected function afterFill(): void
    {
        $persona = request()->query('persona');

        if (is_string($persona) && ctype_digit($persona)) {
            $this->data['persona_id'] = (int) $persona;
        }
    }

    /**
     * @param  array{persona_id: int|string, concepto_id: int|string, medio_pago_id: int|string, numero_operacion: string, fecha_pago: string, monto: int|float|string, recibo: UploadedFile, nivel?: string|null, programa_id?: int|string|null}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $registro = new RegistroDePago(
            personaId: (int) $data['persona_id'],
            conceptoId: (int) $data['concepto_id'],
            medioPagoId: (int) $data['medio_pago_id'],
            numeroOperacion: $data['numero_operacion'],
            fechaPago: CarbonImmutable::parse($data['fecha_pago']),
            monto: (string) $data['monto'],
            nivel: NivelCurricular::tryFrom((string) ($data['nivel'] ?? '')),
            programaId: is_numeric($data['programa_id'] ?? null) ? (int) $data['programa_id'] : null,
        );

        try {
            return app(RegistrarPago::class)($registro, $data['recibo'], AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

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
