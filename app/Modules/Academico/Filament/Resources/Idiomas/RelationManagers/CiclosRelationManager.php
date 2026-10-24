<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\RelationManagers;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Shared\Support\Database\RestriccionUnica;
use Closure;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

final class CiclosRelationManager extends RelationManager
{
    public const string NUMERO_REPETIDO = 'El plan de estudios de este idioma ya tiene ese ciclo.';

    protected static string $relationship = 'ciclos';

    protected static ?string $title = 'Plan de estudios';

    protected static ?string $modelLabel = 'ciclo';

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
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('idioma_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages(['unique' => self::NUMERO_REPETIDO]),
                Select::make('nivel')
                    ->label('Nivel')
                    ->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())
                    ->required(),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(60),
                TextInput::make('horas_lectivas')->label('Horas lectivas')->integer()->minValue(1)->maxValue(32767)->required(),
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
                    ->using(fn (Action $action, array $data): Ciclo => $this->guardarSinRepetir(
                        $action,
                        fn (): Ciclo => Ciclo::query()->create([...$data, 'idioma_id' => $this->getOwnerRecord()->getKey()]),
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (Action $action, Ciclo $record, array $data): Ciclo => $this->guardarSinRepetir(
                        $action,
                        function () use ($record, $data): Ciclo {
                            $record->update($data);

                            return $record;
                        },
                    )),
            ]);
    }

    /**
     * @param  Closure(): Ciclo  $guardar
     */
    private function guardarSinRepetir(Action $action, Closure $guardar): Ciclo
    {
        try {
            // The savepoint keeps the connection usable after a unique violation inside an outer transaction.
            return DB::transaction($guardar);
        } catch (UniqueConstraintViolationException $error) {
            // Another coordinator used the same cycle number after validation ran.
            throw RestriccionUnica::es($error, 'aca_ciclos_idioma_id_numero_unique')
                ? ValidationException::withMessages(["mountedActions.{$action->getNestingIndex()}.data.numero" => self::NUMERO_REPETIDO])
                : $error;
        }
    }
}
