<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use App\Shared\Auth\Support\CatalogoDePermisos;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;

// One checkbox list per module under permisos.<module>; CatalogoDePermisos turns that state back into permission names.
final class CamposDePermisos
{
    public static function make(string $titulo): Section
    {
        $listas = [];

        foreach (CatalogoDePermisos::asignables() as $grupo => ['nombre' => $nombre, 'permisos' => $permisos]) {
            $listas[] = CheckboxList::make("permisos.{$grupo}")
                ->label($nombre)
                ->options($permisos)
                ->bulkToggleable();
        }

        return Section::make($titulo)->columns(2)->schema($listas);
    }
}
