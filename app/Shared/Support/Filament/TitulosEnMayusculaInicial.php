<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Illuminate\Support\Str;

// Filament capitalizes every word ("Solicitudes De Clasificación"); Spanish titles only capitalize the first one.
trait TitulosEnMayusculaInicial
{
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
