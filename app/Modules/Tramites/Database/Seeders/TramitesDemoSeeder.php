<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Database\Seeders;

use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;

// Trámites in every state, made through the real Actions; the queue runs inline so their e-mails are logged now.
final class TramitesDemoSeeder extends Seeder
{
    private int $operacion = 0;

    public function run(): void
    {
        $cola = config('queue.default');
        config(['queue.default' => 'sync']);

        try {
            $this->delEstudianteDelPortal();
            $this->deLaCohorte();
        } finally {
            config(['queue.default' => $cola]);
        }
    }

    private function delEstudianteDelPortal(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $persona = Persona::query()->whereRelation('user', 'email', 'estudiante@ciln.test')->firstOrFail();
        $matricula = (int) Matricula::query()->where(['persona_id' => $persona->id, 'estado' => 'activa'])->value('id');

        $this->pagar($persona, 'CON-01', EstadoPago::Validado);
        $constancia = $this->solicitar($persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $matricula, comentario: 'La necesito para el trámite de mi beca.'));
        app(AvanzarTramite::class)($constancia->id, $administrador);

        $this->solicitar($persona, 'TRASLADO', new DatosDeSolicitud(matriculaId: $matricula, motivo: 'Mi horario de trabajo cambió al turno de la mañana.'));
    }

    private function deLaCohorte(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $cajero = User::query()->where('email', 'caja@ciln.test')->firstOrFail();
        $escenarios = ['finalizado', 'observado por el personal', 'recibo rechazado'];
        $cohorte = Persona::query()->whereNull('user_id')->orderBy('id')->get()
            ->map(fn (Persona $persona): array => [$persona, app(IdiomasConNotasCerradas::class)($persona->id)])
            ->filter(fn (array $conIdiomas): bool => $conIdiomas[1] !== [])
            ->take(count($escenarios))
            ->values();

        foreach ($cohorte as $indice => [$persona, $idiomas]) {
            $escenario = $escenarios[$indice];

            if ($escenario !== 'recibo rechazado') {
                $this->pagar($persona, 'CON-01', EstadoPago::Validado);
            }

            $tramite = $this->solicitar($persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: $idiomas[0]));

            match ($escenario) {
                'finalizado' => $this->finalizar($tramite, $administrador),
                'observado por el personal' => app(ObservarTramite::class)($tramite->id, $administrador, 'Indica la institución para la que necesitas la constancia, para consignarla en el documento.'),
                'recibo rechazado' => app(RechazarPago::class)($this->pagar($persona, 'CON-01', EstadoPago::Pendiente)->id, $cajero, 'El número de operación no corresponde a un depósito del Banco de la Nación.'),
            };
        }
    }

    private function finalizar(Tramite $tramite, User $administrador): void
    {
        app(AvanzarTramite::class)($tramite->id, $administrador);
        app(FinalizarTramite::class)($tramite->id, $administrador);
    }

    private function solicitar(Persona $persona, string $codigo, DatosDeSolicitud $datos): Tramite
    {
        return app(SolicitarTramite::class)($persona->id, (int) TipoTramite::query()->where('codigo', $codigo)->value('id'), $datos);
    }

    private function pagar(Persona $persona, string $codigo, EstadoPago $estado): Pago
    {
        $tarifa = Tarifa::query()->whereRelation('concepto', 'codigo', $codigo)->where('tipo_vinculo', $persona->tipo_vinculo)->sole();

        return Pago::factory()->create([
            'persona_id' => $persona->id, 'concepto_id' => $tarifa->concepto_id, 'tarifa_id' => $tarifa->id, 'monto' => $tarifa->monto,
            'numero_operacion' => sprintf('%08d', 59310000 + $this->operacion++), 'fecha_pago' => now('America/Lima')->toDateString(),
            'estado' => $estado, ...($estado === EstadoPago::Validado ? $this->revision() : []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function revision(): array
    {
        $cajero = User::query()->where('email', 'caja@ciln.test')->value('id');

        return ['validado_por' => $cajero, 'validado_en' => now(), 'boleta_original_verificada_por' => $cajero, 'boleta_original_verificada_en' => now()];
    }
}
