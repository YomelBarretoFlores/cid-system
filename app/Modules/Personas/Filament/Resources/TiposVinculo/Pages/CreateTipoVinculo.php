<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposVinculo\Pages;

use App\Modules\Personas\Actions\GuardarTipoDeVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\TipoVinculoResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTipoVinculo extends CreateRecord
{
    #[\Override]
    protected static string $resource = TipoVinculoResource::class;

    /**
     * @param  array{nombre: string, orden: int|string, activo?: bool, por_defecto?: bool}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return CatalogoEnElPanel::guardar(
                fn (): Model => app(GuardarTipoDeVinculo::class)(null, [
                    'nombre' => $data['nombre'],
                    'orden' => (int) $data['orden'],
                    'activo' => (bool) ($data['activo'] ?? true),
                    'por_defecto' => (bool) ($data['por_defecto'] ?? false),
                ]),
                TipoVinculoResource::TABLA,
                TipoVinculoResource::REPETIDO,
            );
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return TipoVinculoResource::getUrl('index');
    }
}
