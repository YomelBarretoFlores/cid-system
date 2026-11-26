<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Modalidades\Pages;

use App\Modules\Academico\Actions\GuardarModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\ModalidadResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditModalidad extends EditRecord
{
    #[\Override]
    protected static string $resource = ModalidadResource::class;

    /**
     * @param  array{nombre: string, descripcion?: ?string, orden: int|string, activo?: bool}  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return CatalogoEnElPanel::guardar(
            fn (): Model => app(GuardarModalidad::class)((int) $record->getKey(), [
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'orden' => (int) $data['orden'],
                'activo' => (bool) ($data['activo'] ?? $record->getAttribute('activo')),
            ]),
            ModalidadResource::TABLA,
            ModalidadResource::REPETIDO,
        );
    }
}
