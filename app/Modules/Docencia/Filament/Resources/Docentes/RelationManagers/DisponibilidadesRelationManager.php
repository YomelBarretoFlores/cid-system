<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers;

use App\Modules\Docencia\Actions\EliminarDisponibilidad;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DisponibilidadesRelationManager extends RelationManager
{
    protected static string $relationship = 'disponibilidades';

    protected static ?string $title = 'Disponibilidad horaria';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->defaultSort(fn ($query) => $query->orderBy('dia_semana')->orderBy('hora_inicio'))
            ->columns([
                TextColumn::make('dia_semana')->label('Día')->formatStateUsing(fn (int $state): string => Disponibilidad::DIAS[$state] ?? (string) $state),
                TextColumn::make('hora_inicio')->label('Desde')->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                TextColumn::make('hora_fin')->label('Hasta')->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
            ])
            ->headerActions([
                Action::make('agregarDisponibilidad')
                    ->label('Agregar franja')
                    ->icon(Heroicon::OutlinedPlus)
                    ->authorize('create')
                    ->schema($this->campos())
                    ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                        $action,
                        fn (): Disponibilidad => $this->guardar($data),
                        'Disponibilidad agregada',
                    )),
            ])
            ->recordActions([
                Action::make('editarDisponibilidad')
                    ->label('Editar')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->authorize('update')
                    ->fillForm(fn (Disponibilidad $record): array => $record->only(['dia_semana', 'hora_inicio', 'hora_fin']))
                    ->schema($this->campos())
                    ->action(fn (Action $action, Disponibilidad $record, array $data) => AccionDeNegocio::ejecutar(
                        $action,
                        fn (): Disponibilidad => $this->guardar($data, $record->id),
                        'Disponibilidad actualizada',
                    )),
                Action::make('quitarDisponibilidad')
                    ->label('Quitar')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->action(fn (Action $action, Disponibilidad $record) => AccionDeNegocio::ejecutar(
                        $action,
                        fn () => app(EliminarDisponibilidad::class)((int) $this->getOwnerRecord()->getKey(), $record->id),
                        'Disponibilidad quitada',
                    )),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardar(array $data, ?int $disponibilidadId = null): Disponibilidad
    {
        return app(GuardarDisponibilidad::class)(
            (int) $this->getOwnerRecord()->getKey(),
            is_numeric($data['dia_semana'] ?? null) ? (int) $data['dia_semana'] : 0,
            is_string($data['hora_inicio'] ?? null) ? $data['hora_inicio'] : '',
            is_string($data['hora_fin'] ?? null) ? $data['hora_fin'] : '',
            $disponibilidadId,
        );
    }

    /**
     * @return list<Select|TimePicker>
     */
    private function campos(): array
    {
        // Availability hours are wall-clock times, so the picker must not shift them to the storage timezone.
        $sinZonaHoraria = config()->string('app.timezone');

        return [
            Select::make('dia_semana')->label('Día')->options(Disponibilidad::DIAS)->required(),
            TimePicker::make('hora_inicio')->label('Desde')->seconds(false)->timezone($sinZonaHoraria)->required(),
            TimePicker::make('hora_fin')->label('Hasta')->seconds(false)->timezone($sinZonaHoraria)->required(),
        ];
    }
}
