<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposDocumento\Pages;

use App\Modules\Personas\Actions\GuardarTipoDeDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\TipoDocumentoResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditTipoDocumento extends EditRecord
{
    #[\Override]
    protected static string $resource = TipoDocumentoResource::class;

    /**
     * @param  array{nombre: string, orden: int|string, longitud_minima: int|string, longitud_maxima: int|string, solo_digitos?: bool, activo?: bool, por_defecto?: bool}  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return CatalogoEnElPanel::guardar(
                fn (): Model => app(GuardarTipoDeDocumento::class)((int) $record->getKey(), [
                    'nombre' => $data['nombre'],
                    'solo_digitos' => (bool) ($data['solo_digitos'] ?? $record->getAttribute('solo_digitos')),
                    'longitud_minima' => (int) $data['longitud_minima'],
                    'longitud_maxima' => (int) $data['longitud_maxima'],
                    'orden' => (int) $data['orden'],
                    'activo' => (bool) ($data['activo'] ?? $record->getAttribute('activo')),
                    'por_defecto' => (bool) ($data['por_defecto'] ?? $record->getAttribute('por_defecto')),
                ]),
                TipoDocumentoResource::TABLA,
                TipoDocumentoResource::REPETIDO,
            );
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
