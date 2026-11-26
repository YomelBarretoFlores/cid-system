<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Pages;

use App\Modules\Tramites\Actions\ConsultarTextosDePortada;
use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
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
final class EditarContenidoDePortada extends Page
{
    private const string AYUDA_LUGARES = '{lugares_de_pago} se reemplaza por los medios de pago activos del catálogo.';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    #[\Override]
    protected static ?int $navigationSort = 13;

    #[\Override]
    protected static ?string $navigationLabel = 'Contenido de la portada';

    #[\Override]
    protected static ?string $title = 'Contenido de la portada';

    #[\Override]
    protected static ?string $slug = 'contenido-de-la-portada';

    #[\Override]
    protected ?string $subheading = 'Textos de la página pública: presentación del centro, pasos, niveles y preguntas frecuentes. Los cambios se publican al guardar.';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarTextosDePortada::puedeGuardar($usuario);
    }

    public function mount(ConsultarTextosDePortada $consultar): void
    {
        $textos = $consultar();

        $this->form->fill([
            'nosotros' => $textos->nosotros, 'pasos_matricula' => $textos->pasosMatricula, 'pasos_clasificacion' => $textos->pasosClasificacion,
            'niveles' => $textos->niveles, 'preguntas' => $textos->preguntas,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('El centro')->schema([
                    Textarea::make('nosotros')->label('Presentación del centro')->required()->maxLength(2000)->rows(5)
                        ->helperText('Separe los párrafos con una línea en blanco.'),
                ]),
                Section::make('Cómo matricularse')->schema([self::pasos('pasos_matricula')]),
                Section::make('Examen de clasificación')->schema([self::pasos('pasos_clasificacion')]),
                Section::make('Ruta de aprendizaje')->schema([
                    Repeater::make('niveles')->hiddenLabel()->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar nivel')->schema([
                        TextInput::make('nombre')->label('Nivel')->required()->maxLength(40),
                        Textarea::make('descripcion')->label('Descripción')->required()->maxLength(300)->rows(2),
                        Textarea::make('logro')->label('Al concluir el nivel podrá')->required()->maxLength(300)->rows(2),
                    ]),
                ]),
                Section::make('Preguntas frecuentes')->description(self::AYUDA_LUGARES)->schema([
                    Repeater::make('preguntas')->hiddenLabel()->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar pregunta')->schema([
                        TextInput::make('pregunta')->label('Pregunta')->required()->maxLength(150),
                        Textarea::make('respuesta')->label('Respuesta')->required()->maxLength(600)->rows(3),
                    ]),
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

    public function guardar(GuardarTextosDePortada $guardar): void
    {
        try {
            $guardar($this->form->getState(), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Contenido de la portada guardado')->send();
    }

    private static function pasos(string $lista): Repeater
    {
        return Repeater::make($lista)->hiddenLabel()->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar paso')
            ->helperText(self::AYUDA_LUGARES)
            ->schema([
                TextInput::make('titulo')->label('Paso')->required()->maxLength(80),
                Textarea::make('detalle')->label('Detalle')->required()->maxLength(300)->rows(2),
            ]);
    }
}
