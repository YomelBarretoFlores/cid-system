<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Database\Seeders;

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;

final class PagosDemoSeeder extends Seeder
{
    /**
     * Demo amounts only; real fees come from the CILN TUPA/TUSNE.
     *
     * @var array<string, array{0: string, 1: TipoConcepto, 2: array<string, string>}>
     */
    private const array CONCEPTOS = [
        'MAT-01' => ['Derecho de matrícula', TipoConcepto::Matricula, ['estudiante_unasam' => '50.00', 'docente_administrativo_unasam' => '60.00', 'publico_general' => '80.00']],
        'PEN-01' => ['Pensión mensual', TipoConcepto::Pension, ['estudiante_unasam' => '120.00', 'docente_administrativo_unasam' => '140.00', 'publico_general' => '180.00']],
        'EXC-01' => ['Examen de clasificación', TipoConcepto::ExamenClasificacion, ['estudiante_unasam' => '30.00', 'docente_administrativo_unasam' => '30.00', 'publico_general' => '40.00']],
    ];

    public function run(): void
    {
        foreach (self::CONCEPTOS as $codigo => [$nombre, $tipo, $montos]) {
            $concepto = Concepto::query()->create(['codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'fuente' => FuenteConcepto::Tupa]);

            foreach ($montos as $vinculo => $monto) {
                $concepto->tarifas()->create(['tipo_vinculo' => TipoVinculo::from($vinculo), 'monto' => $monto, 'vigente_desde' => '2026-01-01']);
            }
        }

        $this->pagarMatriculas(User::query()->where('email', 'admin@ciln.test')->firstOrFail());
    }

    private function pagarMatriculas(User $cajero): void
    {
        $tarifa = Tarifa::query()
            ->whereRelation('concepto', 'codigo', 'MAT-01')
            ->where('tipo_vinculo', TipoVinculo::PublicoGeneral)
            ->sole();

        Matricula::query()->whereNull('pago_id')->each(function (Matricula $matricula) use ($tarifa, $cajero): void {
            $pago = Pago::factory()->validado()->create([
                'persona_id' => $matricula->persona_id,
                'concepto_id' => $tarifa->concepto_id,
                'tarifa_id' => $tarifa->id,
                'monto' => $tarifa->monto,
                'validado_por' => $cajero->id,
            ]);

            $matricula->update(['pago_id' => $pago->id]);
        });
    }
}
