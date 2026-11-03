<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Models\Documento;

final readonly class ResumenDeEmision
{
    /**
     * @param  list<Documento>  $emitidos
     * @param  int  $omitidos  students who already had a valid document
     * @param  list<string>  $fallidos  student name and the rule that refused the document
     */
    public function __construct(public array $emitidos, public int $omitidos, public array $fallidos) {}
}
