<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class RegistrarNotasParciales
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EsquemaDelGrupo $esquemaDelGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    /**
     * @param  array<int, mixed>  $notas  grades per enrollment id, each an array per component id; blank leaves it empty
     */
    public function __invoke(int $grupoId, int $docenteId, array $notas, User $usuario): void
    {
        Transaccion::ejecutar(function () use ($grupoId, $docenteId, $notas, $usuario): void {
            // Same lock as assignment and unassignment, so a teacher removed from the group cannot save after that change commits.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            GrupoACargo::asegurar($grupo, $docenteId, 'las notas parciales solo se registran');
            $esquema = ($this->esquemaDelGrupo)($grupoId)
                ?? throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.");

            $valores = $this->validar($notas, ($this->estudiantesDelGrupo)($grupoId), $esquema);
            $existentes = NotaParcial::query()->where('grupo_id', $grupoId)->get()
                ->keyBy(fn (NotaParcial $nota): string => "{$nota->matricula_id}.{$nota->componente_id}");

            foreach ($valores as [$matriculaId, $componenteId, $nota]) {
                $registro = $existentes["{$matriculaId}.{$componenteId}"] ?? new NotaParcial(['grupo_id' => $grupoId, 'matricula_id' => $matriculaId, 'componente_id' => $componenteId]);
                $this->guardar($registro, $nota, $usuario);
            }
        });
    }

    /**
     * @param  array<int, mixed>  $notas
     * @param  list<EstudianteDelGrupo>  $estudiantes
     * @return list<array{int, int, ?string}>
     */
    private function validar(array $notas, array $estudiantes, EsquemaDeEvaluacion $esquema): array
    {
        $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, $estudiantes);
        $componentes = array_map(fn (ComponenteDeEvaluacion $componente): int => $componente->id, $esquema->componentes);
        $valores = [];

        if (array_diff(array_keys($notas), $matriculas) !== []) {
            throw new ReglaDeNegocioIncumplida('La lista de estudiantes del grupo cambió; vuelve a cargarla.');
        }

        foreach ($notas as $matriculaId => $porComponente) {
            if (! is_array($porComponente) || array_diff(array_keys($porComponente), $componentes) !== []) {
                throw new ReglaDeNegocioIncumplida('El sistema de evaluación del grupo cambió; vuelve a cargar la página.');
            }

            foreach ($porComponente as $componenteId => $nota) {
                $valores[] = [$matriculaId, $componenteId, $this->nota($nota, $esquema->notaMaxima)];
            }
        }

        return $valores;
    }

    private function nota(mixed $valor, string $maxima): ?string
    {
        $texto = is_string($valor) || is_int($valor) ? str_replace(',', '.', trim((string) $valor)) : '';

        if ($valor === null || (is_string($valor) && $texto === '')) {
            return null;
        }

        if (preg_match('/^\d{1,2}(\.\d{1,2})?\z/', $texto) !== 1 || $this->centesimos($texto) > $this->centesimos($maxima)) {
            $limite = rtrim(rtrim($maxima, '0'), '.');

            throw new ReglaDeNegocioIncumplida("La nota {$texto} no es válida: usa un número de 0 a {$limite} con hasta dos decimales.");
        }

        return number_format($this->centesimos($texto) / 100, 2, '.', '');
    }

    private function centesimos(string $numero): int
    {
        [$entero, $decimales] = explode('.', $numero.'.');

        return (int) $entero * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0');
    }

    private function guardar(NotaParcial $registro, ?string $nota, User $usuario): void
    {
        // Unchanged grades are not rewritten, so the audit trail only shows real changes.
        if ((! $registro->exists && $nota === null) || ($registro->exists && $registro->nota === $nota)) {
            return;
        }

        $registro->fill(['nota' => $nota, 'registrado_por' => $usuario->id])->save();
    }
}
