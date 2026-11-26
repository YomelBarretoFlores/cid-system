<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Pages;

use App\Modules\Personas\Actions\ConsultarTextosLegales;
use App\Modules\Personas\Actions\GuardarTextosLegales;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class EditarPoliticaDePrivacidad extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    #[\Override]
    protected static ?int $navigationSort = 12;

    #[\Override]
    protected static ?string $navigationLabel = 'Política de privacidad';

    #[\Override]
    protected static ?string $title = 'Política de privacidad';

    #[\Override]
    protected static ?string $slug = 'politica-de-privacidad';

    #[\Override]
    protected ?string $subheading = 'Textos de la Ley N.° 29733 que muestra el portal: la política de privacidad y la frase que acepta quien crea su cuenta.';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarTextosLegales::puedeGuardar($usuario);
    }

    public function mount(ConsultarTextosLegales $consultar): void
    {
        $textos = $consultar();

        $this->form->fill([
            'politica_privacidad' => $textos->politica,
            'domicilio_titular' => $textos->domicilio,
            'consentimiento_registro' => $textos->consentimiento,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Política de privacidad')->schema([
                    TextInput::make('domicilio_titular')->label('Domicilio del titular del banco de datos')->required()->maxLength(200)
                        ->helperText('Domicilio legal de la universidad; la política lo imprime donde dice {domicilio}.'),
                    MarkdownEditor::make('politica_privacidad')->label('Texto de la política')->required()->maxLength(20000)
                        ->toolbarButtons([['bold', 'italic', 'link'], ['heading', 'bulletList', 'orderedList', 'table'], ['undo', 'redo']])
                        ->helperText('{universidad} y {centro} se toman de los datos institucionales. El HTML que se escriba se muestra como texto. Al cambiar el texto o el domicilio se actualiza la fecha de la política.'),
                ]),
                Section::make('Registro de cuenta')->schema([
                    Textarea::make('consentimiento_registro')->label('Frase de consentimiento')->required()->maxLength(600)->rows(3)
                        ->helperText('{politica} se muestra como el enlace a la política de privacidad.'),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('guardar')
                ->footer([Actions::make([Action::make('guardar')->label('Guardar')->submit('guardar')])->key('form-actions')]),
        ]);
    }

    public function guardar(GuardarTextosLegales $guardar): void
    {
        try {
            $guardar($this->form->getState(), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Política de privacidad guardada')->send();
    }
}
