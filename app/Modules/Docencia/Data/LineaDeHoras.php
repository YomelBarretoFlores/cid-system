<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class LineaDeHoras
{
    public function __construct(
        public string $idioma,
        public string $nivel,
        public int $centesimasDeHora,
        public ?int $costoCentimos,
        public ?int $montoCentimos,
        public int $clasesConfirmadas = 0,
        public int $clasesNoConfirmadas = 0,
    ) {}

    public function horas(): string
    {
        return self::decimal($this->centesimasDeHora);
    }

    public function costoPorHora(): ?string
    {
        return $this->costoCentimos === null ? null : self::decimal($this->costoCentimos);
    }

    public function monto(): ?string
    {
        return $this->montoCentimos === null ? null : self::decimal($this->montoCentimos);
    }

    public static function soles(int $centimos): string
    {
        return 'S/ '.number_format(intdiv($centimos, 100), 0, '.', ',').'.'.str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function decimal(int $centesimas): string
    {
        return intdiv($centesimas, 100).'.'.str_pad((string) ($centesimas % 100), 2, '0', STR_PAD_LEFT);
    }
}
