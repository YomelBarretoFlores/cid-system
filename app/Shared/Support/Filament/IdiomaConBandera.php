<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

// Table cells show the language as its flag leaf plus the text; the component escapes both.
final class IdiomaConBandera
{
    public static function html(string $idioma, ?string $texto = null): HtmlString
    {
        return new HtmlString(Blade::render('<x-ciln.idioma :idioma="$idioma" :texto="$texto" />', ['idioma' => $idioma, 'texto' => $texto]));
    }
}
