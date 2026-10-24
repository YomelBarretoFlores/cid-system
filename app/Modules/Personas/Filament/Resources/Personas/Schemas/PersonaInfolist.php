<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Schemas;

use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Persona')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('tipo_documento')->label('Tipo de documento')->formatStateUsing(fn (TipoDocumento $state): string => $state->etiqueta()),
                        TextEntry::make('numero_documento')->label('Número de documento')->copyable(),
                        TextEntry::make('tipo_vinculo')->label('Tipo de vínculo')->badge()->formatStateUsing(fn (TipoVinculo $state): string => $state->etiqueta()),
                        TextEntry::make('apellido_paterno')->label('Apellido paterno'),
                        TextEntry::make('apellido_materno')->label('Apellido materno')->placeholder('—'),
                        TextEntry::make('nombres')->label('Nombres'),
                        TextEntry::make('fecha_nacimiento')->label('Fecha de nacimiento')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('correo')->label('Correo personal')->placeholder('—'),
                        TextEntry::make('celular')->label('Celular')->placeholder('—'),
                    ]),
            ]);
    }
}
