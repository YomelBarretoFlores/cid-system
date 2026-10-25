<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Database\Seeders;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;

// The previous month's groups close their final grade sheets through the real Action; one of them is reopened for a rectification.
final class CertificacionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $docenteDemo = User::query()->where('email', 'docente@ciln.test')->firstOrFail();

        foreach (Grupo::query()->whereRelation('periodo', 'estado', 'cerrado')->orderBy('id')->get() as $grupo) {
            $docente = app(DocenteDelGrupo::class)($grupo->id);

            if ($docente !== null) {
                app(CerrarActa::class)($grupo->id, $docente->docenteId, $grupo->codigo === 'ING-1-M' ? $docenteDemo : $administrador);
            }
        }

        $acta = Acta::query()->where('grupo_codigo', 'POR-1-T')->first();

        if ($acta !== null) {
            app(AutorizarRectificacion::class)($acta->id, 'La nota del examen final de un estudiante se transcribió con error; el docente la corregirá.', $administrador);
        }
    }
}
