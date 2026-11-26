<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Pagos\Contracts\CatalogoDeConceptos;
use App\Modules\Pagos\Data\ConceptoDelCatalogo;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Data\DatosDeTipoTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Support\ConfiguracionDelFut;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;

// Trámites already requested keep their own copy of the stages and data, so replacing them here only affects new requests.
final readonly class GuardarTipoDeTramite
{
    public const array CONCEPTOS = [TipoConcepto::Constancia, TipoConcepto::Certificado, TipoConcepto::Tramite, TipoConcepto::Otro];

    public function __construct(private CatalogoDeConceptos $conceptos, private CatalogoDeProgramas $programas) {}

    public function __invoke(DatosDeTipoTramite $datos, User $usuario, ?int $tipoId = null): TipoTramite
    {
        throw_unless($usuario->can(PermisoTramite::Configurar->value), ReglaDeNegocioIncumplida::class, 'No tienes permiso para configurar los trámites.');
        $datos = $this->conTipoDelConcepto($datos);
        throw_if($datos->tipoConcepto instanceof TipoConcepto && ! in_array($datos->tipoConcepto, self::CONCEPTOS, true), ReglaDeNegocioIncumplida::class, 'La tasa de un trámite es de constancia, certificado, trámite u otro concepto.');
        ReglasDeEtapas::validar($datos->etapas, $datos->tipoConcepto !== null);
        $campos = ConfiguracionDelFut::campos($datos->campos, $datos->accionFinal);
        $adjuntos = ConfiguracionDelFut::adjuntos($datos->adjuntos);
        $this->asegurarProgramas($datos->programaIds);
        $this->asegurarTipoPrevio($datos->tipoPrevioId, $tipoId);
        throw_if($datos->mesesSinEstudiar !== null && ($datos->mesesSinEstudiar < 1 || $datos->mesesSinEstudiar > 24), ReglaDeNegocioIncumplida::class, 'Los meses sin estudiar para la reclasificación van de 1 a 24.');

        try {
            return Transaccion::ejecutar(fn (): TipoTramite => $this->guardar($datos, $tipoId, $campos, $adjuntos));
        } catch (UniqueConstraintViolationException) {
            throw new ReglaDeNegocioIncumplida('Ya existe un tipo de trámite con el código '.TipoTramite::normalizarCodigo($datos->codigo).'.');
        }
    }

    /**
     * @param  list<array{clave: string, obligatorio: bool}>  $campos
     * @param  list<array{nombre: string, obligatorio: bool}>  $adjuntos
     */
    private function guardar(DatosDeTipoTramite $datos, ?int $tipoId, array $campos, array $adjuntos): TipoTramite
    {
        $tipo = $tipoId === null ? new TipoTramite : TipoTramite::query()->whereKey($tipoId)->lockForUpdate()->firstOrFail();

        $tipo->fill([
            'codigo' => $datos->codigo,
            'nombre' => trim($datos->nombre),
            'requisitos' => $datos->requisitos === null || trim($datos->requisitos) === '' ? null : trim($datos->requisitos),
            'tipo_concepto' => $datos->tipoConcepto,
            'concepto_id' => $datos->conceptoId,
            'accion_final' => $datos->accionFinal,
            'activo' => $datos->activo,
            'campos' => $campos,
            'adjuntos' => $adjuntos,
            'requiere_tipo_previo_id' => $datos->tipoPrevioId,
            'meses_sin_estudiar' => $datos->accionFinal === AccionFinal::Reclasificacion ? $datos->mesesSinEstudiar : null,
        ])->save();

        $tipo->programas()->whereNotIn('programa_id', $datos->programaIds)->delete();

        foreach (array_unique($datos->programaIds) as $programaId) {
            $tipo->programas()->firstOrCreate(['programa_id' => $programaId]);
        }

        $tipo->etapas()->delete();

        foreach ($datos->etapas as $indice => $etapa) {
            $tipo->etapas()->create(['orden' => $indice + 1, 'nombre' => trim($etapa['nombre']), 'avance' => (int) $etapa['avance'], 'condicion' => $etapa['condicion']]);
        }

        return $tipo->load('etapas');
    }

    /**
     * @param  list<int>  $programaIds
     */
    private function asegurarProgramas(array $programaIds): void
    {
        $existentes = array_map(fn (ProgramaDelCatalogo $programa): int => $programa->id, ($this->programas)());

        throw_if(array_diff($programaIds, $existentes) !== [], ReglaDeNegocioIncumplida::class, 'Elige programas del catálogo.');
    }

    private function asegurarTipoPrevio(?int $tipoPrevioId, ?int $tipoId): void
    {
        $valido = $tipoPrevioId === null || ($tipoPrevioId !== $tipoId && TipoTramite::query()->whereKey($tipoPrevioId)->exists());

        throw_unless($valido, ReglaDeNegocioIncumplida::class, 'El trámite previo debe ser otro tipo de trámite.');
    }

    // A named concept fixes the fee type, so the type and the concept can never disagree.
    private function conTipoDelConcepto(DatosDeTipoTramite $datos): DatosDeTipoTramite
    {
        if ($datos->conceptoId === null) {
            return $datos;
        }

        $concepto = collect(($this->conceptos)())->first(fn (ConceptoDelCatalogo $candidato): bool => $candidato->id === $datos->conceptoId);

        throw_unless(
            $concepto instanceof ConceptoDelCatalogo && $concepto->activo && in_array($concepto->tipo, self::CONCEPTOS, true),
            ReglaDeNegocioIncumplida::class,
            'El concepto de pago debe estar activo y ser de constancia, certificado, trámite u otro.',
        );

        return $datos->conConcepto($concepto->id, $concepto->tipo);
    }
}
