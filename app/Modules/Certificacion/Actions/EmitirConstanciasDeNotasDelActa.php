<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\ResumenDeEmision;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Database\Eloquent\Builder;

final readonly class EmitirConstanciasDeNotasDelActa
{
    public function __construct(private EmitirConstanciaDeNotas $emitir) {}

    public function __invoke(int $actaId, User $emisor): ResumenDeEmision
    {
        EmisionDeDocumento::asegurarPermiso($emisor);

        // The acta row lock serializes two bulk issues of the same acta, so the second one sees the first one's records and skips them.
        return Transaccion::ejecutar(function () use ($actaId, $emisor): ResumenDeEmision {
            $acta = Acta::query()->whereKey($actaId)->lockForUpdate()->first() ?? throw new ReglaDeNegocioIncumplida('El acta final no existe.');

            if ($acta->estado !== EstadoActa::Cerrada) {
                throw new ReglaDeNegocioIncumplida("El acta final del grupo {$acta->grupo_codigo} está en rectificación; emite las constancias cuando se vuelva a cerrar.");
            }

            $estudiantes = $acta->notas()->orderBy('estudiante_nombre')->get(['persona_id', 'estudiante_nombre']);
            $conConstancia = $this->conConstanciaDesdeElCierre($acta, array_values($estudiantes->pluck('persona_id')->all()));
            $emitidos = [];
            $fallidos = [];

            foreach ($estudiantes->whereNotIn('persona_id', $conConstancia) as $estudiante) {
                try {
                    $emitidos[] = ($this->emitir)($estudiante->persona_id, $acta->idioma, $acta->nivel, $emisor);
                } catch (ReglaDeNegocioIncumplida $error) {
                    RegistroDeRechazos::registrar($error);
                    $fallidos[] = "{$estudiante->estudiante_nombre}: {$error->getMessage()}";
                }
            }

            return new ResumenDeEmision($emitidos, count($conConstancia), $fallidos);
        });
    }

    /**
     * @param  list<int>  $personas
     * @return list<int> students who already got a valid grade record of the acta's level (or of every level, as before) after this acta closed
     */
    private function conConstanciaDesdeElCierre(Acta $acta, array $personas): array
    {
        return array_values(array_unique(Documento::query()
            ->whereIn('persona_id', $personas)
            ->where(['tipo' => TipoDocumento::ConstanciaNotas, 'idioma' => $acta->idioma])
            ->where(fn (Builder $documentos): Builder => $documentos->where('nivel', $acta->nivel)->orWhereNull('nivel'))
            ->whereNull('anulado_en')
            ->where('emitido_en', '>=', $acta->validada_en)
            ->pluck('persona_id')
            ->all()));
    }
}
