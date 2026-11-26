<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposVinculo\Pages;

use App\Modules\Personas\Actions\GuardarTipoDeVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\TipoVinculoResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditTipoVinculo extends EditRecord
{
    #[\Override]
    protected static string $resource = TipoVinculoResource::class;

    /**
     * @param  array{nombre: string, orden: int|string, activo?: bool, por_defecto?: bool}  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return CatalogoEnElPanel::guardar(
                fn (): Model => app(GuardarTipoDeVinculo::class)((int) $record->getKey(), [
                    'nombre' => $data['nombre'],
                    'orden' => (int) $data['orden'],
                    'activo' => (bool) ($data['activo'] ?? $record->getAttribute('activo')),
                    'por_defecto' => (bool) ($data['por_defecto'] ?? $record->getAttribute('por_defecto')),
                ]),
                TipoVinculoResource::TABLA,
                TipoVinculoResource::REPETIDO,
            );
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
