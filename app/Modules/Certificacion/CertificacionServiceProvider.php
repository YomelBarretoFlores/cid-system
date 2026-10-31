<?php

declare(strict_types=1);

namespace App\Modules\Certificacion;

use App\Modules\Certificacion\Actions\ArmarBoletaDeNotas;
use App\Modules\Certificacion\Actions\ArmarRecordDeNotas;
use App\Modules\Certificacion\Actions\ConsultarAvanceDeNotas;
use App\Modules\Certificacion\Actions\EmitirDocumentoParaTramite;
use App\Modules\Certificacion\Actions\ListarIdiomasConNotasCerradas;
use App\Modules\Certificacion\Actions\ListarMatriculasAprobadas;
use App\Modules\Certificacion\Actions\ListarMatriculasCalificadas;
use App\Modules\Certificacion\Actions\ListarNivelesPorCertificar;
use App\Modules\Certificacion\Contracts\AvanceDeNotas;
use App\Modules\Certificacion\Contracts\BoletaDeNotas;
use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Contracts\MatriculasAprobadas;
use App\Modules\Certificacion\Contracts\MatriculasCalificadas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
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
        MatriculasCalificadas::class => ListarMatriculasCalificadas::class,
        AvanceDeNotas::class => ConsultarAvanceDeNotas::class,
        BoletaDeNotas::class => ArmarBoletaDeNotas::class,
        RecordDeNotas::class => ArmarRecordDeNotas::class,
    ];

    protected string $module = 'Certificacion';
}
