<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use App\Shared\Auth\Actions\DesactivarUsuario;
use Filament\Forms\Components\Textarea;

final class MotivoDeBaja
{
    public static function campo(): Textarea
    {
        return Textarea::make('motivo')->label('Motivo')->required()->maxLength(DesactivarUsuario::MAXIMO_DEL_MOTIVO)->rows(3)
            ->helperText('Por ejemplo: dejó de enseñar en el CILN, cuenta duplicada o uso indebido.');
    }
}
