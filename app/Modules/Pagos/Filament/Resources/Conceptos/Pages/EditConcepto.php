<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Pages;

use App\Modules\Pagos\Actions\ActualizarConcepto;
use App\Modules\Pagos\Data\DatosDeConcepto;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

final class EditConcepto extends EditRecord
{
    protected static string $resource = ConceptoResource::class;

    // Decided when the form opens: a payment registered meanwhile is reported on save instead of silently dropping the change.
    #[Locked]
    public bool $tipoBloqueado = false;

    protected function beforeFill(): void
    {
        $this->tipoBloqueado = Pago::query()->where('concepto_id', $this->getRecord()->getKey())->exists();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // A locked field is not sent, so it keeps the concept's current value.
        $tipo = $data['tipo'] ?? $record->getAttribute('tipo');

        try {
            return app(ActualizarConcepto::class)((int) $record->getKey(), new DatosDeConcepto(
                nombre: (string) $data['nombre'],
                tipo: $tipo instanceof TipoConcepto ? $tipo : TipoConcepto::from((string) $tipo),
                fuenteId: (int) $data['fuente_id'],
                activo: (bool) ($data['activo'] ?? false),
            ));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
