<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Database\Factories;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Models\TipoTramite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoTramite>
 */
final class TipoTramiteFactory extends Factory
{
    public const array ETAPAS_CON_TASA = [
        ['Solicitud recibida', 0, CondicionDeEtapa::Solicitud],
        ['Pago validado', 33, CondicionDeEtapa::PagoValidado],
        ['En firma de Dirección', 66, CondicionDeEtapa::AccionDelPersonal],
        ['Listo para recojo o descarga', 100, CondicionDeEtapa::AccionDelPersonal],
    ];

    public const array ETAPAS_SIN_TASA = [
        ['Solicitud recibida', 0, CondicionDeEtapa::Solicitud],
        ['En revisión', 50, CondicionDeEtapa::AccionDelPersonal],
        ['Atendido', 100, CondicionDeEtapa::AccionDelPersonal],
    ];

    protected $model = TipoTramite::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('PRUEBA-####'),
            'nombre' => 'Constancia de matrícula',
            'requisitos' => 'Una matrícula activa y el pago de la tasa.',
            'tipo_concepto' => TipoConcepto::Constancia,
            'accion_final' => AccionFinal::ConstanciaMatricula,
            'activo' => true,
        ];
    }

    public function sinTasa(): static
    {
        return $this->state(['tipo_concepto' => null]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (TipoTramite $tipo): void {
            $etapas = $tipo->cobraTasa() ? self::ETAPAS_CON_TASA : self::ETAPAS_SIN_TASA;

            foreach ($etapas as $indice => [$nombre, $avance, $condicion]) {
                $tipo->etapas()->create(['orden' => $indice + 1, 'nombre' => $nombre, 'avance' => $avance, 'condicion' => $condicion]);
            }
        });
    }
}
