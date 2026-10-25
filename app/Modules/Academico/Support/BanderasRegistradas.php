<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Modules\Academico\Models\Idioma;
use App\Shared\Ui\BanderaDeIdioma;
use App\Shared\Ui\BanderasDeIdiomas;

// One query per request: a page may draw dozens of flags.
final class BanderasRegistradas implements BanderasDeIdiomas
{
    /**
     * @var array<string, BanderaDeIdioma>|null
     */
    private ?array $porNombre = null;

    public function para(string $idioma): BanderaDeIdioma
    {
        $this->porNombre ??= Idioma::query()->toBase()->whereNotNull('bandera')->pluck('bandera', 'nombre')
            ->mapWithKeys(fn (mixed $bandera, mixed $nombre): array => [mb_strtolower((string) $nombre) => BanderaDeIdioma::tryFrom((string) $bandera) ?? BanderaDeIdioma::sugeridaPara((string) $nombre)])
            ->all();

        return $this->porNombre[mb_strtolower(trim($idioma))] ?? BanderaDeIdioma::sugeridaPara($idioma);
    }
}
