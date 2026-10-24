<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final readonly class SolicitarExamenDeClasificacion
{
    private const int DIAS_DE_VIGENCIA = 30;

    public function __construct(
        private VerificacionDePago $verificacionDePago,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
    ) {}

    public function __invoke(int $personaId, int $idiomaId, int $pagoId): SolicitudClasificacion
    {
        $idioma = $this->idiomaDisponible($idiomaId);

        // Same order as enrollment: person, then payment.
        return Transaccion::ejecutar(function () use ($personaId, $idioma, $pagoId): SolicitudClasificacion {
            MovimientoDeMatricula::bloquearPersona($personaId);
            ($this->verificacionDePago)($pagoId, $personaId, TipoConcepto::ExamenClasificacion);

            throw_if(SolicitudClasificacion::query()->where('pago_id', $pagoId)->exists(), ReglaDeNegocioIncumplida::class, 'El pago ya se usó en otra solicitud.');

            $pendiente = SolicitudClasificacion::query()
                ->where(['persona_id' => $personaId, 'idioma_id' => $idioma->id, 'estado' => EstadoSolicitudClasificacion::PendienteEvaluacion])
                ->exists();

            throw_if($pendiente, ReglaDeNegocioIncumplida::class, "Ya tiene una solicitud pendiente de evaluación para {$idioma->nombre}.");

            return SolicitudClasificacion::query()->create([
                'persona_id' => $personaId,
                'idioma_id' => $idioma->id,
                'pago_id' => $pagoId,
                'vence_en' => CarbonImmutable::today('America/Lima')->addDays(self::DIAS_DE_VIGENCIA)->toDateString(),
            ]);
        });
    }

    private function idiomaDisponible(int $idiomaId): IdiomaDelCatalogo
    {
        $idioma = collect(($this->catalogoDeIdiomas)())->first(fn (IdiomaDelCatalogo $idioma): bool => $idioma->id === $idiomaId);

        return $idioma instanceof IdiomaDelCatalogo && $idioma->activo
            ? $idioma
            : throw new ReglaDeNegocioIncumplida('El idioma no está disponible para el examen de clasificación.');
    }
}
