<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Database\Seeders;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Database\Seeders\CuentasDeDemo;
use App\Modules\Docencia\Database\Seeders\RelojDeDemo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// The demo student passed Portuguese I and II in the two closed months before the last one, so their record, the boleta of those
// periods, the missing cycles of the level and the habilitation of cycle 3 by approval show up. Skipped once they have a closed acta.
final class HistorialDemoSeeder extends Seeder
{
    private const array NOTAS = [
        1 => ['auditiva' => '16.00', 'oral' => '17.00', 'lectora' => '15.50', 'escrita' => '16.50', 'parcial' => '15.00', 'final' => '16.00'],
        2 => ['auditiva' => '17.00', 'oral' => '18.00', 'lectora' => '16.00', 'escrita' => '17.50', 'parcial' => '14.50', 'final' => '15.50'],
    ];

    public function run(): void
    {
        $personaId = CuentasDeDemo::personaDelEstudiante();
        $periodos = Periodo::query()->where('estado', 'cerrado')->orderByDesc('fecha_inicio')->skip(1)->take(count(self::NOTAS))->get()->reverse()->values();
        $molde = $this->molde();

        if ($personaId === null || ! $molde instanceof Grupo || $periodos->count() < count(self::NOTAS) || $this->yaTieneHistorial($personaId, $periodos, $molde)) {
            $this->command?->info('Historial de demo: 0 actas cerradas');

            return;
        }

        foreach ($periodos as $posicion => $periodo) {
            $this->cursar($personaId, $periodo, $posicion + 1, $molde);
        }

        $this->command?->info('Historial de demo: '.$periodos->count().' actas cerradas');
    }

    // The Portuguese I group with a teacher lends its teacher, classroom and schedule, so the assignment rules hold.
    private function molde(): ?Grupo
    {
        return Grupo::query()->with(['ciclo', 'horarios'])
            ->whereHas('ciclo', fn ($ciclo) => $ciclo->where('numero', 1)->whereRelation('idioma', 'codigo', 'POR'))
            ->whereIn('id', CargaAcademica::query()->select('grupo_id'))
            ->orderBy('id')->first();
    }

    /**
     * @param  Collection<int, Periodo>  $periodos
     */
    private function yaTieneHistorial(int $personaId, Collection $periodos, Grupo $molde): bool
    {
        $codigos = $periodos->map(fn (Periodo $periodo, int $posicion): array => ['periodo_id' => $periodo->id, 'codigo' => $this->codigo($molde, $posicion + 1)]);

        return NotaFinal::query()->where('persona_id', $personaId)->whereRelation('acta', 'estado', EstadoActa::Cerrada)->exists()
            || $codigos->contains(fn (array $clave): bool => Grupo::query()->where($clave)->exists());
    }

    private function codigo(Grupo $molde, int $numero): string
    {
        return (string) preg_replace('/-\d+-/', "-{$numero}-", $molde->codigo, 1);
    }

    private function cursar(int $personaId, Periodo $periodo, int $numero, Grupo $molde): void
    {
        $grupo = Grupo::query()->create([
            'periodo_id' => $periodo->id, 'ciclo_id' => Ciclo::query()->where(['idioma_id' => $molde->ciclo->idioma_id, 'numero' => $numero])->value('id'),
            'programa_id' => $molde->programa_id, 'aula_id' => $molde->aula_id, 'codigo' => $this->codigo($molde, $numero), 'modalidad' => $molde->modalidad,
            'cupo_maximo' => 25, 'cupo_minimo' => 5, 'estado' => EstadoGrupo::Cerrado,
        ]);
        $grupo->horarios()->createMany($molde->horarios->map(fn (Horario $horario): array => $horario->only(['dia_semana', 'hora_inicio', 'hora_fin']))->all());
        $matricula = $this->matricular($personaId, $grupo, $periodo);
        $docenteId = app(DocenteDelGrupo::class)($molde->id)->docenteId ?? 0;
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();

        app(AsignarDocente::class)($grupo->id, $docenteId, $administrador);
        $notas = collect(app(EsquemaDelGrupo::class)($grupo->id)?->ingresadas() ?? [])
            ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => self::NOTAS[$numero][$componente->codigo] ?? '15.00'])->all();
        app(RegistrarNotasParciales::class)($grupo->id, $docenteId, [$matricula->id => $notas], $administrador);

        // The day after the last class, inside the period's grade calendar.
        $cierre = CarbonImmutable::parse($periodo->fecha_fin->toDateString(), 'America/Lima')->addDay()->setTime(10, 0);
        RelojDeDemo::en($cierre, fn () => app(CerrarActa::class)($grupo->id, $docenteId, $administrador));
    }

    private function matricular(int $personaId, Grupo $grupo, Periodo $periodo): Matricula
    {
        $tarifa = Tarifa::query()->whereRelation('concepto', 'codigo', 'MAT-01')->where('tipo_vinculo', 'publico_general')->sole();
        $matriculadoEn = CarbonImmutable::parse($periodo->matricula_inicio->toDateString(), 'America/Lima')->setTime(9, 30);
        $cajero = User::query()->where('email', 'caja@ciln.test')->value('id');
        $pago = Pago::factory()->validado()->create([
            'persona_id' => $personaId, 'concepto_id' => $tarifa->concepto_id, 'tarifa_id' => $tarifa->id, 'monto' => $tarifa->monto,
            'fecha_pago' => $matriculadoEn->toDateString(), 'validado_en' => $matriculadoEn, 'boleta_original_verificada_en' => $matriculadoEn,
            'validado_por' => $cajero, 'boleta_original_verificada_por' => $cajero,
        ]);
        $matricula = Matricula::factory()->create(['persona_id' => $personaId, 'grupo_id' => $grupo->id, 'pago_id' => $pago->id, 'matriculado_en' => $matriculadoEn]);
        $grupo->increment('matriculados');

        return $matricula;
    }
}
