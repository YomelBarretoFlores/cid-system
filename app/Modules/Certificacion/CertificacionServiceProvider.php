<?php

declare(strict_types=1);

namespace App\Modules\Certificacion;

use App\Modules\Certificacion\Actions\EmitirDocumentoParaTramite;
use App\Modules\Certificacion\Actions\ListarIdiomasConNotasCerradas;
use App\Modules\Certificacion\Actions\ListarMatriculasAprobadas;
use App\Modules\Certificacion\Actions\ListarNivelesPorCertificar;
use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Contracts\MatriculasAprobadas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Shared\Support\ModuleServiceProvider;

final class CertificacionServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        IdiomasConNotasCerradas::class => ListarIdiomasConNotasCerradas::class,
        NivelesPorCertificar::class => ListarNivelesPorCertificar::class,
        DocumentoParaTramite::class => EmitirDocumentoParaTramite::class,
        MatriculasAprobadas::class => ListarMatriculasAprobadas::class,
    ];

    protected string $module = 'Certificacion';
}
