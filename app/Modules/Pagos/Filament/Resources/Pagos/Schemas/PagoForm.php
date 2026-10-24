<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Schemas;

use Filament\Schemas\Schema;

final class PagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
