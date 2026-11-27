<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Roles;

use App\Shared\Auth\Actions\EliminarRol;
use App\Shared\Auth\Filament\CamposDePermisos;
use App\Shared\Auth\Filament\Resources\Roles\Pages\CreateRol;
use App\Shared\Auth\Filament\Resources\Roles\Pages\EditRol;
use App\Shared\Auth\Filament\Resources\Roles\Pages\ListRoles;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use UnitEnum;

final class RolResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Role::class;

    #[\Override]
    protected static ?string $slug = 'roles';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Seguridad';

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    protected static ?string $navigationLabel = 'Roles';

    #[\Override]
    protected static ?string $modelLabel = 'rol';

    #[\Override]
    protected static ?string $pluralModelLabel = 'roles';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    protected static bool $isGloballySearchable = false;

    /**
     * @return Builder<Role>
     */
    public static function getEloquentQuery(): Builder
    {
        return Role::query()->where('guard_name', 'web')->withCount(['permissions', 'users']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(125)
                    ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                    ->validationMessages(['unique' => 'Ya existe un rol con ese nombre.']),
                CamposDePermisos::make('Permisos')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('permissions_count')->label('Permisos'),
                TextColumn::make('users_count')->label('Usuarios'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), self::eliminar()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRol::route('/create'),
            'edit' => EditRol::route('/{record}/edit'),
        ];
    }

    private static function eliminar(): Action
    {
        return Action::make('eliminar')
            ->label('Eliminar')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->authorize('delete')
            ->requiresConfirmation()
            ->modalHeading('Eliminar rol')
            ->modalDescription('Solo se puede eliminar un rol que ninguna cuenta tiene.')
            ->action(fn (Action $action, Role $record) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(EliminarRol::class)($record, $usuario),
                'Rol eliminado',
            ));
    }
}
