<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Data\DatosDeTipoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;

// Trámites already requested keep their own copy of the stages, so replacing them here only affects new requests.
final readonly class GuardarTipoDeTramite
{
    public const array CONCEPTOS = [TipoConcepto::Constancia, TipoConcepto::Certificado, TipoConcepto::Tramite, TipoConcepto::Otro];

    public function __invoke(DatosDeTipoTramite $datos, User $usuario, ?int $tipoId = null): TipoTramite
    {
        throw_unless($usuario->can(PermisoTramite::Configurar->value), ReglaDeNegocioIncumplida::class, 'No tienes permiso para configurar los trámites.');
        throw_if($datos->tipoConcepto instanceof TipoConcepto && ! in_array($datos->tipoConcepto, self::CONCEPTOS, true), ReglaDeNegocioIncumplida::class, 'La tasa de un trámite es de constancia, certificado, trámite u otro concepto.');
        ReglasDeEtapas::validar($datos->etapas, $datos->tipoConcepto !== null);

        try {
            return Transaccion::ejecutar(fn (): TipoTramite => $this->guardar($datos, $tipoId));
        } catch (UniqueConstraintViolationException) {
            throw new ReglaDeNegocioIncumplida('Ya existe un tipo de trámite con el código '.TipoTramite::normalizarCodigo($datos->codigo).'.');
        }
    }

    private function guardar(DatosDeTipoTramite $datos, ?int $tipoId): TipoTramite
    {
        $tipo = $tipoId === null ? new TipoTramite : TipoTramite::query()->whereKey($tipoId)->lockForUpdate()->firstOrFail();

        $tipo->fill([
            'codigo' => $datos->codigo,
            'nombre' => trim($datos->nombre),
            'requisitos' => $datos->requisitos === null || trim($datos->requisitos) === '' ? null : trim($datos->requisitos),
            'tipo_concepto' => $datos->tipoConcepto,
            'accion_final' => $datos->accionFinal,
            'activo' => $datos->activo,
        ])->save();

        $tipo->etapas()->delete();

        foreach ($datos->etapas as $indice => $etapa) {
            $tipo->etapas()->create(['orden' => $indice + 1, 'nombre' => trim($etapa['nombre']), 'avance' => (int) $etapa['avance'], 'condicion' => $etapa['condicion']]);
        }

        return $tipo->load('etapas');
    }
}
