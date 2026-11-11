<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Certificacion\Contracts\EstadosDeActas;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Data\EtapasDelActa;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\PestanasDelGrupo;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

final class PreActaDelGrupo extends Page
{
    use PestanasDelGrupo;

    #[\Override]
    protected static bool $shouldRegisterNavigation = false;

    #[\Override]
    protected static ?string $slug = 'grupos/{grupo}/notas';

    #[\Override]
    protected string $view = 'docencia::filament.docente.pre-acta-del-grupo';

    #[Locked]
    public int $grupoId;

    /** @var array<int, array<int, string>> */
    public array $notas = [];

    public ?string $guardadas = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'grupos.notas';
    }

    public function mount(string $grupo): void
    {
        $grupo = self::grupoDeLaRuta($grupo);
        abort_unless(GrupoACargo::tiene($this->docenteId(), $grupo), 404);

        $this->grupoId = $grupo;
        $this->cargarNotas();
    }

    public function hydrate(): void
    {
        // The page may stay open after the group moves to another teacher; reads stop too, not only saves.
        abort_unless(GrupoACargo::tiene($this->docenteId(), $this->grupoId), 404);
    }

    public function getTitle(): string
    {
        return "Pre-acta del grupo {$this->grupo()->codigo}";
    }

    public function getSubheading(): string
    {
        $grupo = $this->grupo();

        return "{$grupo->idioma} · Ciclo {$grupo->cicloNumero} · {$grupo->nivel->etiqueta()} · {$grupo->periodo}";
    }

    public function guardar(RegistrarNotasParciales $registrar): void
    {
        $this->guardadas = null;
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $registrar($this->grupoId, $this->docenteId(), $this->notas, $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('notas', $error->getMessage());

            return;
        }

        $this->cargarNotas();
        $this->guardadas = 'Notas parciales guardadas.';
    }

    public static function numero(string $decimal): string
    {
        return str_contains($decimal, '.') ? rtrim(rtrim($decimal, '0'), '.') : $decimal;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPreActa')
                ->label('Descargar pre acta (PDF)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->url(fn (): string => route('docente.preacta.pdf', $this->grupoId))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $esquema = $this->esquema();
        $estudiantes = $this->estudiantes();
        $registradas = $this->registradasPorComponente($estudiantes);
        $curso = app(CursoDelGrupo::class)($this->grupoId);

        return [
            'esquema' => $esquema,
            'estudiantes' => $estudiantes,
            'registradas' => $registradas,
            'etapas' => EtapasDelActa::de(
                array_sum($registradas),
                count($estudiantes) * count($esquema->componentes ?? []),
                app(EstadosDeActas::class)([$this->grupoId])[$this->grupoId] ?? null,
                $curso?->actasInicio,
                $curso?->actasFin,
                ClasesDelHorario::hoy(),
            ),
        ];
    }

    /**
     * @param  list<EstudianteDelGrupo>  $estudiantes
     * @return array<int, int> saved grades of the active students by component id
     */
    private function registradasPorComponente(array $estudiantes): array
    {
        $notas = app(NotasParcialesDelGrupo::class)($this->grupoId);

        return collect($estudiantes)
            ->flatMap(fn (EstudianteDelGrupo $estudiante): array => array_keys($notas[$estudiante->matriculaId] ?? []))
            ->countBy()
            ->all();
    }

    protected function pestana(): string
    {
        return 'notas';
    }

    protected function grupoDeLaPestana(): int
    {
        return $this->grupoId;
    }

    private function cargarNotas(): void
    {
        $esquema = $this->esquema();
        $guardadas = NotaParcial::query()->where('grupo_id', $this->grupoId)->get()
            ->mapWithKeys(fn (NotaParcial $nota): array => ["{$nota->matricula_id}.{$nota->componente_id}" => $nota->nota ?? '']);

        $this->notas = $esquema instanceof EsquemaDeEvaluacion ? collect($this->estudiantes())
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => collect($esquema->componentes)
                ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => $guardadas["{$estudiante->matriculaId}.{$componente->id}"] ?? ''])
                ->all()])
            ->all() : [];
    }

    private function esquema(): ?EsquemaDeEvaluacion
    {
        return app(EsquemaDelGrupo::class)($this->grupoId);
    }

    /**
     * @return list<EstudianteDelGrupo>
     */
    private function estudiantes(): array
    {
        return app(EstudiantesDelGrupo::class)($this->grupoId);
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
