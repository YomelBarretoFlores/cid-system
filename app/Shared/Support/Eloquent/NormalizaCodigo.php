<?php

declare(strict_types=1);

namespace App\Shared\Support\Eloquent;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait NormalizaCodigo
{
    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(trim($codigo));
    }

    /**
     * @return Attribute<string, string>
     */
    protected function codigo(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizarCodigo($value));
    }
}
