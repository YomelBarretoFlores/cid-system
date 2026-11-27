<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;

final readonly class ComponenteDeEvaluacion
{
    /**
     * @param  list<array{codigo: string, peso: int}>  $fuentes  earlier columns a calculated column combines
     * @param  ?int  $ciclo  1 when it is the grade of the group's cycle, 2 for the second cycle of an accelerated group
     */
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public TipoDeComponente $tipo = TipoDeComponente::Ingresada,
        public ?CalculoDeComponente $calculo = null,
        public RedondeoDeNota $redondeo = RedondeoDeNota::Ninguno,
        public array $fuentes = [],
        public ?int $ciclo = null,
    ) {}

    public function esIngresada(): bool
    {
        return $this->tipo === TipoDeComponente::Ingresada;
    }

    /**
     * @return array{id: int, codigo: string, nombre: string, tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>, ciclo: ?int}
     */
    public function aArreglo(): array
    {
        return [
            'id' => $this->id, 'codigo' => $this->codigo, 'nombre' => $this->nombre, 'tipo' => $this->tipo->value,
            'calculo' => $this->calculo?->value, 'redondeo' => $this->redondeo->value, 'fuentes' => $this->fuentes, 'ciclo' => $this->ciclo,
        ];
    }

    /**
     * @param  array{id: int, codigo: string, nombre: string, tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>, ciclo: ?int}  $datos
     */
    public static function deArreglo(array $datos): self
    {
        return new self(
            (int) $datos['id'], $datos['codigo'], $datos['nombre'], TipoDeComponente::from($datos['tipo']),
            CalculoDeComponente::tryFrom((string) $datos['calculo']), RedondeoDeNota::from($datos['redondeo']), $datos['fuentes'], $datos['ciclo'],
        );
    }
}
