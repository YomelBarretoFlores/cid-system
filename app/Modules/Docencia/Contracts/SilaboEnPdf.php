<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Data\SilaboParaLeer;
use Spatie\LaravelPdf\PdfBuilder;

interface SilaboEnPdf
{
    public function __invoke(SilaboParaLeer $silabo): PdfBuilder;
}
