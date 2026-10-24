<?php

declare(strict_types=1);

use App\Modules\Academico\AcademicoServiceProvider;
use App\Modules\Certificacion\CertificacionServiceProvider;
use App\Modules\Docencia\DocenciaServiceProvider;
use App\Modules\Matricula\MatriculaServiceProvider;
use App\Modules\Pagos\PagosServiceProvider;
use App\Modules\Personas\PersonasServiceProvider;
use App\Modules\Tablero\TableroServiceProvider;
use App\Modules\Tramites\TramitesServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Shared\Audit\AuditServiceProvider;

return [
    AppServiceProvider::class,
    AuditServiceProvider::class,
    AdminPanelProvider::class,
    AcademicoServiceProvider::class,
    PersonasServiceProvider::class,
    MatriculaServiceProvider::class,
    PagosServiceProvider::class,
    DocenciaServiceProvider::class,
    CertificacionServiceProvider::class,
    TableroServiceProvider::class,
    TramitesServiceProvider::class,
];
