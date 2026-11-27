<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Schemas;

use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Modalidad;
use App\Modules\Academico\Models\Programa;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\DocentesHabilitados;
use App\Modules\Docencia\Data\DocenteAsignado;
use App\Modules\Docencia\Enums\PermisoDocencia;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class GrupoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Grupo')->columns(2)->schema(self::grupo()),
                Section::make('Horario')
                    ->description('Días y horas de clase. Después de crear el grupo, el horario se ajusta en su ficha.')
                    ->visibleOn('create')
                    ->schema([self::campoDeHorarios()]),
                Section::make('Docente')
                    ->description(fn (string $operation): ?string => $operation === 'create' ? 'Puedes asignarlo ahora o después desde la ficha del grupo.' : null)
                    ->schema([self::docenteActual(), self::docente()])
                    ->visible(fn (string $operation): bool => $operation === 'view' || ($operation === 'create' && self::asignaDocentes())),
            ]);
    }

    /**
     * @return list<Component>
     */
    private static function grupo(): array
    {
        return [
            Select::make('periodo_id')
                ->label('Periodo')
                ->relationship('periodo', 'nombre')
                ->preload()
                ->required()
                ->disabledOn('edit'),
            Select::make('ciclo_id')
                ->label('Idioma y ciclo')
                ->relationship('ciclo', 'nombre', fn (Builder $query): Builder => $query
                    ->where('activo', true)
                    ->whereHas('idioma', fn (Builder $idioma): Builder => $idioma->where('activo', true))
                    ->with('idioma')
                    ->orderBy('idioma_id')
                    ->orderBy('numero'))
                ->getOptionLabelFromRecordUsing(fn (Ciclo $record): string => self::etiquetaDeCiclo($record))
                ->preload()
                ->required()
                ->live()
                ->disabledOn('edit')
                ->helperText('El periodo y el ciclo no cambian después de crear el grupo.'),
            Select::make('programa_id')
                ->label('Programa')
                ->relationship('programa', 'nombre', fn (Builder $query, ?Grupo $record): Builder => $query
                    ->where(fn (Builder $disponibles): Builder => $disponibles->where('activo', true)->when($record?->programa_id, fn (Builder $q, int $actual): Builder => $q->orWhereKey($actual)))
                    ->orderBy('nombre'))
                ->default(fn (): ?int => Programa::query()->where('por_defecto', true)->value('id'))
                ->preload()
                ->required()
                ->helperText('El régimen del programa decide si el grupo puede ser regular o acelerado.'),
            TextInput::make('codigo')->label('Código de grupo')->required()->maxLength(20),
            Select::make('modalidad')
                ->label('Modalidad')
                ->options(fn (?Grupo $record): array => self::modalidades($record?->modalidad))
                ->required()
                ->live(),
            Toggle::make('es_acelerado')
                ->label('Acelerado')
                ->default(false)
                ->live()
                ->helperText(fn (Get $get, ?Grupo $record): ?string => self::segundoCiclo($get, $record)),
            Select::make('aula_id')
                ->label('Aula')
                ->relationship('aula', 'codigo', fn (Builder $query, ?Grupo $record): Builder => $query
                    ->where(fn (Builder $disponibles): Builder => $disponibles->where('activo', true)->when($record?->aula_id, fn (Builder $q, int $actual): Builder => $q->orWhereKey($actual)))
                    ->orderBy('codigo'))
                ->getOptionLabelFromRecordUsing(fn (Aula $record): string => "{$record->codigo} · {$record->nombre} ({$record->capacidad} estudiantes)")
                ->preload()
                ->required()
                ->visible(fn (Get $get): bool => ! self::esVirtual($get)),
            TextInput::make('enlace_virtual')
                ->label('Enlace de la clase virtual')
                ->url()
                ->maxLength(255)
                ->required(fn (Get $get): bool => self::esVirtual($get))
                ->visible(fn (Get $get): bool => self::modalidad($get)->usa_enlace ?? false),
            TextInput::make('cupo_maximo')->label('Cupo máximo')->integer()->minValue(1)->maxValue(999)->required(),
            TextInput::make('cupo_minimo')->label('Cupo mínimo de apertura')->integer()->minValue(0)->maxValue(999)->required(),
        ];
    }

    private static function campoDeHorarios(): Repeater
    {
        // Class hours are wall-clock times, so the picker must not shift them to the storage timezone.
        $sinZonaHoraria = config()->string('app.timezone');

        return Repeater::make('horarios')
            ->hiddenLabel()
            ->addActionLabel('Agregar día de clase')
            ->columns(3)
            ->defaultItems(0)
            ->schema([
                Select::make('dia_semana')->label('Día')->options(Horario::DIAS)->required(),
                TimePicker::make('hora_inicio')->label('Desde')->seconds(false)->timezone($sinZonaHoraria)->required(),
                TimePicker::make('hora_fin')->label('Hasta')->seconds(false)->timezone($sinZonaHoraria)->required(),
            ]);
    }

    private static function docente(): Select
    {
        return Select::make('docente_id')
            ->label('Docente')
            ->placeholder('Por asignar')
            ->helperText('Docentes activos habilitados para el idioma y el nivel del ciclo; el horario debe estar dentro de su disponibilidad.')
            ->options(fn (Get $get): array => self::docentesHabilitados($get('ciclo_id')))
            ->searchable()
            ->visibleOn('create');
    }

    private static function docenteActual(): TextInput
    {
        return TextInput::make('docente_actual')
            ->label('Docente')
            ->formatStateUsing(fn (?Grupo $record): string => $record instanceof Grupo ? self::nombreDelDocente($record) : '')
            ->dehydrated(false)
            ->visibleOn('view');
    }

    private static function nombreDelDocente(Grupo $grupo): string
    {
        $docente = app(DocenteDelGrupo::class)($grupo->id);

        if (! $docente instanceof DocenteAsignado) {
            return 'Por asignar';
        }

        return $docente->nombreCompleto;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{dia: int, desde: string, hasta: string}>
     */
    public static function horarios(array $data): array
    {
        $filas = is_array($data['horarios'] ?? null) ? array_values(array_filter($data['horarios'], is_array(...))) : [];

        return array_map(fn (array $fila): array => [
            'dia' => self::entero($fila['dia_semana'] ?? null),
            'desde' => self::texto($fila['hora_inicio'] ?? null),
            'hasta' => self::texto($fila['hora_fin'] ?? null),
        ], $filas);
    }

    /**
     * @return array<int, string>
     */
    public static function docentesHabilitados(mixed $cicloId): array
    {
        $ciclo = is_numeric($cicloId) ? Ciclo::query()->find((int) $cicloId, ['id', 'idioma_id', 'nivel']) : null;

        return $ciclo === null ? [] : app(DocentesHabilitados::class)($ciclo->idioma_id, $ciclo->nivel);
    }

    private static function asignaDocentes(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Gestionar->value) === true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeGrupo
    {
        return new DatosDeGrupo(
            periodoId: self::entero($data['periodo_id'] ?? null),
            cicloId: self::entero($data['ciclo_id'] ?? null),
            codigo: trim(self::texto($data['codigo'] ?? null)),
            modalidad: self::texto($data['modalidad'] ?? null),
            esAcelerado: (bool) ($data['es_acelerado'] ?? false),
            cupoMaximo: self::entero($data['cupo_maximo'] ?? null),
            cupoMinimo: self::entero($data['cupo_minimo'] ?? null),
            programaId: self::entero($data['programa_id'] ?? null),
            aulaId: filled($data['aula_id'] ?? null) ? self::entero($data['aula_id']) : null,
            enlaceVirtual: filled($data['enlace_virtual'] ?? null) ? trim(self::texto($data['enlace_virtual'])) : null,
        );
    }

    public static function etiquetaDeCiclo(Ciclo $ciclo): string
    {
        return "{$ciclo->idioma->nombre} · Ciclo {$ciclo->numero} ({$ciclo->nivel->etiqueta()})";
    }

    public static function etiquetaDeGrupo(Grupo $grupo): string
    {
        $ciclo = $grupo->ciclo;

        return $grupo->cicloHasta === null
            ? self::etiquetaDeCiclo($ciclo)
            : "{$ciclo->idioma->nombre} · Ciclos {$ciclo->numero} y {$grupo->cicloHasta->numero} ({$ciclo->nivel->etiqueta()})";
    }

    private static function segundoCiclo(Get $get, ?Grupo $record): ?string
    {
        if (! (bool) $get('es_acelerado')) {
            return null;
        }

        $ciclo = $record->ciclo ?? Ciclo::query()->find(self::entero($get('ciclo_id')));

        if ($ciclo === null) {
            return 'Cubre también el ciclo siguiente del mismo nivel.';
        }

        $siguiente = $record->cicloHasta->nombre ?? Ciclo::query()->siguienteDelNivel($ciclo)->value('nombre');

        return $siguiente === null ? "{$ciclo->nombre} no tiene ciclo siguiente en su nivel." : "Cubre también: {$siguiente}";
    }

    /**
     * The active modalities plus the one stored, so editing a group never drops an inactive value.
     *
     * @return array<string, string>
     */
    public static function modalidades(?string $guardada = null, bool $todas = false): array
    {
        return Modalidad::query()
            ->when(! $todas, fn (Builder $query): Builder => $query->where(fn (Builder $q): Builder => $q->where('activo', true)->orWhere('codigo', $guardada)))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo')
            ->all();
    }

    private static function modalidad(Get $get): ?Modalidad
    {
        $codigo = self::texto($get('modalidad'));

        return $codigo === '' ? null : Modalidad::query()->where('codigo', $codigo)->first();
    }

    // Without a classroom the class link is where the group meets.
    private static function esVirtual(Get $get): bool
    {
        $modalidad = self::modalidad($get);

        return $modalidad instanceof Modalidad && ! $modalidad->usa_aula;
    }

    private static function entero(mixed $valor): int
    {
        return is_numeric($valor) ? (int) $valor : 0;
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
