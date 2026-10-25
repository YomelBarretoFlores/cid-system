<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use Filament\Auth\MultiFactor\App\AppAuthentication;

final class AutenticacionPorApp extends AppAuthentication
{
    // Filament shows "set up" as a small link that staff miss; a button makes the next step obvious.
    public function getActions(): array
    {
        $acciones = parent::getActions();
        $acciones[0]->button();

        return $acciones;
    }
}
