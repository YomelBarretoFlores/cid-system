<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum RegimenDePrograma: string
{
    case Regular = 'regular';
    case Acelerado = 'acelerado';
    case Ambos = 'ambos';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Regular => 'Solo regular',
            self::Acelerado => 'Solo acelerado',
            self::Ambos => 'Regular y acelerado',
        };
    }

    public function admiteGrupo(bool $esAcelerado): bool
    {
        return match ($this) {
            self::Regular => ! $esAcelerado,
            self::Acelerado => $esAcelerado,
            self::Ambos => true,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $regimen): array => [$regimen->value, $regimen->etiqueta()], self::cases()), 1, 0);
    }
}
