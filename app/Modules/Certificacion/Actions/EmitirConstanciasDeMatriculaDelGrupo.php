<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\ResumenDeEmision;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Support\Facades\DB;

final readonly class EmitirConstanciasDeMatriculaDelGrupo
{
    public function __construct(private EmitirConstanciaDeMatricula $emitir, private EstudiantesDelGrupo $estudiantesDelGrupo) {}

    public function __invoke(int $grupoId, User $emisor): ResumenDeEmision
    {
        EmisionDeDocumento::asegurarPermiso($emisor);

        return Transaccion::ejecutar(function () use ($grupoId, $emisor): ResumenDeEmision {
            // The group belongs to Academico, so two bulk issues of the same group queue on an advisory lock and the second one skips the first one's records.
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ["cer_constancias_matricula:grupo:{$grupoId}"]);

            $estudiantes = collect(($this->estudiantesDelGrupo)($grupoId));
            $conConstancia = $this->conConstanciaVigente(array_values($estudiantes->pluck('matriculaId')->all()));
            $emitidos = [];
            $fallidos = [];

            foreach ($estudiantes->whereNotIn('matriculaId', $conConstancia) as $estudiante) {
                try {
                    $emitidos[] = ($this->emitir)($estudiante->matriculaId, $emisor);
                } catch (ReglaDeNegocioIncumplida $error) {
                    RegistroDeRechazos::registrar($error);
                    $fallidos[] = "{$estudiante->nombreCompleto}: {$error->getMessage()}";
                }
            }

            return new ResumenDeEmision($emitidos, count($conConstancia), $fallidos);
        });
    }

    /**
     * @param  list<int>  $matriculas
     * @return list<int> enrollments that already have a valid enrollment record
     */
    private function conConstanciaVigente(array $matriculas): array
    {
        return array_values(array_unique(array_map(intval(...), Documento::query()
            ->whereIn('matricula_id', $matriculas)
            ->where('tipo', TipoDocumento::ConstanciaMatricula)
            ->whereNull('anulado_en')
            ->pluck('matricula_id')
            ->all())));
    }
}
