<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

final class HojaDelTablero extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $encabezados
     * @param  list<list<string|int|float|null>>  $filas
     * @param  array<string, string>  $formatos  column letter => number format
     */
    public function __construct(
        private readonly string $titulo,
        private readonly array $encabezados,
        private readonly array $filas,
        private readonly array $formatos = [],
    ) {}

    public function title(): string
    {
        return $this->titulo;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->encabezados;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        return $this->filas;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->formatos;
    }

    // Text is always written as text, so a name that starts with "=" never becomes a formula.
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
