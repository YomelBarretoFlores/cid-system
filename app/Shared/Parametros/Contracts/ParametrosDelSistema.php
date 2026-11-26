<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Contracts;

use App\Shared\Parametros\Actions\ConsultarParametrosDelSistema;
use App\Shared\Parametros\Data\ValoresDeParametros;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

// The operational values staff edit on «Parámetros del sistema»; read once per request or job.
#[Bind(ConsultarParametrosDelSistema::class)]
#[Scoped]
interface ParametrosDelSistema
{
    public function __invoke(): ValoresDeParametros;
}
