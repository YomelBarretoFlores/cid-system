<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Filament;

use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Enums\Parametro;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Actions\Action;
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
final class EditarParametrosDelSistema extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Institución';

    #[\Override]
    protected static ?int $navigationSort = 30;

    #[\Override]
    protected static ?string $navigationLabel = 'Parámetros del sistema';

    #[\Override]
    protected static ?string $title = 'Parámetros del sistema';

    #[\Override]
    protected static ?string $slug = 'parametros-del-sistema';

    #[\Override]
    protected ?string $subheading = 'Valores operativos que aplican todos los módulos. Los cambios rigen desde que se guardan; lo ya registrado conserva el valor con que se registró.';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarParametrosDelSistema::puedeGuardar($usuario);
    }

    public function mount(ParametrosDelSistema $parametrosDelSistema): void
    {
        $this->form->fill($parametrosDelSistema()->comoArreglo());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Clasificación')->columns(2)->schema([
                    $this->campo(Parametro::DiasVigenciaClasificacion, 'Vigencia de la solicitud de examen (días)'),
                    $this->campo(Parametro::PuntajeMaximoClasificacion, 'Puntaje máximo del examen', 'El puntaje se registra de 0 a este valor.'),
                ]),
                Section::make('Asistencia docente')->columns(2)->schema([
                    $this->campo(Parametro::InicioAbreMinutosAntes, 'Llamado de inicio: abre (minutos antes del inicio)', 'Cierra al terminar la clase.'),
                    $this->campo(Parametro::CierreAbreMinutosAntesDelFin, 'Llamado de cierre: abre (minutos antes del fin)'),
                    $this->campo(Parametro::CierreCierraMinutosDespuesDelFin, 'Llamado de cierre: cierra (minutos después del fin)'),
                    $this->campo(Parametro::ConfirmacionMinutosDespuesDelFin, 'Confirmación del delegado (minutos después del fin)', 'No puede vencer antes de que cierre el llamado de cierre.'),
                ]),
                Section::make('Archivos')->columns(3)->schema([
                    $this->campo(Parametro::MaximoAdjuntosTramite, 'Adjuntos por trámite'),
                    $this->campo(Parametro::AdjuntoMaximoMb, 'Tamaño de cada adjunto (MB)'),
                    $this->campo(Parametro::ReciboMaximoMb, 'Tamaño del recibo de pago (MB)'),
                    $this->campo(Parametro::FotoMaximoMb, 'Tamaño de la fotografía (MB)'),
                    $this->campo(Parametro::FotoLadoMinimoPx, 'Lado mínimo de la fotografía (píxeles)'),
                    $this->campo(Parametro::FotoLadoMaximoPx, 'Lado máximo de la fotografía (píxeles)'),
                ]),
                Section::make('Portal público')->columns(2)->schema([
                    $this->campo(Parametro::UmbralPocasVacantes, 'Aviso de pocas vacantes', 'El grupo se resalta cuando le quedan estas vacantes o menos. 0 lo desactiva.'),
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

    public function guardar(GuardarParametrosDelSistema $guardar): void
    {
        try {
            $guardar($this->form->getState(), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Parámetros del sistema guardados')->send();
    }

    private function campo(Parametro $parametro, string $etiqueta, ?string $ayuda = null): TextInput
    {
        return TextInput::make($parametro->value)
            ->label($etiqueta)
            ->required()
            ->integer()
            ->minValue($parametro->minimo())
            ->maxValue($parametro->maximo())
            ->helperText(trim(($ayuda ?? '')." De {$parametro->minimo()} a {$parametro->maximo()}."));
    }
}
