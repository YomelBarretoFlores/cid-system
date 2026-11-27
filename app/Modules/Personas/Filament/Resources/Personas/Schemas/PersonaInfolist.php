<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Schemas;

use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Filament\FichaDePersonaEnElPanel;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Ficha de la persona')
                    ->persistTabInQueryString('pestana')
                    ->tabs([
                        Tab::make('Datos personales')->schema([self::datos()]),
                        ...array_map(
                            fn (array $pestana): Tab => Tab::make($pestana['titulo'])->schema([
                                Livewire::make($pestana['componente'], fn (Persona $record): array => ['personaId' => $record->id])->key("ficha-{$pestana['clave']}")->lazy(),
                            ]),
                            app(FichaDePersonaEnElPanel::class)->pestanas(auth()->user()),
                        ),
                    ]),
            ]);
    }

    private static function datos(): Grid
    {
        return Grid::make(3)
            ->schema([
                TextEntry::make('tipoDeDocumento.nombre')->label('Tipo de documento'),
                TextEntry::make('numero_documento')->label('Número de documento')->copyable(),
                TextEntry::make('vinculo.nombre')->label('Tipo de vínculo')->badge(),
                TextEntry::make('apellido_paterno')->label('Apellido paterno'),
                TextEntry::make('apellido_materno')->label('Apellido materno')->placeholder('—'),
                TextEntry::make('nombres')->label('Nombres'),
                TextEntry::make('fecha_nacimiento')->label('Fecha de nacimiento')->date('d/m/Y')->placeholder('—'),
                TextEntry::make('correo')->label('Correo personal')->placeholder('—'),
                TextEntry::make('celular')->label('Celular')->placeholder('—'),
                TextEntry::make('direccion')->label('Dirección')->placeholder('—'),
                TextEntry::make('tratamiento_aceptado_en')->label('Aceptó el tratamiento de datos')->dateTime('d/m/Y H:i', 'America/Lima')->placeholder('Sin consentimiento registrado'),
                TextEntry::make('habilitada_en')->label('Cuenta habilitada')->dateTime('d/m/Y H:i', 'America/Lima')->placeholder('Pendiente: se habilita al validar su primer pago'),
                // A URL state makes Filament skip the disk, so the private file goes through the panel route.
                ImageEntry::make('foto')->label('Fotografía')
                    ->state(fn (Persona $record): ?string => $record->foto === null ? null : route('panel.personas.foto', [$record, 'v' => hash('crc32b', $record->foto)]))
                    ->circular()->imageHeight(120)->placeholder('Sin fotografía'),
            ]);
    }
}
