<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Docente\Pages;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Actions\CopiarSilabo;
use App\Modules\Docencia\Actions\GuardarSilabo;
use App\Modules\Docencia\Actions\PublicarSilabo;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\ContenidoDelSilabo;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Docencia\Support\SemanasDelPeriodo;
use App\Modules\Docencia\Support\SilabosParaCopiar;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\PestanasDelGrupo;
use App\Shared\Support\Logging\RegistroDeRechazos;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

final class SilaboDelGrupo extends Page
{
    use PestanasDelGrupo;

    private const array CELDAS_OBLIGATORIAS = ['saber', 'actividades'];

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'grupos/{grupo}/silabo';

    protected string $view = 'docencia::filament.docente.silabo-del-grupo';

    #[Locked]
    public int $grupoId;

    /** @var array<string, mixed> */
    public array $silabo = [];

    public ?string $aviso = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'grupos.silabo';
    }

    public function mount(string $grupo): void
    {
        $grupo = self::grupoDeLaRuta($grupo);
        abort_unless(GrupoACargo::tiene($this->docenteId(), $grupo), 404);

        $this->grupoId = $grupo;
        $this->cargar();
    }

    public function hydrate(): void
    {
        // The page may stay open after the group moves to another teacher; reads stop too, not only saves.
        abort_unless(GrupoACargo::tiene($this->docenteId(), $this->grupoId), 404);
    }

    public function getTitle(): string
    {
        return "Sílabo del grupo {$this->curso()->codigo}";
    }

    public function getSubheading(): string
    {
        $curso = $this->curso();

        return "{$curso->idioma} · Ciclo {$curso->cicloNumero} · {$curso->nivel->etiqueta()} · {$curso->periodo}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->identificacion(),
            Section::make('II. Sumilla')->schema([$this->texto('sumilla')]),
            Section::make('III. Competencia')->schema([$this->texto('competencias')]),
            Section::make('IV. Capacidades')->schema([$this->texto('capacidades')]),
            Section::make('V. Programación de contenidos')
                ->description('Una fila por semana del periodo. Saber y Actividades son obligatorios para publicar.')
                ->schema(fn (): array => array_map($this->semana(...), array_keys($this->rangos()), $this->rangos())),
            Section::make('VI. Estrategias metodológicas')->schema([$this->texto('metodologia')]),
            Section::make('VII. Evaluación')
                ->description('Componentes y pesos del sistema de evaluación vigente del grupo. Indicadores e instrumento son obligatorios para publicar.')
                ->schema(fn (): array => $this->evaluaciones()),
            Section::make('VIII. Referencias bibliográficas')
                ->description('Una referencia por línea.')
                ->schema([$this->texto('bibliografia')]),
        ]);
    }

    public function guardar(GuardarSilabo $guardar): void
    {
        $exito = $this->estado() === EstadoSilabo::Publicado ? 'Cambios guardados. El sílabo sigue publicado.' : 'Borrador guardado.';
        $this->ejecutar(fn (): Silabo => $guardar($this->grupoId, $this->docenteId(), $this->datos()), $exito);
    }

    public function publicar(PublicarSilabo $publicar): void
    {
        $this->ejecutar(fn (): Silabo => $publicar($this->grupoId, $this->docenteId(), $this->datos(), $this->usuario()), 'Sílabo publicado.');
    }

    /**
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label(fn (): string => $this->estado() === EstadoSilabo::Publicado ? 'Guardar cambios' : 'Guardar borrador')
                ->icon(Heroicon::OutlinedCheck)
                ->color('gray')
                ->action(fn (GuardarSilabo $guardar) => $this->guardar($guardar)),
            Action::make('copiar')
                ->label('Copiar de un sílabo anterior')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->modalDescription('Se copian los textos, las semanas por número y la evaluación por componente. Reemplaza todo lo que escribiste en este sílabo.')
                ->schema([
                    Select::make('origen')
                        ->label('Sílabo publicado del mismo ciclo')
                        ->options(fn (): array => app(SilabosParaCopiar::class)($this->grupoId))
                        ->required(),
                ])
                ->modalSubmitActionLabel('Copiar y reemplazar')
                ->visible(fn (): bool => app(SilabosParaCopiar::class)($this->grupoId) !== [])
                ->action(fn (array $data, CopiarSilabo $copiar) => $this->copiar($copiar, is_numeric($data['origen'] ?? null) ? (int) $data['origen'] : 0)),
            Action::make('publicar')
                ->label('Publicar')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalHeading('¿Publicar el sílabo?')
                ->modalDescription('Tus estudiantes podrán leerlo. Después podrás corregirlo y cada cambio quedará registrado.')
                ->modalSubmitActionLabel('Sí, publicar')
                ->visible(fn (): bool => $this->estado() !== EstadoSilabo::Publicado)
                ->action(fn (PublicarSilabo $publicar) => $this->publicar($publicar)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['estado' => $this->estado()];
    }

    protected function pestana(): string
    {
        return 'silabo';
    }

    protected function grupoDeLaPestana(): int
    {
        return $this->grupoId;
    }

    private function copiar(CopiarSilabo $copiar, int $origen): void
    {
        $exito = $this->estado() === EstadoSilabo::Publicado ? 'Sílabo copiado. Sigue publicado con el nuevo contenido.' : 'Sílabo copiado. Revísalo y publícalo cuando esté listo.';
        $this->ejecutar(fn (): Silabo => $copiar($this->grupoId, $this->docenteId(), $origen), $exito);
    }

    /**
     * @param  Closure(): mixed  $operacion
     */
    private function ejecutar(Closure $operacion, string $exito): void
    {
        $this->aviso = null;
        $this->resetErrorBag();

        try {
            $operacion();
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('silabo', $error->getMessage());

            return;
        }

        $this->cargar();
        $this->aviso = $exito;
    }

    private function identificacion(): Section
    {
        $curso = $this->curso();
        $ficha = app(DetalleDeGrupos::class)([$this->grupoId])[$this->grupoId] ?? null;
        $grupo = $this->grupo();

        return Section::make('I. Datos generales')
            ->description('Registrados por la coordinación académica.')
            ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
            ->schema([
                $this->dato('idioma', 'Idioma', $curso->idioma),
                $this->dato('ciclo', 'Ciclo', $curso->cicloNombre),
                $this->dato('nivel', 'Nivel', $curso->nivel->etiqueta()),
                $this->dato('periodo', 'Periodo', "{$curso->periodo} (".Formato::fechaCorta($grupo->periodoInicio).' al '.Formato::fechaCorta($grupo->periodoFin).')'),
                $this->dato('horas', 'Horas lectivas', (string) $curso->horasLectivas),
                $this->dato('horario', 'Horario', $ficha instanceof FichaDeGrupo && $ficha->sesiones !== [] ? implode(' / ', $ficha->horarioCompacto()) : 'Por confirmar'),
                $this->dato('aula', 'Aula', $ficha->aula ?? $ficha->enlaceVirtual ?? 'Por asignar'),
                $this->dato('docente', 'Docente', app(DocenteDelGrupo::class)($this->grupoId)->nombreCompleto ?? 'Sin docente'),
            ]);
    }

    private function dato(string $nombre, string $etiqueta, string $valor): TextEntry
    {
        return TextEntry::make("identificacion_{$nombre}")->label($etiqueta)->state($valor);
    }

    private function texto(string $campo): Textarea
    {
        return Textarea::make("silabo.{$campo}")
            ->label(ContenidoDelSilabo::TEXTOS[$campo])
            ->hiddenLabel()
            ->markAsRequired()
            ->rows(4)
            ->autosize()
            ->maxLength(ContenidoDelSilabo::LIMITE_DE_TEXTO);
    }

    /**
     * @param  array{CarbonImmutable, CarbonImmutable}  $rango
     */
    private function semana(int $numero, array $rango): Section
    {
        $celdas = array_map(
            fn (string $campo, string $etiqueta): Textarea => Textarea::make("silabo.semanas.{$numero}.{$campo}")
                ->label($etiqueta)
                ->markAsRequired(in_array($campo, self::CELDAS_OBLIGATORIAS, true))
                ->rows(2)
                ->autosize()
                ->maxLength(ContenidoDelSilabo::LIMITE_DE_CELDA),
            array_keys(ContenidoDelSilabo::CAMPOS_DE_SEMANA),
            ContenidoDelSilabo::CAMPOS_DE_SEMANA,
        );

        return Section::make("Semana {$numero} · ".Formato::rango($rango[0], $rango[1]))
            ->compact()
            ->collapsible()
            ->columns(['default' => 1, 'md' => 2])
            ->schema($celdas);
    }

    /**
     * @return list<Section|Text>
     */
    private function evaluaciones(): array
    {
        $esquema = $this->esquema();

        if (! $esquema instanceof EsquemaDeEvaluacion) {
            return [Text::make('El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre. Puedes guardar el borrador, pero no publicarlo.')];
        }

        $semanas = array_combine(array_keys($this->rangos()), array_map(fn (int $semana): string => "Semana {$semana}", array_keys($this->rangos())));

        return array_map(fn (ComponenteDeEvaluacion $componente): Section => Section::make("{$componente->nombre} · ".Formato::nota($componente->peso).' %')
            ->compact()
            ->columns(['default' => 1, 'md' => 3])
            ->schema([
                Textarea::make("silabo.evaluaciones.{$componente->id}.indicadores")->label('Indicadores')->markAsRequired()->rows(2)->autosize()->maxLength(ContenidoDelSilabo::LIMITE_DE_CELDA),
                Textarea::make("silabo.evaluaciones.{$componente->id}.instrumento")->label('Instrumento')->markAsRequired()->rows(2)->autosize()->maxLength(ContenidoDelSilabo::LIMITE_DE_CELDA),
                Select::make("silabo.evaluaciones.{$componente->id}.semana")->label('Semana')->options($semanas)->native()->placeholder('Sin definir'),
            ]), $esquema->componentes);
    }

    private function cargar(): void
    {
        $silabo = Silabo::query()->with(['semanas', 'evaluaciones'])->where('grupo_id', $this->grupoId)->first();
        $semanas = $silabo?->semanas->keyBy('semana');
        $evaluaciones = $silabo?->evaluaciones->keyBy('componente_codigo');

        $this->silabo = [
            ...$this->valores($silabo?->only(array_keys(ContenidoDelSilabo::TEXTOS)), array_keys(ContenidoDelSilabo::TEXTOS)),
            'semanas' => array_map(
                fn (int $semana): array => $this->valores($semanas?->get($semana)?->only(array_keys(ContenidoDelSilabo::CAMPOS_DE_SEMANA)), array_keys(ContenidoDelSilabo::CAMPOS_DE_SEMANA)),
                array_combine(array_keys($this->rangos()), array_keys($this->rangos())),
            ),
            'evaluaciones' => collect($this->esquema()->componentes ?? [])->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [
                $componente->id => $this->valores($evaluaciones?->get($componente->codigo)?->only(['indicadores', 'instrumento', 'semana']), ['indicadores', 'instrumento', 'semana']),
            ])->all(),
        ];
    }

    /**
     * Form fields hold strings; a missing row or value is an empty field.
     *
     * @param  array<string, mixed>|null  $guardados
     * @param  list<string>  $campos
     * @return array<string, string>
     */
    private function valores(?array $guardados, array $campos): array
    {
        return array_combine($campos, array_map(fn (string $campo): string => is_scalar($guardados[$campo] ?? null) ? (string) $guardados[$campo] : '', $campos));
    }

    /**
     * The form keys evaluation rows by component id; the Actions take component codes. An unknown id makes the Action ask for a reload.
     *
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $codigos = collect($this->esquema()->componentes ?? [])->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => $componente->codigo]);
        $evaluaciones = is_array($this->silabo['evaluaciones'] ?? null) ? $this->silabo['evaluaciones'] : [];

        return [
            ...$this->silabo,
            'evaluaciones' => collect($evaluaciones)->mapWithKeys(fn (mixed $fila, int|string $id): array => [$codigos[$id] ?? "componente-{$id}" => $fila])->all(),
        ];
    }

    /**
     * @return array<int, array{CarbonImmutable, CarbonImmutable}>
     */
    private function rangos(): array
    {
        $grupo = $this->grupo();

        return SemanasDelPeriodo::rangos($grupo->periodoInicio, $grupo->periodoFin);
    }

    private function estado(): ?EstadoSilabo
    {
        $estado = Silabo::query()->where('grupo_id', $this->grupoId)->value('estado');

        return $estado instanceof EstadoSilabo ? $estado : EstadoSilabo::tryFrom(is_string($estado) ? $estado : '');
    }

    private function esquema(): ?EsquemaDeEvaluacion
    {
        return app(EsquemaDelGrupo::class)($this->grupoId);
    }

    private function curso(): DatosDelCurso
    {
        return app(CursoDelGrupo::class)($this->grupoId) ?? abort(404);
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
