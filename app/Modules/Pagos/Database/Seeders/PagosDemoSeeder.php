<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Database\Seeders;

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// Payment dates are Lima dates no later than today, so the collection report of the current month has data.
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
        'CON-01' => ['Constancia', TipoConcepto::Constancia, ['estudiante_unasam' => '20.00', 'docente_administrativo_unasam' => '20.00', 'publico_general' => '25.00']],
        'CER-01' => ['Certificado de nivel', TipoConcepto::Certificado, ['estudiante_unasam' => '60.00', 'docente_administrativo_unasam' => '70.00', 'publico_general' => '90.00']],
        'TRA-01' => ['Trámite académico', TipoConcepto::Tramite, ['estudiante_unasam' => '15.00', 'docente_administrativo_unasam' => '15.00', 'publico_general' => '20.00']],
    ];

    private const array MEDIOS = [MedioPago::BancoNacion, MedioPago::PagaloPe, MedioPago::CajaPresencial];

    private int $secuencia = 0;

    public function run(): void
    {
        foreach (self::CONCEPTOS as $codigo => [$nombre, $tipo, $montos]) {
            $concepto = Concepto::query()->create(['codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'fuente' => FuenteConcepto::Tupa]);

            foreach ($montos as $vinculo => $monto) {
                $concepto->tarifas()->create(['tipo_vinculo' => TipoVinculo::from($vinculo), 'monto' => $monto, 'vigente_desde' => '2026-01-01']);
            }
        }

        $cajero = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $hoy = CarbonImmutable::today('America/Lima');

        Matricula::query()->whereNull('pago_id')->lazyById()->each(function (Matricula $matricula) use ($cajero, $hoy): void {
            $fecha = min($hoy, CarbonImmutable::parse($matricula->matriculado_en->timezone('America/Lima')->toDateString())->subDay());
            $matricula->update(['pago_id' => $this->pagar($matricula->persona_id, 'MAT-01', $fecha, EstadoPago::Validado, $cajero)->id]);
        });

        SolicitudClasificacion::query()->whereNull('pago_id')->lazyById()->each(function (SolicitudClasificacion $solicitud) use ($cajero, $hoy): void {
            $solicitud->update(['pago_id' => $this->pagar($solicitud->persona_id, 'EXC-01', $hoy->subDays(6), EstadoPago::Validado, $cajero)->id]);
        });

        $this->pensionesDelMes($cajero, $hoy);
        $this->pagosPorRevisar($cajero, $hoy);
    }

    private function pensionesDelMes(User $cajero, CarbonImmutable $hoy): void
    {
        $inicio = $hoy->startOfMonth();
        $personas = Matricula::query()->where('estado', 'activa')->orderBy('id')->limit(14)->pluck('persona_id')->unique();

        foreach ($personas->values() as $indice => $personaId) {
            $this->pagar($personaId, 'PEN-01', min($hoy, $inicio->addDays($indice * 2 % max(1, $hoy->day))), EstadoPago::Validado, $cajero);
        }
    }

    // Recent payments the cashier still has to review, and one already rejected.
    private function pagosPorRevisar(User $cajero, CarbonImmutable $hoy): void
    {
        $personas = Persona::query()->whereNull('user_id')->orderByDesc('id')->limit(4)->pluck('id');

        foreach ([['MAT-01', 0], ['MAT-01', 1], ['PEN-01', 2]] as [$concepto, $dias]) {
            $this->pagar($personas[$dias], $concepto, $hoy->subDays($dias), EstadoPago::Pendiente, $cajero);
        }

        $this->pagar($personas[3], 'PEN-01', $hoy->subDays(4), EstadoPago::Rechazado, $cajero);
    }

    private function pagar(int $personaId, string $codigo, CarbonImmutable $fecha, EstadoPago $estado, User $cajero): Pago
    {
        $vinculo = Persona::query()->whereKey($personaId)->value('tipo_vinculo');
        $tarifa = Tarifa::query()->whereRelation('concepto', 'codigo', $codigo)->where('tipo_vinculo', $vinculo)->sole();
        $revisado = $estado === EstadoPago::Pendiente ? [] : ['validado_por' => $cajero->id, 'validado_en' => min(now(), $fecha->setTime(17, 0))];

        return Pago::factory()->create([
            'persona_id' => $personaId, 'concepto_id' => $tarifa->concepto_id, 'tarifa_id' => $tarifa->id, 'monto' => $tarifa->monto,
            'medio_pago' => self::MEDIOS[$this->secuencia % 3], 'numero_operacion' => sprintf('%08d', 48210000 + $this->secuencia++),
            'fecha_pago' => $fecha->toDateString(), 'estado' => $estado,
            'motivo_rechazo' => $estado === EstadoPago::Rechazado ? 'El monto no coincide con el recibo.' : null,
            ...$revisado,
        ]);
    }
}
