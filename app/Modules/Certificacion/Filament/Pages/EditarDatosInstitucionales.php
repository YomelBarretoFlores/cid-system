<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Pages;

use App\Modules\Certificacion\Actions\GuardarDatosInstitucionales;
use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class EditarDatosInstitucionales extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    #[\Override]
    protected static ?int $navigationSort = 10;

    #[\Override]
    protected static ?string $navigationLabel = 'Datos institucionales';

    #[\Override]
    protected static ?string $title = 'Datos institucionales';

    #[\Override]
    protected static ?string $slug = 'datos-institucionales';

    #[\Override]
    protected ?string $subheading = 'Textos que imprimen las constancias, los certificados y las actas. Los documentos ya emitidos se vuelven a imprimir con los datos vigentes.';

    #[\Override]
    protected string $view = 'certificacion::filament.datos-institucionales';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarDatosInstitucionales::puedeGuardar($usuario);
    }

    public function mount(DatosInstitucionales $datosInstitucionales): void
    {
        $datos = $datosInstitucionales();

        $this->form->fill([
            'universidad' => $datos->universidad, 'lema' => $datos->lema, 'centro' => $datos->centro, 'direccion' => $datos->direccion,
            'telefono' => $datos->telefono, 'web' => $datos->web, 'facebook' => $datos->facebook, 'ciudad' => $datos->ciudad,
            'formato_numero' => $datos->formatoNumero, 'formato_numero_constancia_notas' => $datos->formatoNumeroConstanciaNotas,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Encabezado')->columns(2)->schema([
                    TextInput::make('universidad')->label('Universidad')->required()->maxLength(150)->columnSpanFull(),
                    TextInput::make('lema')->label('Lema')->required()->maxLength(150),
                    TextInput::make('centro')->label('Centro')->required()->maxLength(150),
                ]),
                Section::make('Pie de página')->columns(2)->schema([
                    TextInput::make('direccion')->label('Dirección')->required()->maxLength(200),
                    TextInput::make('telefono')->label('Teléfono y anexo')->required()->maxLength(100),
                    TextInput::make('web')->label('Web')->required()->maxLength(100),
                    TextInput::make('facebook')->label('Facebook')->required()->maxLength(100),
                ]),
                Section::make('Fecha y numeración')->columns(3)->schema([
                    TextInput::make('ciudad')->label('Ciudad')->required()->maxLength(60),
                    TextInput::make('formato_numero')->label('Número de los documentos')->required()->maxLength(60)
                        ->helperText('{anio}: año; {numero:6}: correlativo con 6 dígitos.'),
                    TextInput::make('formato_numero_constancia_notas')->label('Número de la constancia de notas')->required()->maxLength(60)
                        ->helperText('Por ejemplo: N° {anio} - {numero:05}.'),
                ]),
            ]);
    }

    public function guardar(GuardarDatosInstitucionales $guardar): void
    {
        $datos = $this->form->getState();

        try {
            $guardar($datos, AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Datos institucionales guardados')->send();
    }
}
