<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Schemas\Components\Component;

final class IngresarAlPanel extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $respuesta = parent::authenticate();

        if ($respuesta instanceof LoginResponse) {
            ProtegerSesionDelPanel::registrarActividad();
        }

        return $respuesta;
    }

    // A remembered session would come back without the second factor, so the panel never offers it.
    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()->hidden();
    }
}
