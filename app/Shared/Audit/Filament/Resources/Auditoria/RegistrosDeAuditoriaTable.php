<?php

declare(strict_types=1);

namespace App\Shared\Audit\Filament\Resources\Auditoria;

use App\Shared\Audit\Enums\OperacionAuditada;
use App\Shared\Audit\Models\RegistroDeAuditoria;
use App\Shared\Audit\Support\NombresDeTablas;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class RegistrosDeAuditoriaTable
{
    private const string ZONA = 'America/Lima';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ocurrido_en')->label('Fecha y hora')->dateTime('d/m/Y H:i:s', self::ZONA)->sortable(),
                TextColumn::make('tabla')->label('Tabla')->formatStateUsing(fn (string $state): string => NombresDeTablas::de($state)),
                TextColumn::make('operacion')->label('Operación')->formatStateUsing(fn (OperacionAuditada $state): string => $state->etiqueta()),
                TextColumn::make('registro_id')->label('Registro'),
                TextColumn::make('usuario.name')->label('Usuario')->placeholder('Sistema'),
                TextColumn::make('ip')->label('IP')->placeholder('—'),
                TextColumn::make('solicitud_id')->label('Solicitud')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('ocurrido_en')->orderByDesc('id'))
            ->paginated([25, 50, 100])
            ->filters([
                SelectFilter::make('tabla')->label('Tabla')->options(fn (): array => self::tablas())->searchable(),
                SelectFilter::make('operacion')->label('Operación')->options(OperacionAuditada::opciones()),
                SelectFilter::make('usuario_id')->label('Usuario')->options(fn (): array => self::usuarios())->searchable(),
                self::porFecha(),
                self::porRegistro(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function tablas(): array
    {
        return RegistroDeAuditoria::query()->distinct()->orderBy('tabla')->pluck('tabla')
            ->mapWithKeys(fn (string $tabla): array => [$tabla => NombresDeTablas::de($tabla)])
            ->sort()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function usuarios(): array
    {
        return User::query()
            ->whereIn('id', RegistroDeAuditoria::query()->select('usuario_id')->whereNotNull('usuario_id')->distinct())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function porFecha(): Filter
    {
        return Filter::make('ocurrido_en')
            ->schema([
                DatePicker::make('desde')->label('Desde'),
                DatePicker::make('hasta')->label('Hasta'),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['desde'] ?? null, fn (Builder $consulta, string $desde): Builder => $consulta->where('ocurrido_en', '>=', self::limite($desde, inicio: true)))
                ->when($data['hasta'] ?? null, fn (Builder $consulta, string $hasta): Builder => $consulta->where('ocurrido_en', '<=', self::limite($hasta, inicio: false))));
    }

    // The offset travels with the value: a bare timestamp would be read in the database session's zone.
    private static function limite(string $fecha, bool $inicio): string
    {
        $dia = CarbonImmutable::parse($fecha, self::ZONA);

        return ($inicio ? $dia->startOfDay() : $dia->endOfDay())->format('Y-m-d H:i:s.uP');
    }

    private static function porRegistro(): Filter
    {
        return Filter::make('registro_id')
            ->schema([TextInput::make('registro_id')->label('Registro (ID)')->integer()])
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                filled($data['registro_id'] ?? null),
                fn (Builder $consulta): Builder => $consulta->where('registro_id', (int) $data['registro_id']),
            ));
    }
}
