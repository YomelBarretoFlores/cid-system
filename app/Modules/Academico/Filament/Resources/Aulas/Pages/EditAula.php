<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Pages;

use App\Modules\Academico\Actions\ActualizarAula;
use App\Modules\Academico\Filament\Resources\Aulas\AulaResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditAula extends EditRecord
{
    #[\Override]
    protected static string $resource = AulaResource::class;

    /**
     * @param  array{nombre: string, capacidad: int|string, ubicacion?: ?string, activo?: bool}  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(ActualizarAula::class)($record->getKey(), [
                'nombre' => $data['nombre'],
                'capacidad' => (int) $data['capacidad'],
                'ubicacion' => array_key_exists('ubicacion', $data) ? $data['ubicacion'] : $record->getAttribute('ubicacion'),
                'activo' => (bool) ($data['activo'] ?? $record->getAttribute('activo')),
            ]);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }
}
