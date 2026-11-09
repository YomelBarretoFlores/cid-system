<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Controllers;

use App\Modules\Personas\Http\Controllers\Concerns\SirveFoto;
use App\Modules\Personas\Models\Persona;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VerFotoParaPersonal
{
    use SirveFoto;

    public function __invoke(Persona $persona): StreamedResponse
    {
        return $this->servirFoto($persona->foto);
    }
}
