<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Pages;

use App\Modules\Certificacion\Actions\GuardarTextosDeDocumentos;
use App\Modules\Certificacion\Contracts\TextosDeDocumentos;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use BackedEnum;
use Filament\Forms\Components\Textarea;
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
final class EditarTextosDeDocumentos extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    #[\Override]
    protected static ?int $navigationSort = 11;

    #[\Override]
    protected static ?string $navigationLabel = 'Textos de documentos';

    #[\Override]
    protected static ?string $title = 'Textos de documentos';

    #[\Override]
    protected static ?string $slug = 'textos-de-documentos';

    #[\Override]
    protected ?string $subheading = 'Frases que imprimen las constancias, la acreditación y el certificado. Cada documento conserva los textos vigentes al emitirse; los ya emitidos no cambian.';

    #[\Override]
    protected string $view = 'certificacion::filament.datos-institucionales';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && GuardarTextosDeDocumentos::puedeGuardar($usuario);
    }

    public function mount(TextosDeDocumentos $textosDeDocumentos): void
    {
        $textos = $textosDeDocumentos();

        $this->form->fill([
            'cierre_constancia_matricula' => $textos->cierreConstanciaMatricula, 'cierre_constancia_notas' => $textos->cierreConstanciaNotas,
            'escala_notas' => $textos->escalaNotas, 'programa_acreditacion' => $textos->programaAcreditacion,
            'suficiencia_acreditacion' => $textos->suficienciaAcreditacion, 'constancia_acta_acreditacion' => $textos->constanciaActaAcreditacion,
            'validez_acreditacion' => $textos->validezAcreditacion, 'texto_certificado' => $textos->textoCertificado,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Constancias')->schema([
                    Textarea::make('cierre_constancia_matricula')->label('Frase final de la constancia de matrícula')->required()->maxLength(300)->rows(2)
                        ->helperText('También la imprime la constancia de inscripción.'),
                    Textarea::make('cierre_constancia_notas')->label('Frase final de la constancia de notas')->required()->maxLength(300)->rows(2),
                    TextInput::make('escala_notas')->label('Nota de la escala de calificación')->required()->maxLength(200),
                ]),
                Section::make('Acreditación de conocimiento de idioma extranjero')->columns(2)->schema([
                    TextInput::make('programa_acreditacion')->label('Modalidad al llevar ciclos')->required()->maxLength(150),
                    TextInput::make('suficiencia_acreditacion')->label('Modalidad por examen de suficiencia')->required()->maxLength(150),
                    Textarea::make('constancia_acta_acreditacion')->label('Frase del acta final')->required()->maxLength(300)->rows(2)->columnSpanFull(),
                    TextInput::make('validez_acreditacion')->label('Frase de validez')->required()->maxLength(300)->columnSpanFull()
                        ->helperText('{mencion}: grado de la mención, por ejemplo «Maestro en Educación».'),
                ]),
                Section::make('Certificado de nivel')->schema([
                    Textarea::make('texto_certificado')->label('Texto del certificado')->required()->maxLength(500)->rows(3)
                        ->helperText('{nivel}, {idioma} y {horas} («120 horas académicas») se imprimen en negrita.'),
                ]),
            ]);
    }

    public function guardar(GuardarTextosDeDocumentos $guardar): void
    {
        try {
            $guardar($this->form->getState(), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Textos de documentos guardados')->send();
    }
}
