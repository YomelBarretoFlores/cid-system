<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Docente\Pages;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\PrepararActaDelGrupo;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Modules\Docencia\Data\EtapasDelActa;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\PestanasDelGrupo;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

final class ActaFinalDelGrupo extends Page
{
    use PestanasDelGrupo;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'grupos/{grupo}/acta';

    protected string $view = 'certificacion::filament.docente.acta-final-del-grupo';

    #[Locked]
    public int $grupoId;

    /** @var array<int, array<int, string>> */
    public array $notas = [];

    public ?string $aviso = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'grupos.acta';
    }

    public function mount(string $grupo): void
    {
        $grupo = self::grupoDeLaRuta($grupo);
        $this->asegurarAcceso($grupo);
        $this->grupoId = $grupo;
        $this->cargarNotas();
    }

    public function hydrate(): void
    {
        $this->asegurarAcceso($this->grupoId);
    }

    public function getTitle(): string
    {
        return "Acta final del grupo {$this->curso()->codigo}";
    }

    public function getSubheading(): string
    {
        $curso = $this->curso();

        return "{$curso->idioma} · Ciclo {$curso->cicloNumero} · {$curso->nivel->etiqueta()} · {$curso->periodo}";
    }

    public function validar(CerrarActa $cerrar): void
    {
        $this->ejecutar(fn (int $docenteId, User $usuario) => $cerrar($this->grupoId, $docenteId, $usuario), 'Acta final validada y cerrada.');
    }

    public function guardar(RectificarNotasFinales $rectificar): void
    {
        $this->ejecutar(fn (int $docenteId) => $rectificar($this->grupoId, $docenteId, $this->notas), 'Notas del acta rectificadas. Valídala para cerrarla de nuevo.');
    }

    /**
     * @param  list<FilaDelActa>  $filas
     * @return array{aprobados: int, desaprobados: int, nsp: int}
     */
    public static function resumen(array $filas): array
    {
        $conteo = array_count_values(array_map(fn (FilaDelActa $fila): string => $fila->estadoFinal->value, $filas));

        return [
            'aprobados' => $conteo[EstadoFinal::Aprobado->value] ?? 0,
            'desaprobados' => $conteo[EstadoFinal::Desaprobado->value] ?? 0,
            'nsp' => $conteo[EstadoFinal::Nsp->value] ?? 0,
        ];
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Descargar acta (PDF)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (): string => route('docente.acta.pdf', $this->grupoId))
                ->openUrlInNewTab()
                ->visible(fn (): bool => ($acta = $this->acta()) instanceof ActaDelGrupo && $acta->estaCerrada()),
            Action::make('validarActa')
                ->label('Validar y cerrar acta')
                ->requiresConfirmation()
                ->modalHeading('¿Cerrar el acta final?')
                ->modalDescription('Al cerrarla ya no podrás cambiar las notas.')
                ->modalSubmitActionLabel('Sí, cerrar acta')
                ->visible(fn (): bool => ($acta = $this->acta()) instanceof ActaDelGrupo && ! $acta->estaCerrada())
                ->disabled(fn (): bool => ! $this->esEditable() && ! $this->curso()->admiteActasEl(now()->toImmutable()))
                ->action(fn (CerrarActa $cerrar) => $this->validar($cerrar)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $curso = $this->curso();
        $acta = $this->acta();

        return [
            'curso' => $curso,
            'acta' => $acta,
            'problema' => $acta instanceof ActaDelGrupo ? null : $acta,
            'editable' => $this->esEditable(),
            'motivo' => $acta instanceof ActaDelGrupo && $acta->admiteRectificacion() ? $this->motivoDeRectificacion($acta) : null,
            'etapas' => $acta instanceof ActaDelGrupo ? $this->etapas($acta, $curso) : [],
        ];
    }

    /**
     * @return list<array{int, string, string, 'hecho'|'actual'|'pendiente'}>
     */
    private function etapas(ActaDelGrupo $acta, DatosDelCurso $curso): array
    {
        $registradas = array_sum(array_map(fn (FilaDelActa $fila): int => count($fila->notas), $acta->filas));

        return EtapasDelActa::de($registradas, count($acta->filas) * count($acta->componentes), $acta->estado, $curso->actasInicio, $curso->actasFin, CarbonImmutable::now('America/Lima'));
    }

    protected function pestana(): string
    {
        return 'acta';
    }

    protected function grupoDeLaPestana(): int
    {
        return $this->grupoId;
    }

    /**
     * @param  Closure(int, User): void  $operacion
     */
    private function ejecutar(Closure $operacion, string $exito): void
    {
        $this->aviso = null;
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $operacion($this->asegurarAcceso($this->grupoId), $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('acta', $error->getMessage());

            return;
        }

        $this->cargarNotas();
        $this->aviso = $exito;
    }

    private function curso(): DatosDelCurso
    {
        return app(CursoDelGrupo::class)($this->grupoId) ?? abort(404);
    }

    private function acta(): ActaDelGrupo|string
    {
        try {
            return app(PrepararActaDelGrupo::class)($this->grupoId);
        } catch (ReglaDeNegocioIncumplida $error) {
            return $error->getMessage();
        }
    }

    private function esEditable(): bool
    {
        $acta = $this->acta();

        return $acta instanceof ActaDelGrupo && $acta->admiteRectificacion();
    }

    private function cargarNotas(): void
    {
        $acta = $this->acta();

        $this->notas = $acta instanceof ActaDelGrupo && $acta->admiteRectificacion() ? collect($acta->filas)
            ->mapWithKeys(fn (FilaDelActa $fila): array => [$fila->matriculaId => collect($acta->componentes)
                ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => $fila->notas[$componente->id] ?? ''])
                ->all()])
            ->all() : [];
    }

    private function motivoDeRectificacion(ActaDelGrupo $acta): ?string
    {
        return Rectificacion::query()->where('acta_id', $acta->actaId)->whereNull('cerrada_en')->value('motivo');
    }

    private function asegurarAcceso(int $grupoId): int
    {
        return app(AccesoDelDocente::class)->asegurar((int) auth()->id(), $grupoId);
    }
}
