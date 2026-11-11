<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\RelationManagers;

use App\Modules\Academico\Actions\GuardarCiclo;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

final class CiclosRelationManager extends RelationManager
{
    public const string NUMERO_REPETIDO = 'El plan de estudios de este idioma ya tiene ese ciclo.';

    #[\Override]
    protected static string $relationship = 'ciclos';

    #[\Override]
    protected static ?string $title = 'Plan de estudios';

    #[\Override]
    protected static ?string $modelLabel = 'ciclo';

    #[\Override]
    protected static ?string $pluralModelLabel = 'ciclos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('numero')
                    ->label('Ciclo')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(12)
                    ->required()
                    ->disabled(fn (?Ciclo $record): bool => $this->tieneGrupos($record))
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('idioma_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages(['unique' => self::NUMERO_REPETIDO]),
                Select::make('nivel')
                    ->label('Nivel')
                    ->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())
                    ->required()
                    ->disabled(fn (?Ciclo $record): bool => $this->tieneGrupos($record)),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(60),
                TextInput::make('horas_lectivas')->label('Horas lectivas')->integer()->minValue(1)->maxValue(32767)->required()
                    ->disabled(fn (?Ciclo $record): bool => $this->tieneGrupos($record))
                    ->helperText(fn (?Ciclo $record): ?string => $this->tieneGrupos($record) ? 'El ciclo ya tiene grupos: su número, nivel y horas lectivas no cambian.' : null),
                Toggle::make('activo')->label('Disponible para nuevos grupos')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('numero')->label('Ciclo')->sortable(),
                TextColumn::make('nivel')->label('Nivel')->badge()->formatStateUsing(fn (NivelCurricular $state): string => $state->etiqueta()),
                TextColumn::make('nombre')->label('Nombre'),
                TextColumn::make('horas_lectivas')->label('Horas lectivas'),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('numero')
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo ciclo')
                    ->using(fn (Action $action, array $data): Ciclo => $this->guardar($action, $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (Action $action, Ciclo $record, array $data): Ciclo => $this->guardar($action, $data, $record)),
            ]);
    }

    private function tieneGrupos(?Ciclo $ciclo): bool
    {
        return $ciclo instanceof Ciclo && Grupo::query()->where('ciclo_id', $ciclo->id)->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardar(Action $action, array $data, ?Ciclo $ciclo = null): Ciclo
    {
        // Locked fields are not sent, so they keep the cycle's current values.
        $valores = [...($ciclo?->only(['numero', 'nivel', 'horas_lectivas']) ?? []), ...$data];

        try {
            return app(GuardarCiclo::class)((int) $this->getOwnerRecord()->getKey(), $this->datosDeCiclo($valores), $ciclo?->id);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        } catch (UniqueConstraintViolationException $error) {
            // Another coordinator used the same cycle number after validation ran.
            throw RestriccionUnica::es($error, 'aca_ciclos_idioma_id_numero_unique')
                ? ValidationException::withMessages(["mountedActions.{$action->getNestingIndex()}.data.numero" => self::NUMERO_REPETIDO])
                : $error;
        }
    }

    /**
     * @param  array<string, mixed>  $valores
     */
    private function datosDeCiclo(array $valores): DatosDeCiclo
    {
        $nivel = $valores['nivel'] ?? null;

        return new DatosDeCiclo(
            numero: is_numeric($valores['numero'] ?? null) ? (int) $valores['numero'] : 0,
            nivel: $nivel instanceof NivelCurricular ? $nivel : NivelCurricular::from(is_string($nivel) ? $nivel : ''),
            nombre: is_string($valores['nombre'] ?? null) ? $valores['nombre'] : '',
            horasLectivas: is_numeric($valores['horas_lectivas'] ?? null) ? (int) $valores['horas_lectivas'] : 0,
            activo: (bool) ($valores['activo'] ?? true),
        );
    }
}
