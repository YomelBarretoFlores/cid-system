<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;

// Small academic and payment scenarios for the management dashboard, built straight on the factories.
final class DatosDelTablero
{
    public static function periodo(string $nombre, string $inicio, string $fin, int $anio = 2027, int $numero = 1, EstadoPeriodo $estado = EstadoPeriodo::Cerrado): Periodo
    {
        return Periodo::factory()->create([
            'nombre' => $nombre, 'anio' => $anio, 'numero' => $numero, 'estado' => $estado,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'matricula_inicio' => $inicio, 'matricula_fin' => $inicio,
        ]);
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

    public static function pago(TipoConcepto $tipo, string $monto, string $fecha, string $estado = 'validado'): Pago
    {
        return Pago::factory()->{$estado}()->for(self::concepto($tipo))->create(['monto' => $monto, 'fecha_pago' => $fecha]);
    }

    public static function matricula(Grupo $grupo, ?Pago $pago = null, bool $retirada = false): Matricula
    {
        $fabrica = $retirada ? Matricula::factory()->retirada() : Matricula::factory()->activa();

        return $fabrica->create(['grupo_id' => $grupo->id, 'pago_id' => $pago?->id, ...($pago instanceof Pago ? ['persona_id' => $pago->persona_id] : [])]);
    }
}
