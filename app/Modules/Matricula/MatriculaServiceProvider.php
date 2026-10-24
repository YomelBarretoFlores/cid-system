<?php

declare(strict_types=1);

namespace App\Modules\Matricula;

use App\Modules\Matricula\Actions\DescribirContextoAcademicoDePagos;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Shared\Support\ModuleServiceProvider;

final class MatriculaServiceProvider extends ModuleServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ContextoAcademicoDePagos::class => DescribirContextoAcademicoDePagos::class,
    ];

    protected string $module = 'Matricula';
}
