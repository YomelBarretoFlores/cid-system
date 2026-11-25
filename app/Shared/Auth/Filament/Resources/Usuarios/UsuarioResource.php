<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios;

use App\Shared\Auth\Filament\CamposDePermisos;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use UnitEnum;

final class UsuarioResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = User::class;

    #[\Override]
    protected static ?string $slug = 'usuarios';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Seguridad';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Usuarios del personal';

    #[\Override]
    protected static ?string $modelLabel = 'usuario del personal';

    #[\Override]
    protected static ?string $pluralModelLabel = 'usuarios del personal';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    protected static bool $isGloballySearchable = false;

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return User::query()
            ->where(fn (Builder $consulta): Builder => $consulta->permission(User::ADMIN_PANEL_PERMISSION)->orWhereNotNull('desactivado_en'))
            ->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                TextInput::make('email')->label('Correo')->email()->required()->maxLength(255)
                    ->disabledOn('edit')
                    ->helperText('Recibirá en este correo el enlace para crear su contraseña.')
                    ->rule(fn (string $operation): Closure => function (string $attribute, mixed $value, Closure $fail) use ($operation): void {
                        if ($operation === 'create' && is_string($value) && User::query()->where('email', mb_strtolower(trim($value)))->exists()) {
                            $fail('Ya existe una cuenta con ese correo.');
                        }
                    }),
                Select::make('roles')->label('Roles')->multiple()->columnSpanFull()
                    ->options(fn (): array => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'id')->all()),
                CamposDePermisos::make('Permisos adicionales')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('roles.name')->label('Roles')->listWithLineBreaks()->placeholder('Sin roles'),
                TextColumn::make('estado')->label('Estado')
                    ->state(fn (User $record): string => $record->desactivado_en === null ? 'Activo' : 'Desactivado'),
                TextColumn::make('doble_factor')->label('Doble factor configurado')
                    ->state(fn (User $record): string => $record->getRawOriginal('app_authentication_secret') === null ? 'No' : 'Sí'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), ...AccionesDeUsuario::todas()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'create' => CreateUsuario::route('/create'),
            'edit' => EditUsuario::route('/{record}/edit'),
        ];
    }
}
