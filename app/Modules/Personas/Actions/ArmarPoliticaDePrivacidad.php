<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\Table\Table;

// The staff write Markdown; any HTML they type is shown as text and unsafe links are dropped, so the page never runs their markup.
final readonly class ArmarPoliticaDePrivacidad
{
    public function __construct(private ConsultarTextosLegales $consultar, private DatosInstitucionales $datosInstitucionales) {}

    public function __invoke(): HtmlString
    {
        $textos = ($this->consultar)();
        $institucion = ($this->datosInstitucionales)();
        $markdown = strtr($textos->politica, ['{universidad}' => $institucion->universidad, '{centro}' => $institucion->centro, '{domicilio}' => $textos->domicilio]);

        return new HtmlString(Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'default_attributes' => [Table::class => ['class' => 'tabla-formal']],
        ], [new DefaultAttributesExtension]));
    }
}
