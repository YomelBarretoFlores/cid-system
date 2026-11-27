<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use Illuminate\Database\Seeder;

// Every pre-acta component of the demo student's current groups, so their grades page and the pre-acta are complete; grades already typed stay.
final class NotasCompletasDemoSeeder extends Seeder
{
    private const array NOTAS = ['auditiva' => '16.00', 'oral' => '17.50', 'lectora' => '15.50', 'escrita' => '16.00', 'parcial' => '15.00', 'final' => '16.50'];

    public function run(): void
    {
        $matriculas = Matricula::query()
            ->where(['persona_id' => CuentasDeDemo::personaDelEstudiante(), 'estado' => 'activa'])
            ->whereIn('grupo_id', Grupo::query()->whereRelation('periodo', 'estado', 'en_curso')->select('id'))
            ->orderBy('grupo_id')->get();
        $registradas = $matriculas->sum(fn (Matricula $matricula): int => $this->completar($matricula));

        $this->command?->info("Notas de demo: {$registradas} notas registradas");
    }

    private function completar(Matricula $matricula): int
    {
        $docenteId = CargaAcademica::query()->where('grupo_id', $matricula->grupo_id)->value('docente_id');
        $llenas = NotaParcial::query()->where('matricula_id', $matricula->id)->whereNotNull('nota')->pluck('componente_id')->map(intval(...))->all();
        $faltantes = collect(app(EsquemaDelGrupo::class)($matricula->grupo_id)?->ingresadas() ?? [])
            ->reject(fn (ComponenteDeEvaluacion $componente): bool => in_array($componente->id, $llenas, true))
            ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => self::NOTAS[$componente->codigo] ?? '15.00']);

        if ($docenteId === null || $faltantes->isEmpty()) {
            return 0;
        }

        app(RegistrarNotasParciales::class)($matricula->grupo_id, (int) $docenteId, [$matricula->id => $faltantes->all()], CuentasDeDemo::usuarioDe((int) $docenteId));

        return $faltantes->count();
    }
}
