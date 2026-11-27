<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum RegimenDeEsquema: string
{
    case Regular = 'regular';
    case Acelerado = 'acelerado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Acelerado => 'Acelerado',
        };
    }

    public function esAcelerado(): bool
    {
        return $this === self::Acelerado;
    }

    public static function delGrupo(bool $esAcelerado): self
    {
        return $esAcelerado ? self::Acelerado : self::Regular;
    }
}
