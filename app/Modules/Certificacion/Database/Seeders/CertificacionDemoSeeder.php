<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Database\Seeders;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadosDelPeriodo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeNotasDelActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;

// The closed months close their final grade sheets through the real Actions; one is reopened for a rectification,
// and the English cohort gets its level certificates, grade records and one voided record.
final class CertificacionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $docenteDemo = User::query()->where('email', 'docente@ciln.test')->firstOrFail();

        foreach (Grupo::query()->whereRelation('periodo', 'estado', 'cerrado')->orderBy('id')->get() as $grupo) {
            $docente = app(DocenteDelGrupo::class)($grupo->id);

            if ($docente !== null) {
                app(CerrarActa::class)($grupo->id, $docente->docenteId, str_starts_with($grupo->codigo, 'ING-') ? $docenteDemo : $administrador);
            }
        }

        $porRectificar = Acta::query()->where('grupo_codigo', 'POR-1-T')->first();

        if ($porRectificar !== null) {
            app(AutorizarRectificacion::class)($porRectificar->id, 'La nota del examen final de un estudiante se transcribió con error; el docente la corregirá.', $administrador);
        }

        $this->documentos($administrador);
    }

    private function documentos(User $administrador): void
    {
        $cohorte = Acta::query()->where('grupo_codigo', 'ING-4-A')->first();

        if ($cohorte !== null) {
            app(EmitirCertificadosDelPeriodo::class)($cohorte->periodo_id, $administrador);
            $constancias = app(EmitirConstanciasDeNotasDelActa::class)($cohorte->id, $administrador);

            if ($constancias !== []) {
                app(AnularDocumento::class)($constancias[0]->id, 'Se emitió antes de que el estudiante actualizara su nombre en Personas.', $administrador);
            }
        }

        $estudiante = Persona::query()->whereRelation('user', 'email', 'estudiante@ciln.test')->value('id');
        $matricula = Matricula::query()->where(['persona_id' => $estudiante, 'estado' => 'activa'])->value('id');

        if ($matricula !== null) {
            app(EmitirConstanciaDeMatricula::class)($matricula, $administrador);
        }
    }
}
