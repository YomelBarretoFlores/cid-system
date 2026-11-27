<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Shared\Support\Notas\Fraccion;
use App\Shared\Support\Notas\Nota;
use LogicException;

// Applies a scheme's columns to one student's entered grades: blanks count as zero, and nothing is computed when no grade was entered.
final readonly class NotasCalculadas
{
    /**
     * @param  array<string, ?string>  $valores  value of every column by code, with two decimals
     * @param  array<int, string>  $columnaDelCiclo  code of the column that is the grade of each cycle
     */
    private function __construct(public bool $sinNotas, public array $valores, private array $columnaDelCiclo) {}

    /**
     * @param  list<ComponenteDeEvaluacion>  $componentes  in the order of the scheme; a calculated column only uses earlier ones
     * @param  array<int, string>  $notas  entered grade per column id, blanks left out
     */
    public static function de(array $componentes, array $notas): self
    {
        $ciclos = [];

        foreach ($componentes as $componente) {
            if ($componente->ciclo !== null) {
                $ciclos[$componente->ciclo] = $componente->codigo;
            }
        }

        if (! array_any($componentes, fn (ComponenteDeEvaluacion $componente): bool => $componente->esIngresada() && isset($notas[$componente->id]))) {
            return new self(true, array_fill_keys(array_map(fn (ComponenteDeEvaluacion $componente): string => $componente->codigo, $componentes), null), $ciclos);
        }

        $exactos = [];

        foreach ($componentes as $componente) {
            $exactos[$componente->codigo] = $componente->esIngresada()
                ? Fraccion::entero(Nota::centesimos($notas[$componente->id] ?? '0'))
                : self::calcular($componente, $exactos);
        }

        return new self(false, array_map(fn (Fraccion $valor): string => Nota::deCentesimos($valor->redondeadaA(1)), $exactos), $ciclos);
    }

    public function valor(string $codigo): ?string
    {
        return $this->valores[$codigo] ?? null;
    }

    public function delCiclo(int $ciclo): ?string
    {
        return isset($this->columnaDelCiclo[$ciclo]) ? $this->valor($this->columnaDelCiclo[$ciclo]) : null;
    }

    public function tieneCiclo(int $ciclo): bool
    {
        return isset($this->columnaDelCiclo[$ciclo]);
    }

    /**
     * @param  array<string, Fraccion>  $anteriores
     */
    private static function calcular(ComponenteDeEvaluacion $componente, array $anteriores): Fraccion
    {
        [$suma, $pesos] = [Fraccion::entero(0), 0];

        foreach ($componente->fuentes as $fuente) {
            $peso = $componente->calculo === CalculoDeComponente::Ponderado ? $fuente['peso'] : 1;
            $valor = $anteriores[$fuente['codigo']] ?? throw new LogicException("La columna «{$componente->codigo}» usa «{$fuente['codigo']}», que no está antes.");
            [$suma, $pesos] = [$suma->mas($valor->por($peso)), $pesos + $peso];
        }

        $promedio = $suma->entre($pesos);

        return $componente->redondeo === RedondeoDeNota::Entero ? Fraccion::entero($promedio->redondeadaA(100)) : $promedio;
    }
}
