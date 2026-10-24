<?php

declare(strict_types=1);

namespace App\Modules\Docencia;

use App\Modules\Docencia\Actions\VerificarCargaDelGrupo;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Shared\Support\ModuleServiceProvider;

final class DocenciaServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        VerificacionDeCargaDocente::class => VerificarCargaDelGrupo::class,
    ];

    protected string $module = 'Docencia';
}
