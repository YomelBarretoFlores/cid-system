<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Docencia\Filament\Resources\Docentes\Actions\CambiarEstado;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\DocenteIdioma;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Data\FichaDeEstudiante;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewDocente extends ViewRecord
{
    #[\Override]
    protected static string $resource = DocenteResource::class;

    private ?FichaDeEstudiante $persona = null;

    public function persona(): FichaDeEstudiante
    {
        return $this->persona ??= app(FichaDePersona::class)($this->docente()->persona_id);
    }

    /**
     * @return list<string>
     */
    public function idiomasHabilitados(): array
    {
        $nombres = array_column(array_map(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id, $idioma->nombre], app(CatalogoDeIdiomas::class)()), 1, 0);

        return array_values($this->docente()->idiomas()->orderBy('id')->get()
            ->map(fn (DocenteIdioma $idioma): string => ($nombres[$idioma->idioma_id] ?? '—')." · hasta {$idioma->nivel_maximo->etiqueta()}")
            ->all());
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            CambiarEstado::make(),
        ];
    }

    private function docente(): Docente
    {
        $docente = $this->getRecord();

        return $docente instanceof Docente ? $docente : abort(404);
    }
}
