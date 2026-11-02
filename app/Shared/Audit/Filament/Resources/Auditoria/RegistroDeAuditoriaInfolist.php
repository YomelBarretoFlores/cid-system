<?php

declare(strict_types=1);

namespace App\Shared\Audit\Filament\Resources\Auditoria;

use App\Shared\Audit\Enums\OperacionAuditada;
use App\Shared\Audit\Models\RegistroDeAuditoria;
use App\Shared\Audit\Support\CambiosDelRegistro;
use App\Shared\Audit\Support\NombresDeTablas;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RegistroDeAuditoriaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Registro')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('ocurrido_en')->label('Fecha y hora')->dateTime('d/m/Y H:i:s', 'America/Lima'),
                        TextEntry::make('tabla')->label('Tabla')->formatStateUsing(fn (string $state): string => NombresDeTablas::de($state)),
                        TextEntry::make('operacion')->label('Operación')->formatStateUsing(fn (OperacionAuditada $state): string => $state->etiqueta()),
                        TextEntry::make('registro_id')->label('Registro')->placeholder('—'),
                        TextEntry::make('usuario.name')->label('Usuario')->placeholder('Sistema'),
                        TextEntry::make('ip')->label('IP')->placeholder('—'),
                        TextEntry::make('solicitud_id')->label('Solicitud')->placeholder('—')->columnSpan(2),
                    ]),
                Section::make('Cambios')
                    ->schema([
                        RepeatableEntry::make('cambios')
                            ->hiddenLabel()
                            ->state(fn (RegistroDeAuditoria $record): array => CambiosDelRegistro::calcular($record->datos_anteriores, $record->datos_nuevos))
                            ->placeholder('Sin cambios en los datos.')
                            ->table(fn (RegistroDeAuditoria $record): array => self::columnas($record->operacion))
                            ->schema(fn (RegistroDeAuditoria $record): array => self::celdas($record->operacion)),
                    ]),
            ]);
    }

    /**
     * @return list<TableColumn>
     */
    private static function columnas(OperacionAuditada $operacion): array
    {
        return array_values(array_map(TableColumn::make(...), self::campos($operacion)));
    }

    /**
     * @return list<TextEntry>
     */
    private static function celdas(OperacionAuditada $operacion): array
    {
        return array_map(
            fn (string $campo): TextEntry => TextEntry::make($campo)->placeholder('—')->wrap(),
            array_keys(self::campos($operacion)),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function campos(OperacionAuditada $operacion): array
    {
        return match ($operacion) {
            OperacionAuditada::Alta => ['campo' => 'Campo', 'despues' => 'Después'],
            OperacionAuditada::Baja => ['campo' => 'Campo', 'antes' => 'Antes'],
            OperacionAuditada::Cambio => ['campo' => 'Campo', 'antes' => 'Antes', 'despues' => 'Después'],
        };
    }
}
