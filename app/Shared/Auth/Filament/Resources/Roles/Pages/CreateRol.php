<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Roles\Pages;

use App\Shared\Auth\Actions\GuardarRol;
use App\Shared\Auth\Data\DatosDeRol;
use App\Shared\Auth\Filament\Resources\Roles\RolResource;
use App\Shared\Auth\Support\CatalogoDePermisos;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateRol extends CreateRecord
{
    #[\Override]
    protected static string $resource = RolResource::class;

    #[\Override]
    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(GuardarRol::class)(self::datos($data), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeRol
    {
        return new DatosDeRol(is_string($data['name'] ?? null) ? $data['name'] : '', CatalogoDePermisos::aplanar($data['permisos'] ?? []));
    }

    protected function getRedirectUrl(): string
    {
        return RolResource::getUrl('index');
    }
}
