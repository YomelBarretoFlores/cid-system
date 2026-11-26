<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\GradosFut\Pages;

use App\Modules\Tramites\Actions\GuardarGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\GradoFutResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditGradoFut extends EditRecord
{
    #[\Override]
    protected static string $resource = GradoFutResource::class;

    /**
     * @param  array{nombre: string, titulo_otorgado: string, orden: int|string, activo?: bool}  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return CatalogoEnElPanel::guardar(
            fn (): Model => app(GuardarGradoFut::class)((int) $record->getKey(), [
                'nombre' => $data['nombre'],
                'titulo_otorgado' => $data['titulo_otorgado'],
                'orden' => (int) $data['orden'],
                'activo' => (bool) ($data['activo'] ?? $record->getAttribute('activo')),
            ]),
            GradoFutResource::TABLA,
            GradoFutResource::REPETIDO,
        );
    }
}
