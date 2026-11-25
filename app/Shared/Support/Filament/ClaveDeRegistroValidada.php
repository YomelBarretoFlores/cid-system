<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Closure;
use Illuminate\Database\Eloquent\Model;

// A key that is not a valid bigint ("/tramites/create" on a resource without a create page) must answer 404, not a PostgreSQL cast error.
trait ClaveDeRegistroValidada
{
    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $modelo = app(static::getModel());
        $campo = static::getRecordRouteKeyName() ?? $modelo->getRouteKeyName();
        $claveNumerica = $campo === $modelo->getKeyName() && $modelo->getKeyType() === 'int';

        if ($claveNumerica && filter_var($key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return null;
        }

        return parent::resolveRecordRouteBinding($key, $modifyQuery);
    }
}
