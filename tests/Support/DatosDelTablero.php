<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

// Small academic and payment scenarios for the management dashboard, built straight on the factories.
final class DatosDelTablero
{
    public static function periodo(string $nombre, string $inicio, string $fin, int $anio = 2027, int $numero = 1, EstadoPeriodo $estado = EstadoPeriodo::Cerrado): Periodo
    {
        self::liberarNumero($anio, $numero);

        return Periodo::factory()->create([
            'nombre' => $nombre, 'anio' => $anio, 'numero' => $numero, 'estado' => $estado,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'matricula_inicio' => $inicio, 'matricula_fin' => $inicio,
        ]);
    }

    // A period the factory numbered at random may already hold the number a test asks for; it moves to a free one.
    private static function liberarNumero(int $anio, int $numero): void
    {
        $usados = Periodo::query()->where('anio', $anio)->pluck('numero')->map(fn (mixed $usado): int => (int) $usado)->all();
        if (! in_array($numero, $usados, true)) {
            return;
        }

        $libre = array_first(array_diff(range(1, Periodo::NUMERO_MAXIMO), $usados)) ?? throw new LogicException("No quedan números de periodo libres en {$anio} para liberar el {$numero}.");
        Periodo::query()->where(['anio' => $anio, 'numero' => $numero])->update(['numero' => $libre]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    public static function grupo(Periodo $periodo, Idioma $idioma, int $ciclo = 1, NivelCurricular $nivel = NivelCurricular::Basico, array $atributos = []): Grupo
    {
        $cicloDelIdioma = Ciclo::query()->firstOrCreate(
            ['idioma_id' => $idioma->id, 'numero' => $ciclo],
            ['nivel' => $nivel, 'nombre' => "{$nivel->etiqueta()} {$ciclo}", 'horas_lectivas' => 60],
        );

        return Grupo::factory()->create(['periodo_id' => $periodo->id, 'ciclo_id' => $cicloDelIdioma->id, ...$atributos]);
    }

    public static function concepto(TipoConcepto $tipo): Concepto
    {
        $codigo = 'T-'.strtoupper(substr($tipo->value, 0, 12));

        return Concepto::query()->where('codigo', $codigo)->first()
            ?? Concepto::factory()->create(['codigo' => $codigo, 'nombre' => "Concepto {$tipo->value}", 'tipo' => $tipo]);
    }

    public static function pago(TipoConcepto $tipo, string $monto, string $fecha, string $estado = 'validado', ?int $personaId = null): Pago
    {
        return Pago::factory()->{$estado}()->for(self::concepto($tipo))->create(['monto' => $monto, 'fecha_pago' => $fecha, ...($personaId === null ? [] : ['persona_id' => $personaId])]);
    }

    public static function matricula(Grupo $grupo, ?Pago $pago = null, bool $retirada = false): Matricula
    {
        $fabrica = $retirada ? Matricula::factory()->retirada() : Matricula::factory()->activa();

        return $fabrica->create(['grupo_id' => $grupo->id, 'pago_id' => $pago?->id, ...($pago instanceof Pago ? ['persona_id' => $pago->persona_id] : [])]);
    }

    public static function clase(Grupo $grupo, Docente $docente, string $fecha, string $inicio, string $fin): ClaseDictada
    {
        return ClaseDictada::query()->create([
            'grupo_id' => $grupo->id, 'docente_id' => $docente->id, 'fecha' => $fecha,
            'hora_inicio' => $inicio, 'hora_fin' => $fin, 'registrado_por' => User::factory()->create()->id,
        ]);
    }

    public static function costo(string $condicion, string $costoHora, string $desde, ?string $hasta = null): CostoPorHora
    {
        return CostoPorHora::query()->create([
            'condicion_laboral_id' => CondicionLaboral::idDe($condicion), 'costo_hora' => $costoHora, 'vigente_desde' => $desde,
            'vigente_hasta' => $hasta, 'registrado_por' => User::factory()->create()->id,
        ]);
    }

    /**
     * @param  list<int>  $dias
     */
    public static function horario(Grupo $grupo, array $dias, string $inicio, string $fin): void
    {
        DB::table('aca_horarios')->insert(array_map(fn (int $dia): array => [
            'grupo_id' => $grupo->id, 'dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fin' => $fin, 'created_at' => now(), 'updated_at' => now(),
        ], $dias));
    }
}
