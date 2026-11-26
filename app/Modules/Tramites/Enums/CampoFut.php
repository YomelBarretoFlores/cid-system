<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

// The fields of the paper FUT a trámite type can ask; the case order is the order of the form.
enum CampoFut: string
{
    case Matricula = 'matricula';
    case Idioma = 'idioma';
    case Nivel = 'nivel';
    case Programa = 'programa';
    case Mencion = 'mencion';
    case Modalidad = 'modalidad';
    case Exposicion = 'exposicion';
    case Celular = 'celular';
    case Motivo = 'motivo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Matricula => 'Matrícula',
            self::Idioma => 'Idioma',
            self::Nivel => 'Nivel',
            self::Programa => 'Programa',
            self::Mencion => 'Mención',
            self::Modalidad => 'Modalidad',
            self::Exposicion => 'Y expongo que',
            self::Celular => 'Celular',
            self::Motivo => 'Motivo',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $campo): array => [$campo->value, $campo->etiqueta()], self::cases()), 1, 0);
    }
}
