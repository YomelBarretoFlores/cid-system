<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\PestanasDelGrupo;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;

final class AsistenciaDelGrupo extends Page
{
    use PestanasDelGrupo;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'grupos/{grupo}/asistencia';

    protected string $view = 'docencia::filament.docente.asistencia-del-grupo';

    #[Locked]
    public int $grupoId;

    public string $clase = '';

    /** @var array<int, string> */
    public array $estados = [];

    public ?string $guardada = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'grupos.asistencia';
    }

    public function mount(string $grupo): void
    {
        $grupo = self::grupoDeLaRuta($grupo);
        abort_unless(GrupoACargo::tiene($this->docenteId(), $grupo), 404);

        $this->grupoId = $grupo;
        $this->clase = (ClasesDelHorario::hastaHoy($this->grupo())[0] ?? null)?->clave() ?? '';
        $this->cargarEstados();
    }

    public function hydrate(): void
    {
        // The page may stay open after the group moves to another teacher; reads stop too, not only saves.
        abort_unless(GrupoACargo::tiene($this->docenteId(), $this->grupoId), 404);
    }

    public function getTitle(): string
    {
        return "Asistencia del grupo {$this->grupo()->codigo}";
    }

    public function getSubheading(): string
    {
        $grupo = $this->grupo();

        return "{$grupo->idioma} · Ciclo {$grupo->cicloNumero} · {$grupo->nivel->etiqueta()} · {$grupo->periodo}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('clase')
                ->label('Clase')
                ->options(fn (): array => $this->opcionesDeClase())
                ->selectablePlaceholder(false)
                ->helperText('Solo clases del horario entre el inicio del periodo y hoy. Nunca una clase futura.')
                ->live(),
            Section::make('Estudiantes')
                ->description('Guardar de nuevo corrige la asistencia de esta clase.')
                ->schema(fn (): array => array_map(
                    fn (EstudianteDelGrupo $estudiante): ToggleButtons => ToggleButtons::make("estados.{$estudiante->matriculaId}")
                        ->label($estudiante->nombreCompleto)
                        ->options(collect(EstadoAsistencia::cases())->mapWithKeys(fn (EstadoAsistencia $estado): array => [$estado->value => $estado->etiqueta()])->all())
                        ->colors([EstadoAsistencia::Presente->value => 'success', EstadoAsistencia::Tardanza->value => 'warning', EstadoAsistencia::Falta->value => 'danger'])
                        ->inline()
                        ->grouped(),
                    $this->estudiantes(),
                ))
                ->visible(fn (): bool => $this->estudiantes() !== []),
        ]);
    }

    public function updatedClase(): void
    {
        $this->guardada = null;
        $this->resetErrorBag();
        $this->cargarEstados();
    }

    public function guardar(RegistrarAsistencia $registrar): void
    {
        $this->guardada = null;
        $elegida = $this->claseElegida();
        [$fecha, $hora] = $elegida instanceof ClaseProgramada ? [$elegida->fecha->toDateString(), substr($elegida->sesion->horaInicio, 0, 5)] : ['', ''];
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $registrar($this->grupoId, $this->docenteId(), $fecha, $hora, $this->estados, $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('asistencia', $error->getMessage());

            return;
        }

        $this->guardada = 'Asistencia del '.mb_lcfirst((string) $elegida?->descripcion()).' guardada.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['hayClases' => ClasesDelHorario::hastaHoy($this->grupo()) !== [], 'hayEstudiantes' => $this->estudiantes() !== []];
    }

    protected function pestana(): string
    {
        return 'asistencia';
    }

    protected function grupoDeLaPestana(): int
    {
        return $this->grupoId;
    }

    /**
     * @return array<string, string>
     */
    private function opcionesDeClase(): array
    {
        $registradas = ClaseDictada::query()->where('grupo_id', $this->grupoId)->get()
            ->mapWithKeys(fn (ClaseDictada $clase): array => [$clase->fecha->toDateString().'|'.substr($clase->hora_inicio, 0, 5) => true]);

        return collect(ClasesDelHorario::hastaHoy($this->grupo()))
            ->mapWithKeys(fn (ClaseProgramada $opcion): array => [$opcion->clave() => $opcion->descripcion().(isset($registradas[$opcion->clave()]) ? ' · registrada' : '')])
            ->all();
    }

    private function cargarEstados(): void
    {
        $elegida = $this->claseElegida();
        $clase = $elegida instanceof ClaseProgramada ? ClaseDictada::query()->where('grupo_id', $this->grupoId)->where('fecha', $elegida->fecha->toDateString())->where('hora_inicio', $elegida->sesion->horaInicio)->first() : null;
        $guardados = $clase === null ? collect() : Asistencia::query()->where('clase_id', $clase->id)->get()->mapWithKeys(fn (Asistencia $asistencia): array => [$asistencia->matricula_id => $asistencia->estado->value]);

        $this->estados = collect($this->estudiantes())
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => $guardados[$estudiante->matriculaId] ?? EstadoAsistencia::Presente->value])
            ->all();
    }

    private function claseElegida(): ?ClaseProgramada
    {
        return collect(ClasesDelHorario::hastaHoy($this->grupo()))->first(fn (ClaseProgramada $clase): bool => $clase->clave() === $this->clase);
    }

    /**
     * @return list<EstudianteDelGrupo>
     */
    private function estudiantes(): array
    {
        $clase = $this->claseElegida();
        $estudiantes = app(EstudiantesDelGrupo::class)($this->grupoId);

        return $clase instanceof ClaseProgramada ? ClasesDelHorario::estudiantesDe($estudiantes, $clase->fecha) : $estudiantes;
    }

    private function grupo(): HorarioDeGrupo
    {
        return app(HorariosDeGrupos::class)([$this->grupoId])[$this->grupoId] ?? abort(404);
    }

    private function docenteId(): int
    {
        return app(DocenteDelUsuario::class)((int) auth()->id()) ?? abort(403);
    }
}
