<?php

declare(strict_types=1);

namespace App\Shared\Support\Eloquent;

use App\Shared\Support\Cache\ContenidoPublico;

// For the models the public home page shows: saving or deleting one refreshes the page at once.
trait RenuevaContenidoPublico
{
    protected static function bootRenuevaContenidoPublico(): void
    {
        static::saved(ContenidoPublico::olvidar(...));
        static::deleted(ContenidoPublico::olvidar(...));
    }
}
