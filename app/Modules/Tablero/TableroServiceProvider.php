<?php

declare(strict_types=1);

namespace App\Modules\Tablero;

use App\Shared\Support\ModuleServiceProvider;

final class TableroServiceProvider extends ModuleServiceProvider
{
    #[\Override]
    protected string $module = 'Tablero';
}
