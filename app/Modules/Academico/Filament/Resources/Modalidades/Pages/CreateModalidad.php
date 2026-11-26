<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Modalidades\Pages;

use App\Modules\Academico\Actions\GuardarModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\ModalidadResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateModalidad extends CreateRecord
{
    #[\Override]
    protected static string $resource = ModalidadResource::class;

    /**
     * @param  array{nombre: string, descripcion?: ?string, orden: int|string, activo?: bool, usa_aula?: bool, usa_enlace?: bool}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return CatalogoEnElPanel::guardar(
                fn (): Model => app(GuardarModalidad::class)(null, [
                    'nombre' => $data['nombre'],
                    'descripcion' => $data['descripcion'] ?? null,
                    'orden' => (int) $data['orden'],
                    'activo' => (bool) ($data['activo'] ?? true),
                    'usa_aula' => (bool) ($data['usa_aula'] ?? false),
                    'usa_enlace' => (bool) ($data['usa_enlace'] ?? false),
                ]),
                ModalidadResource::TABLA,
                ModalidadResource::REPETIDO,
            );
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return ModalidadResource::getUrl('index');
    }
}
