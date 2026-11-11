<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\DocenteIdioma;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Support\Filament\IdiomaConBandera;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

final class ListDocentes extends ListRecords
{
    #[\Override]
    protected static string $resource = DocenteResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $personas = null;

    /**
     * @var array<int, string>|null
     */
    private ?array $idiomas = null;

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->personas ??= app(EtiquetasDePersonas::class)($this->personasDeLaPagina());

        return $this->personas[$personaId] ?? '—';
    }

    public function idiomasDelDocente(Docente $docente): HtmlString|string
    {
        $this->idiomas ??= collect(app(CatalogoDeIdiomas::class)())
            ->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])
            ->all();
        $nombres = $docente->idiomas->map(fn (DocenteIdioma $habilitado): ?string => $this->idiomas[$habilitado->idioma_id] ?? null)->filter();

        if ($nombres->isEmpty()) {
            return 'Sin idiomas habilitados';
        }

        return new HtmlString('<span class="flex flex-wrap gap-x-3 gap-y-1">'.$nombres->map(fn (string $nombre): string => IdiomaConBandera::html($nombre)->toHtml())->implode('').'</span>');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return list<int>
     */
    private function personasDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $docentes = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($docentes->map(fn (Docente $docente): int => $docente->persona_id)->all()));
    }
}
