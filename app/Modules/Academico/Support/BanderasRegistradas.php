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
        $this->porNombre ??= $this->guardadas();

        return $this->porNombre[self::clave($idioma)] ?? BanderaDeIdioma::sugeridaPara($idioma);
    }

    /**
     * @return array<string, BanderaDeIdioma>
     */
    private function guardadas(): array
    {
        $guardadas = [];

        foreach (Idioma::query()->toBase()->whereNotNull('bandera')->pluck('bandera', 'nombre') as $nombre => $bandera) {
            $guardadas[self::clave((string) $nombre)] = BanderaDeIdioma::tryFrom((string) $bandera) ?? BanderaDeIdioma::sugeridaPara((string) $nombre);
        }

        return $guardadas;
    }

    private static function clave(string $idioma): string
    {
        return mb_strtolower(trim($idioma));
    }
}
