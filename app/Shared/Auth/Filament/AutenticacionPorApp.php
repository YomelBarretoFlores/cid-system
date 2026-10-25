<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;

final class AutenticacionPorApp extends AppAuthentication
{
    // Filament shows "set up" as a small link that staff miss; a button makes the next step obvious.
    public function getActions(): array
    {
        return array_map(
            fn (Action $accion): Action => $accion->getName() === 'setUpAppAuthentication' ? $accion->button() : $accion,
            parent::getActions(),
        );
    }
}
