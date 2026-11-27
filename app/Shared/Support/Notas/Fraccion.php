<?php

declare(strict_types=1);

namespace App\Shared\Support\Notas;

// Averages of averages are kept as exact fractions of hundredths, so only the rounding the scheme asks for ever happens.
final readonly class Fraccion
{
    private function __construct(public int $numerador, public int $denominador) {}

    public static function entero(int $valor): self
    {
        return new self($valor, 1);
    }

    public function mas(self $otra): self
    {
        return $this->reducida($this->numerador * $otra->denominador + $otra->numerador * $this->denominador, $this->denominador * $otra->denominador);
    }

    public function por(int $factor): self
    {
        return $this->reducida($this->numerador * $factor, $this->denominador);
    }

    public function entre(int $divisor): self
    {
        return $this->reducida($this->numerador, $this->denominador * $divisor);
    }

    /** Rounds half up to a multiple of the unit; grades are never negative. */
    public function redondeadaA(int $unidad): int
    {
        return intdiv($this->numerador * 2 + $unidad * $this->denominador, $unidad * $this->denominador * 2) * $unidad;
    }

    private function reducida(int $numerador, int $denominador): self
    {
        $divisor = $this->mcd($numerador, $denominador);

        return new self(intdiv($numerador, $divisor), intdiv($denominador, $divisor));
    }

    private function mcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return max(abs($a), 1);
    }
}
