<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\AccionesDeCuentaDocente;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\ConCuentasDeDocentes;
use App\Modules\Docencia\Filament\Resources\Docentes\Actions\CambiarEstado;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\DocenteIdioma;
use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Modules\Personas\Data\FichaDeEstudiante;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewDocente extends ViewRecord implements ConCuentasDeDocentes
{
    #[\Override]
    protected static string $resource = DocenteResource::class;

    private ?FichaDeEstudiante $persona = null;

    private ?CuentaDelPortal $cuenta = null;

    private bool $cuentaLeida = false;

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

    // Staff look for the teacher by name, so the page is titled with it rather than with the code.
    public function getTitle(): string
    {
        return $this->persona()->nombreCompleto;
    }

    public function getSubheading(): string
    {
        return "{$this->docente()->codigo} · {$this->persona()->documento}";
    }

    public function cuentaDe(Docente $docente): ?CuentaDelPortal
    {
        if (! $this->cuentaLeida) {
            $this->cuenta = app(CuentasDePersonas::class)([$docente->persona_id])[$docente->persona_id] ?? null;
            $this->cuentaLeida = true;
        }

        return $this->cuenta;
    }

    public function olvidarCuentas(): void
    {
        $this->cuentaLeida = false;
    }

    protected function getHeaderActions(): array
    {
        [$crear, $cambiar, $baja, $reactivar] = AccionesDeCuentaDocente::todas();

        return [
            EditAction::make(),
            $crear->authorize('update')->color('gray'),
            ActionGroup::make([
                $cambiar->authorize('update'),
                $reactivar->authorize('update')->label('Reactivar la cuenta'),
                CambiarEstado::make(),
                $baja->authorize('update')->label('Dar de baja la cuenta'),
            ])
                ->label('Más acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('gray')
                ->button(),
        ];
    }

    private function docente(): Docente
    {
        $docente = $this->getRecord();

        return $docente instanceof Docente ? $docente : abort(404);
    }
}
