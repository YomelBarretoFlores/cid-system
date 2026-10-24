<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\Concerns;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;

trait ConsultaElCatalogoDeIdiomas
{
    /**
     * @var array<int, IdiomaDelCatalogo>|null
     */
    private ?array $idiomas = null;

    public function idioma(int $idiomaId): ?IdiomaDelCatalogo
    {
        $this->idiomas ??= collect(app(CatalogoDeIdiomas::class)())->keyBy('id')->all();

        return $this->idiomas[$idiomaId] ?? null;
    }

    public function nombreDeIdioma(int $idiomaId): string
    {
        return $this->idioma($idiomaId)->nombre ?? '—';
    }
}
