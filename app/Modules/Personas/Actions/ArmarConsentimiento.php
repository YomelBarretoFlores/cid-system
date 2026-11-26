<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use Illuminate\Support\HtmlString;

// The consent sentence of the sign-up form: the staff's text is escaped and only the policy link is added as markup.
final readonly class ArmarConsentimiento
{
    public function __construct(private ConsultarTextosLegales $consultar) {}

    public function __invoke(): HtmlString
    {
        $enlace = '<a href="'.e(route('privacidad')).'" target="_blank" rel="noopener" class="enlace">política de privacidad</a>';

        return new HtmlString(str_replace('{politica}', $enlace, e(($this->consultar)()->consentimiento)));
    }
}
