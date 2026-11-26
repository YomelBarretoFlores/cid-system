<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use Illuminate\Support\HtmlString;

// An editable text whose markers print in bold: the staff's text is escaped first, so only the tags added here reach the page.
final class TextoResaltado
{
    /**
     * @param  array<string, string>  $valores  marker (without braces) => value
     */
    public static function html(string $plantilla, array $valores): HtmlString
    {
        $reemplazos = [];

        foreach ($valores as $marca => $valor) {
            $reemplazos['{'.$marca.'}'] = '<strong>'.e($valor).'</strong>';
        }

        return new HtmlString(strtr(e($plantilla), $reemplazos));
    }
}
