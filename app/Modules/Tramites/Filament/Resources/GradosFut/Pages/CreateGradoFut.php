<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\GradosFut\Pages;

use App\Modules\Tramites\Actions\GuardarGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\GradoFutResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateGradoFut extends CreateRecord
{
    #[\Override]
    protected static string $resource = GradoFutResource::class;

    /**
     * @param  array{nombre: string, titulo_otorgado: string, orden: int|string, activo?: bool}  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return CatalogoEnElPanel::guardar(
            fn (): Model => app(GuardarGradoFut::class)(null, [
                'nombre' => $data['nombre'],
                'titulo_otorgado' => $data['titulo_otorgado'],
                'orden' => (int) $data['orden'],
                'activo' => (bool) ($data['activo'] ?? true),
            ]),
            GradoFutResource::TABLA,
            GradoFutResource::REPETIDO,
        );
    }

    protected function getRedirectUrl(): string
    {
        return GradoFutResource::getUrl('index');
    }
}
