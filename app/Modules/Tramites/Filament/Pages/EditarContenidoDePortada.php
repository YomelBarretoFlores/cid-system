<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Pages;

use App\Modules\Tramites\Actions\ConsultarTextosDePortada;
use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Modules\Tramites\Filament\Pages\Schemas\FormularioDeContenidoDePortada;
use App\Modules\Tramites\Support\BorradorDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class EditarContenidoDePortada extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Institución';

    #[\Override]
    protected static ?int $navigationSort = 13;

    #[\Override]
    protected static ?string $navigationLabel = 'Contenido de la portada';

    #[\Override]
    protected static ?string $title = 'Contenido de la portada';

    #[\Override]
    protected static ?string $slug = 'contenido-de-la-portada';

    #[\Override]
    protected ?string $subheading = 'Textos, títulos, foto de portada, pie de página y secciones visibles de la página pública. Los cambios se publican al guardar.';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarTextosDePortada::puedeGuardar($usuario);
    }

    public function mount(ConsultarTextosDePortada $consultar): void
    {
        $this->form->fill(FormularioDeContenidoDePortada::estado($consultar()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components(FormularioDeContenidoDePortada::componentes());
    }

    /**
     * @return list<Action>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('vistaPrevia')->label('Vista previa')->color('gray')
                ->action(fn (ConsultarTextosDePortada $consultar) => $this->abrirVistaPrevia($consultar)),
        ];
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

    public function guardar(GuardarTextosDePortada $guardar, ConsultarTextosDePortada $consultar): void
    {
        try {
            $guardar($this->form->getState(), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        $this->form->fill(FormularioDeContenidoDePortada::estado($consultar()));
        Notification::make()->success()->title('Contenido de la portada guardado')->send();
    }

    // Opened from the server reply, so a blocked pop-up still leaves the link in the notification.
    private function abrirVistaPrevia(ConsultarTextosDePortada $consultar): void
    {
        try {
            BorradorDePortada::guardar($this->form->getState(), $consultar());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        $url = route('panel.portada.vista-previa');
        $this->js('window.open('.Js::from($url).", '_blank', 'noopener')");
        Notification::make()->info()->title('Vista previa lista')->body('Se abrió en otra pestaña. Los cambios aún no se guardaron.')
            ->actions([Action::make('abrir')->label('Abrir vista previa')->url($url, shouldOpenInNewTab: true)])
            ->send();
    }
}
