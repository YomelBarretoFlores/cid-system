<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Programa;
use App\Modules\Academico\Support\ComponentesDelEsquema;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A scheme in force may already grade any group, so from its start only its name and its end date change; a new
 * scheme takes over from the day after that end. Schemes of one language, level, program and regime never overlap.
 */
final readonly class GuardarEsquemaDeEvaluacion
{
    public function __invoke(DatosDeEsquema $datos, ?int $esquemaId = null): EsquemaEvaluacion
    {
        $componentes = ComponentesDelEsquema::validar($datos->componentes, $datos->regimen);
        [$maxima, $minima] = $this->notas($datos);
        $this->asegurarProgramaCompatible($datos);

        if ($datos->vigenteHasta instanceof CarbonImmutable && $datos->vigenteHasta->lt($datos->vigenteDesde)) {
            throw new ReglaDeNegocioIncumplida('La vigencia debe terminar en o después de su inicio.');
        }

        return Transaccion::ejecutar(function () use ($datos, $esquemaId, $componentes, $maxima, $minima): EsquemaEvaluacion {
            // Two saves for one scope run one after the other, so neither misses the other's validity.
            DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['aca_esquemas:'.implode(':', $this->alcance($datos))]);
            $esquema = $esquemaId === null ? new EsquemaEvaluacion : EsquemaEvaluacion::query()->whereKey($esquemaId)->lockForUpdate()->firstOrFail();
            $this->asegurarSinCruce($datos, $esquemaId);

            if ($esquema->exists && $esquema->vigente_desde->lte(CarbonImmutable::today('America/Lima'))) {
                return $this->cerrarOrenombrar($esquema, $datos, $componentes, [$maxima, $minima]);
            }

            $esquema->fill([
                'nombre' => trim($datos->nombre), 'idioma_id' => $datos->idiomaId, 'nivel' => $datos->nivel,
                'programa_id' => $datos->programaId, 'regimen' => $datos->regimen, 'nota_maxima' => $maxima,
                'nota_minima_aprobatoria' => $minima, 'vigente_desde' => $datos->vigenteDesde, 'vigente_hasta' => $datos->vigenteHasta,
            ])->save();
            $esquema->componentes()->delete();
            $esquema->componentes()->createMany($componentes);

            return $esquema;
        });
    }

    /**
     * @return array{numeric-string, numeric-string}
     */
    private function notas(DatosDeEsquema $datos): array
    {
        $maxima = ComponentesDelEsquema::decimal($datos->notaMaxima);
        $minima = ComponentesDelEsquema::decimal($datos->notaMinimaAprobatoria);

        if (bccomp($maxima, '0', 2) <= 0 || bccomp($maxima, '99.99', 2) > 0) {
            throw new ReglaDeNegocioIncumplida('La nota máxima debe estar entre 0 y 99.99.');
        }

        if (bccomp($minima, '0', 2) <= 0 || bccomp($minima, $maxima, 2) > 0) {
            throw new ReglaDeNegocioIncumplida('La nota mínima aprobatoria debe estar entre 0 y la nota máxima.');
        }

        return [$maxima, $minima];
    }

    private function asegurarProgramaCompatible(DatosDeEsquema $datos): void
    {
        $programa = $datos->programaId === null ? null : Programa::query()->find($datos->programaId);

        if ($programa !== null && $datos->regimen instanceof RegimenDeEsquema && ! $programa->regimen->admiteGrupo($datos->regimen->esAcelerado())) {
            throw new ReglaDeNegocioIncumplida("El programa {$programa->nombre} no tiene grupos de régimen {$datos->regimen->etiqueta()}.");
        }
    }

    /**
     * @return list<string>
     */
    private function alcance(DatosDeEsquema $datos): array
    {
        return [(string) ($datos->idiomaId ?? '-'), $datos->nivel->value ?? '-', (string) ($datos->programaId ?? '-'), $datos->regimen->value ?? '-'];
    }

    private function asegurarSinCruce(DatosDeEsquema $datos, ?int $esquemaId): void
    {
        $otro = EsquemaEvaluacion::query()
            ->when($esquemaId !== null, fn (Builder $query): Builder => $query->whereKeyNot($esquemaId))
            ->when($datos->idiomaId === null, fn (Builder $query): Builder => $query->whereNull('idioma_id'), fn (Builder $query): Builder => $query->where('idioma_id', $datos->idiomaId))
            ->when(! $datos->nivel instanceof NivelCurricular, fn (Builder $query): Builder => $query->whereNull('nivel'), fn (Builder $query): Builder => $query->where('nivel', $datos->nivel))
            ->when($datos->programaId === null, fn (Builder $query): Builder => $query->whereNull('programa_id'), fn (Builder $query): Builder => $query->where('programa_id', $datos->programaId))
            ->when(! $datos->regimen instanceof RegimenDeEsquema, fn (Builder $query): Builder => $query->whereNull('regimen'), fn (Builder $query): Builder => $query->where('regimen', $datos->regimen))
            ->when($datos->vigenteHasta instanceof CarbonImmutable, fn (Builder $query): Builder => $query->where('vigente_desde', '<=', $datos->vigenteHasta))
            ->where(fn (Builder $query): Builder => $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $datos->vigenteDesde))
            ->first();

        if ($otro !== null) {
            throw new ReglaDeNegocioIncumplida("Se cruza con la vigencia del sistema «{$otro->nombre}»; cierra su vigencia antes de crear otro.");
        }
    }

    /**
     * @param  list<array{codigo: string, nombre: string, tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>, ciclo: ?int, orden: int}>  $componentes
     * @param  array{numeric-string, numeric-string}  $notas
     */
    private function cerrarOrenombrar(EsquemaEvaluacion $esquema, DatosDeEsquema $datos, array $componentes, array $notas): EsquemaEvaluacion
    {
        $guardados = $esquema->componentes()->get()->map(fn (ComponenteEvaluacion $componente): array => [
            'codigo' => $componente->codigo, 'nombre' => $componente->nombre, 'tipo' => $componente->tipo->value, 'calculo' => $componente->calculo?->value,
            'redondeo' => $componente->redondeo->value, 'fuentes' => $componente->fuentes, 'ciclo' => $componente->ciclo, 'orden' => $componente->orden,
        ])->all();
        $mismoContenido = $guardados == $componentes
            && [$esquema->idioma_id, $esquema->nivel, $esquema->programa_id, $esquema->regimen, $esquema->nota_maxima, $esquema->nota_minima_aprobatoria, $esquema->vigente_desde->toDateString()]
            === [$datos->idiomaId, $datos->nivel, $datos->programaId, $datos->regimen, $notas[0], $notas[1], $datos->vigenteDesde->toDateString()];

        if (! $mismoContenido) {
            throw new ReglaDeNegocioIncumplida("El sistema «{$esquema->nombre}» ya está vigente; para cambiar componentes o notas, cierra su vigencia y crea uno nuevo.");
        }

        $hoy = CarbonImmutable::today('America/Lima');

        // Moving the end into the past would leave days already graded without a scheme; an end already past stays as it is.
        $cambiaElFin = $datos->vigenteHasta?->toDateString() !== $esquema->vigente_hasta?->toDateString();

        if ($cambiaElFin && $datos->vigenteHasta instanceof CarbonImmutable && $datos->vigenteHasta->toDateString() < $hoy->toDateString()) {
            throw new ReglaDeNegocioIncumplida("Un sistema vigente no puede terminar antes de hoy ({$hoy->format('d/m/Y')}).");
        }

        $esquema->update(['nombre' => trim($datos->nombre), 'vigente_hasta' => $datos->vigenteHasta]);

        return $esquema;
    }
}
