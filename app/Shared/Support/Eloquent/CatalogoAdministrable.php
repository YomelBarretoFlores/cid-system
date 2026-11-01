<?php

declare(strict_types=1);

namespace App\Shared\Support\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A catalog the CILN edits from the panel: its code comes from the first name and never changes, so data and
 * integrations keep pointing at it; items in use are deactivated, never deleted.
 *
 * @mixin Model
 */
trait CatalogoAdministrable
{
    public static function bootCatalogoAdministrable(): void
    {
        static::creating(function (Model $catalogo): void {
            $catalogo->setAttribute('codigo', $catalogo->getAttribute('codigo') ?? Str::slug((string) $catalogo->getAttribute('nombre'), '_'));
        });
    }

    public static function idDe(string $codigo): int
    {
        return (int) static::query()->where('codigo', $codigo)->valueOrFail('id');
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }
}
