<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Database\Seeders;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Contracts\NivelesConNotas;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeResolucion;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Trámites in every state, made through the real Actions; the queue runs inline so their e-mails are logged now.
// Also a passed proficiency exam and the accreditation of a Posgrado student, so the FUT procedures show their outcome.
final class TramitesDemoSeeder extends Seeder
{
    private int $operacion = 0;

    public function run(): void
    {
        $cola = config('queue.default');
        config(['queue.default' => 'sync']);

        $this->vincularConceptos();

        try {
            $this->delEstudianteDelPortal();
            $this->deLaCohorte();
            $this->procedimientosDelFut();
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
            ->map(fn (Persona $persona): array => [$persona, app(NivelesConNotas::class)($persona->id)])
            ->filter(fn (array $conNiveles): bool => $conNiveles[1] !== [])
            ->take(count($escenarios))
            ->values();

        foreach ($cohorte as $indice => [$persona, $niveles]) {
            $escenario = $escenarios[$indice];

            if ($escenario !== 'recibo rechazado') {
                $this->pagar($persona, 'CON-NOT', EstadoPago::Validado, $niveles[0]->nivel->value);
            }

            $tramite = $this->solicitar($persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: $niveles[0]->idioma, nivel: $niveles[0]->nivel->value));

            match ($escenario) {
                'finalizado' => $this->finalizar($tramite, $administrador),
                'observado por el personal' => app(ObservarTramite::class)($tramite->id, $administrador, 'Indica la institución para la que necesitas la constancia, para consignarla en el documento.'),
                'recibo rechazado' => app(RechazarPago::class)($this->pagar($persona, 'CON-NOT', EstadoPago::Pendiente, $niveles[0]->nivel->value)->id, $cajero, 'El número de operación no corresponde a un depósito del Banco de la Nación.'),
            };
        }
    }

    private function procedimientosDelFut(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();
        $conNiveles = Persona::query()->whereNull('user_id')->orderBy('id')->get()
            ->map(fn (Persona $persona): array => [$persona, app(NivelesConNotas::class)($persona->id)])
            ->filter(fn (array $persona): bool => $persona[1] !== [])
            ->values();
        $posgrado = $conNiveles->get(3) ?? $conNiveles->last();

        if ($posgrado !== null) {
            $this->acreditacion($posgrado[0], $posgrado[1][0], $administrador);
        }

        $frances = Grupo::query()->whereRelation('ciclo.idioma', 'codigo', 'FRA')->pluck('id');
        $sinFrances = Persona::query()->whereNull('user_id')
            ->whereIn('id', Matricula::query()->select('persona_id'))
            ->whereNotIn('id', Matricula::query()->whereIn('grupo_id', $frances)->select('persona_id'))
            ->orderBy('id')
            ->first();

        if ($sinFrances !== null) {
            $this->pagar($sinFrances, 'SUF-01', EstadoPago::Validado);
            $suficiencia = $this->solicitar($sinFrances, 'SUFICIENCIA', new DatosDeSolicitud(idioma: 'Francés', nivel: NivelCurricular::Basico->value));
            $this->finalizar($suficiencia, $administrador, new DatosDeResolucion(nota: '16'));
        }
    }

    // A student of the cohort also enrolled in the next Posgrado group (Portuguese, accelerated) accredits a level already passed.
    private function acreditacion(Persona $persona, NivelPorCertificar $nivel, User $administrador): void
    {
        $grupo = Grupo::query()->where('codigo', 'POR-2-T')->whereRelation('programa', 'codigo', 'POSGRADO')->first();

        if ($grupo === null) {
            return;
        }

        Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id, 'pago_id' => $this->pagar($persona, 'MAT-01', EstadoPago::Validado)->id, 'matriculado_en' => now()]);
        DB::table('aca_grupos')->where('id', $grupo->id)->increment('matriculados');

        $this->pagar($persona, 'ACR-PG', EstadoPago::Validado, programa: 'POSGRADO');
        $tramite = $this->solicitar($persona, 'ACREDITACION', new DatosDeSolicitud(idioma: $nivel->idioma, nivel: $nivel->nivel->value, mencionGrado: 'maestria', mencionTexto: 'Gestión Educativa'));
        $this->finalizar($tramite, $administrador);
    }

    private function finalizar(Tramite $tramite, User $administrador, DatosDeResolucion $resolucion = new DatosDeResolucion): void
    {
        app(AvanzarTramite::class)($tramite->id, $administrador);
        app(FinalizarTramite::class)($tramite->id, $administrador, resolucion: $resolucion);
    }

    private function solicitar(Persona $persona, string $codigo, DatosDeSolicitud $datos): Tramite
    {
        return app(SolicitarTramite::class)($persona->id, (int) TipoTramite::query()->where('codigo', $codigo)->value('id'), $datos);
    }

    // Each type takes only a payment of its own concept, whose type becomes the fee type. The reclassification keeps fee type
    // "otro": its concept (EXC-01) is an exam concept, which a trámite type cannot take.
    private function vincularConceptos(): void
    {
        $vinculos = ['CONSTANCIA-NOTAS' => 'CON-NOT', 'CERTIFICADO-NIVEL' => 'CER-01', 'SUFICIENCIA' => 'SUF-01', 'ACREDITACION' => 'ACR-PG', 'CERTIFICADO-POSGRADO' => 'CER-01'];

        foreach ($vinculos as $tipo => $codigo) {
            $concepto = Concepto::query()->where('codigo', $codigo)->firstOrFail();
            TipoTramite::query()->where('codigo', $tipo)->update(['concepto_id' => $concepto->id, 'tipo_concepto' => $concepto->tipo]);
        }
    }

    private function pagar(Persona $persona, string $codigo, EstadoPago $estado, ?string $nivel = null, ?string $programa = null): Pago
    {
        $tarifa = Tarifa::query()->whereRelation('concepto', 'codigo', $codigo)->where(['tipo_vinculo' => $persona->tipo_vinculo, 'nivel' => $nivel])
            ->when($programa === null, fn ($tarifas) => $tarifas->whereNull('programa_id'), fn ($tarifas) => $tarifas->whereIn('programa_id', DB::table('aca_programas')->where('codigo', $programa)->select('id')))
            ->sole();

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
