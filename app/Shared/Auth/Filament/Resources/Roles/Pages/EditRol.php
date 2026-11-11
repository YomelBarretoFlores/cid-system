<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Roles\Pages;

use App\Shared\Auth\Actions\GuardarRol;
use App\Shared\Auth\Filament\Resources\Roles\RolResource;
use App\Shared\Auth\Support\CatalogoDePermisos;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

final class EditRol extends EditRecord
{
    #[\Override]
    protected static string $resource = RolResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rol = $this->getRecord();

        return $rol instanceof Role
            ? ['name' => $rol->name, 'permisos' => CatalogoDePermisos::agrupar($rol->permissions->pluck('name'))]
            : $data;
    }

    /**
     * @param  Role  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(GuardarRol::class)(CreateRol::datos($data), AccionDeNegocio::usuario(), $record);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
