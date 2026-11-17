<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Actions\DesignarDelegado;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarLlamadoDeCierre;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Enums\Llamado;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Docencia\Support\VentanasDeLlamado;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\PestanasDelGrupo;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

final class AsistenciaDelGrupo extends Page
{
    use PestanasDelGrupo;

    #[\Override]
    protected static bool $shouldRegisterNavigation = false;

    #[\Override]
    protected static ?string $slug = 'grupos/{grupo}/asistencia';

    #[\Override]
    protected string $view = 'docencia::filament.docente.asistencia-del-grupo';

    #[Locked]
    public int $grupoId;

    public string $clase = '';

    /** @var array<int, string> */
    public array $estados = [];

    public ?string $tema = null;

    // The call shown when the class was loaded, so a save near a window edge goes to the call the teacher filled in.
    #[Locked]
    public Llamado $llamado = Llamado::Inicio;

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
                ->helperText('Solo clases del horario cuyo llamado de inicio ya abrió. Nunca una clase futura.')
                ->live(),
            Textarea::make('tema')
                ->label('Tema dictado en la sesión')
                ->helperText(fn (): ?string => match ($this->llamado) {
                    Llamado::Inicio => 'Obligatorio al tomar el llamado de cierre.',
                    Llamado::Cierre, Llamado::Posterior => 'Obligatorio.',
                    Llamado::Anterior, Llamado::Cerrado => null,
                })
                ->placeholder(fn (): ?string => $this->llamado === Llamado::Cerrado ? 'Sin tema registrado' : null)
                ->rows(2)
                ->maxLength(1000)
                ->disabled(fn (): bool => $this->llamado === Llamado::Cerrado)
                ->visible(fn (): bool => $this->estudiantesDelLlamado() !== []),
            View::make('docencia::filament.docente.pasos-del-llamado')
                ->viewData(fn (): array => ['pasos' => $this->pasosDelLlamado()])
                ->visible(fn (): bool => $this->estudiantesDelLlamado() !== []),
            Section::make('Estudiantes')
                ->key('estudiantes')
                ->description(fn (): string => $this->descripcionDelLlamado())
                ->afterHeader([
                    Action::make('todosPresentes')
                        ->label('Todos presentes')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('gray')
                        ->size(Size::Small)
                        ->action(fn () => $this->marcarTodosPresentes())
                        ->visible(fn (): bool => $this->llamado === Llamado::Inicio),
                ])
                ->schema(fn (): array => array_map($this->marcaDe(...), $this->estudiantesDelLlamado()))
                ->visible(fn (): bool => $this->estudiantesDelLlamado() !== []),
        ]);
    }

    public function updatedClase(): void
    {
        $this->guardada = null;
        $this->resetErrorBag();
        $this->cargarEstados();
    }

    public function guardar(RegistrarAsistencia $registrar, RegistrarLlamadoDeCierre $cerrar): void
    {
        $this->guardada = null;
        $elegida = $this->claseElegida();
        [$fecha, $hora] = $elegida instanceof ClaseProgramada ? [$elegida->fecha->toDateString(), substr($elegida->sesion->horaInicio, 0, 5)] : ['', ''];
        $registro = $this->llamado === Llamado::Cierre ? $cerrar : $registrar;

        $clase = $this->intentar(fn (): ClaseDictada => $registro($this->grupoId, $this->docenteId(), $fecha, $hora, $this->estados, $this->usuario(), $this->tema));

        if ($clase instanceof ClaseDictada) {
            $tipo = $clase->registro_posterior ? Llamado::Posterior : $this->llamado;
            $this->guardada = $tipo->guardado(mb_lcfirst((string) $elegida?->descripcion()));
            $this->cargarEstados();
        }
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('designarDelegado')
                ->label('Designar delegado')
                ->icon(Heroicon::OutlinedIdentification)
                ->modalDescription('El delegado confirma desde su celular que la clase se dictó.')
                ->fillForm(fn (): array => ['matricula' => app(DelegadoDelGrupo::class)($this->grupoId)?->matriculaId])
                ->schema([
                    Select::make('matricula')
                        ->label('Estudiante')
                        ->options(fn (): array => collect(app(EstudiantesDelGrupo::class)($this->grupoId))->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => $estudiante->nombreCompleto])->all())
                        ->required(),
                ])
                ->action(fn (array $data) => $this->designarDelegado(is_numeric($data['matricula'] ?? null) ? (int) $data['matricula'] : 0)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'hayClases' => ClasesDelHorario::hastaHoy($this->grupo()) !== [],
            'hayEstudiantes' => $this->estudiantes() !== [],
            'boton' => $this->llamado->boton(),
            'delegado' => app(DelegadoDelGrupo::class)($this->grupoId)?->nombreCompleto,
        ];
    }

    protected function pestana(): string
    {
        return 'asistencia';
    }

    protected function grupoDeLaPestana(): int
    {
        return $this->grupoId;
    }

    private function designarDelegado(int $matriculaId): void
    {
        $this->guardada = null;
        $delegado = $this->intentar(fn (): mixed => app(DesignarDelegado::class)($this->grupoId, $this->docenteId(), $matriculaId, $this->usuario()));

        if ($delegado !== null) {
            $this->guardada = 'Delegado del grupo: '.app(DelegadoDelGrupo::class)($this->grupoId)?->nombreCompleto.'.';
        }
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $operacion
     * @return T|null
     */
    private function intentar(Closure $operacion): mixed
    {
        try {
            return $operacion();
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('asistencia', $error->getMessage());

            return null;
        }
    }

    // Only fills the form; the teacher still saves the call, so the opening-call rules apply as usual.
    private function marcarTodosPresentes(): void
    {
        foreach ($this->estudiantesDelLlamado() as $estudiante) {
            $this->estados[$estudiante->matriculaId] = EstadoAsistencia::Presente->value;
        }
    }

    private function marcaDe(EstudianteDelGrupo $estudiante): ToggleButtons
    {
        return ToggleButtons::make("estados.{$estudiante->matriculaId}")
            ->label($estudiante->nombreCompleto)
            ->options(collect($this->llamado->estados())->mapWithKeys(fn (EstadoAsistencia $estado): array => [$estado->value => $estado->etiqueta()])->all())
            ->colors([EstadoAsistencia::Presente->value => 'success', EstadoAsistencia::Falta->value => 'danger'])
            ->disabled($this->llamado === Llamado::Cerrado)
            ->inline()
            ->grouped();
    }

    /**
     * The calls of the chosen class as numbered steps: [number, call, state shown, kind] where kind is actual, hecho or pendiente.
     *
     * @return list<array{int, string, string, string}>
     */
    private function pasosDelLlamado(): array
    {
        return match ($this->llamado) {
            Llamado::Inicio => [[1, 'Llamado de inicio', 'En curso', 'actual'], [2, 'Llamado de cierre', 'Pendiente', 'pendiente']],
            Llamado::Cierre => [[1, 'Llamado de inicio', 'Tomado', 'hecho'], [2, 'Llamado de cierre', 'En curso', 'actual']],
            Llamado::Cerrado => [[1, 'Llamado de inicio', 'Cerrado', 'hecho'], [2, 'Llamado de cierre', 'Cerrado', 'hecho']],
            Llamado::Posterior => [[1, 'Registro posterior', 'En curso', 'actual']],
            Llamado::Anterior => [[1, 'Registro anterior a los llamados', 'Registrado', 'hecho']],
        };
    }

    private function descripcionDelLlamado(): string
    {
        $elegida = $this->claseElegida();
        $ventanas = $elegida instanceof ClaseProgramada ? VentanasDeLlamado::de($elegida->fecha, $elegida->sesion) : null;

        return match ($this->llamado) {
            Llamado::Inicio => "Llamado de inicio: abierto hasta las {$ventanas?->cierraInicio()->format('H:i')}. Guardar de nuevo corrige este llamado.",
            Llamado::Cierre => "Llamado de cierre: solo los estudiantes presentes al inicio. Abierto hasta las {$ventanas?->cierraCierre()->format('H:i')}.",
            Llamado::Posterior => 'Registro posterior: un solo llamado, sin confirmación del delegado. Guardar de nuevo corrige la asistencia.',
            Llamado::Anterior => 'Registrada antes de los dos llamados: guardar de nuevo corrige la asistencia, sin confirmación del delegado.',
            Llamado::Cerrado => 'Los llamados de esta clase ya cerraron.',
        };
    }

    /**
     * @return array<string, string>
     */
    private function opcionesDeClase(): array
    {
        $registradas = ClaseDictada::query()->where('grupo_id', $this->grupoId)->get()
            ->mapWithKeys(fn (ClaseDictada $clase): array => [$clase->fecha->toDateString().'|'.substr($clase->hora_inicio, 0, 5) => ' · '.EstadoDeLaClase::de($clase)->etiqueta()]);

        return collect(ClasesDelHorario::hastaHoy($this->grupo()))
            ->mapWithKeys(fn (ClaseProgramada $opcion): array => [$opcion->clave() => $opcion->descripcion().($registradas[$opcion->clave()] ?? '')])
            ->all();
    }

    private function cargarEstados(): void
    {
        $elegida = $this->claseElegida();
        $clase = $elegida instanceof ClaseProgramada ? ClaseDictada::query()->where('grupo_id', $this->grupoId)->where('fecha', $elegida->fecha->toDateString())->where('hora_inicio', $elegida->sesion->horaInicio)->first() : null;
        $this->llamado = $elegida instanceof ClaseProgramada ? EstadoDeLaClase::llamadoPara($clase, VentanasDeLlamado::de($elegida->fecha, $elegida->sesion)) : Llamado::Inicio;
        $guardadas = $clase === null ? collect() : Asistencia::query()->where('clase_id', $clase->id)->get()->keyBy('matricula_id');
        $this->tema = $clase?->tema;
        $estudiantes = $this->llamado === Llamado::Cierre
            ? array_filter($this->estudiantes(), fn (EstudianteDelGrupo $estudiante): bool => ! in_array($guardadas->get($estudiante->matriculaId)?->estado, [null, EstadoAsistencia::Falta], true))
            : $this->estudiantes();

        $this->estados = collect($estudiantes)
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => $this->estadoGuardado($guardadas->get($estudiante->matriculaId))])
            ->all();
    }

    private function estadoGuardado(?Asistencia $asistencia): string
    {
        $estado = $this->llamado === Llamado::Cierre ? $asistencia?->estado_cierre : $asistencia?->estado;

        return ($estado ?? EstadoAsistencia::Presente)->value;
    }

    /**
     * Students of the call loaded for the chosen class: the second call only lists those present or late at the start.
     *
     * @return list<EstudianteDelGrupo>
     */
    private function estudiantesDelLlamado(): array
    {
        return array_values(array_filter($this->estudiantes(), fn (EstudianteDelGrupo $estudiante): bool => array_key_exists($estudiante->matriculaId, $this->estados)));
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

    private function usuario(): User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : abort(403);
    }
}
