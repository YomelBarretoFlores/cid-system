<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CuentasDocentes;

use App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages\ListCuentasDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TablaDeCuentasDocentes
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('persona_id')
                    ->label('Docente')
                    ->formatStateUsing(fn (int $state, ListCuentasDocentes $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('usuario')->label('Usuario')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): ?string => $livewire->cuentaDe($record)?->usuario)
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('estado_de_cuenta')->label('Cuenta')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): string => self::estado($record, $livewire)),
                TextColumn::make('correo')->label('Correo de la cuenta')->color('gray')
                    ->state(fn (Docente $record, ListCuentasDocentes $livewire): ?string => $livewire->cuentaDe($record)?->correo)
                    ->placeholder('Sin correo'),
            ])
            ->recordActions(AccionesDeCuentaDocente::todas());
    }

    private static function estado(Docente $docente, ConCuentasDeDocentes $livewire): string
    {
        $cuenta = $livewire->cuentaDe($docente);

        return AccionesDeCuentaDocente::estado($cuenta);
    }
}
