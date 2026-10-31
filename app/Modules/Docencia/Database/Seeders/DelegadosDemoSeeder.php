<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\DesignarDelegado;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use Illuminate\Database\Seeder;

// A delegate for every current group that has none; the demo student delegates their English group so their page fills.
final class DelegadosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $designados = 0;
        $cargas = CargaAcademica::query()
            ->whereIn('grupo_id', Grupo::query()->whereRelation('periodo', 'estado', 'en_curso')->select('id'))
            ->orderBy('grupo_id')->get();

        foreach ($cargas as $carga) {
            $designados += $this->designar($carga);
        }

        $this->command?->info("Delegados de demo: {$designados} designados");
    }

    private function designar(CargaAcademica $carga): int
    {
        $delegado = app(DelegadoDelGrupo::class)($carga->grupo_id) instanceof EstudianteDelGrupo ? null : $this->elegido($carga->grupo_id);

        if (! $delegado instanceof EstudianteDelGrupo) {
            return 0;
        }

        app(DesignarDelegado::class)($carga->grupo_id, $carga->docente_id, $delegado->matriculaId, CuentasDeDemo::usuarioDe($carga->docente_id));

        return 1;
    }

    private function elegido(int $grupoId): ?EstudianteDelGrupo
    {
        $estudiantes = app(EstudiantesDelGrupo::class)($grupoId);
        $demo = collect($estudiantes)->firstWhere('personaId', CuentasDeDemo::personaDelEstudiante());
        $esSuGrupoDeIngles = Grupo::query()->whereKey($grupoId)->value('codigo') === ControlDeClasesDemoSeeder::GRUPO;

        return ($esSuGrupoDeIngles ? $demo : null) ?? $estudiantes[0] ?? null;
    }
}
