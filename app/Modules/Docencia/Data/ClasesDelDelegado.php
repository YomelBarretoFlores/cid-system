<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class ClasesDelDelegado
{
    /**
     * @param  list<ClaseParaConfirmar>  $porConfirmar  within the confirmation window, newest first
     * @param  list<ClaseParaConfirmar>  $revisadas  confirmed or observed, last reviewed first
     */
    public function __construct(
        public bool $esDelegado,
        public array $porConfirmar,
        public array $revisadas,
    ) {}

    public function pendientesDelDelegado(): int
    {
        return count(array_filter($this->porConfirmar, fn (ClaseParaConfirmar $clase): bool => $clase->puedeConfirmar));
    }
}
