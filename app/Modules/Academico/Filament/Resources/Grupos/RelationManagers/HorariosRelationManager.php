<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\RelationManagers;

use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Actions\QuitarHorario;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class HorariosRelationManager extends RelationManager
{
    protected static string $relationship = 'horarios';

    protected static ?string $title = 'Horarios';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                TextColumn::make('dia_semana')->label('Día')->formatStateUsing(fn (int $state): string => Horario::DIAS[$state] ?? (string) $state),
                TextColumn::make('hora_inicio')->label('Desde')->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                TextColumn::make('hora_fin')->label('Hasta')->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
            ])
            ->headerActions([
                $this->agregar(),
            ])
            ->recordActions([
                $this->editar(),
                DeleteAction::make()
                    ->hidden(fn (): bool => $this->grupoCongelado())
                    ->action(fn (Action $action, Horario $record) => AccionDeNegocio::ejecutar(
                        $action,
                        fn () => app(QuitarHorario::class)((int) $this->getOwnerRecord()->getKey(), $record->id),
                        'Horario eliminado',
                    )),
            ]);
    }

    private function agregar(): Action
    {
        return Action::make('agregarHorario')
            ->label('Agregar horario')
            ->icon(Heroicon::OutlinedPlus)
            ->authorize('create')
            ->hidden(fn (): bool => $this->grupoCongelado())
            ->schema($this->campos())
            ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Horario => $this->guardar($data),
                'Horario agregado',
            ));
    }

    private function editar(): Action
    {
        return Action::make('editarHorario')
            ->label('Editar')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->authorize('update')
            ->hidden(fn (): bool => $this->grupoCongelado())
            ->fillForm(fn (Horario $record): array => $record->only(['dia_semana', 'hora_inicio', 'hora_fin']))
            ->schema($this->campos())
            ->action(fn (Action $action, Horario $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Horario => $this->guardar($data, $record->id),
                'Horario actualizado',
            ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardar(array $data, ?int $horarioId = null): Horario
    {
        return app(GuardarHorario::class)(
            (int) $this->getOwnerRecord()->getKey(),
            is_numeric($data['dia_semana'] ?? null) ? (int) $data['dia_semana'] : 0,
            is_string($data['hora_inicio'] ?? null) ? $data['hora_inicio'] : '',
            is_string($data['hora_fin'] ?? null) ? $data['hora_fin'] : '',
            $horarioId,
        );
    }

    /**
     * @return list<Select|TimePicker>
     */
    private function campos(): array
    {
        // Class hours are wall-clock times, so the picker must not shift them to the storage timezone.
        $sinZonaHoraria = config()->string('app.timezone');

        return [
            Select::make('dia_semana')->label('Día')->options(Horario::DIAS)->required(),
            TimePicker::make('hora_inicio')->label('Desde')->seconds(false)->timezone($sinZonaHoraria)->required(),
            TimePicker::make('hora_fin')->label('Hasta')->seconds(false)->timezone($sinZonaHoraria)->required(),
        ];
    }

    private function grupoCongelado(): bool
    {
        $grupo = $this->getOwnerRecord();

        return $grupo instanceof Grupo && $grupo->estado->esTerminal();
    }
}
