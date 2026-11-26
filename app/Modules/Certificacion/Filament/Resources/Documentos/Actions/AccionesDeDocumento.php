<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos\Actions;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirCertificadosDelPeriodo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeMatriculaDelGrupo;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\AvisoDeEmision;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

final class AccionesDeDocumento
{
    public static function emitir(): Action
    {
        return Action::make('emitir')
            ->label('Emitir documento')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->authorize('emitir', Documento::class)
            ->schema([
                Select::make('tipo')->label('Tipo de documento')->options(self::tipos())->required()->live(),
                Select::make('persona_id')
                    ->label('Estudiante')
                    ->helperText('Busca por documento o por apellidos y nombres.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                    ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                    ->required()
                    ->live(),
                Select::make('matricula_id')
                    ->label('Matrícula activa')
                    ->options(fn (Get $get): array => filled($get('persona_id')) ? app(MatriculasActivasDePersona::class)((int) $get('persona_id')) : [])
                    ->visible(fn (Get $get): bool => $get('tipo') === TipoDocumento::ConstanciaMatricula->value)
                    ->required(),
                Select::make('idioma')
                    ->label('Idioma')
                    ->helperText('Idiomas con actas finales cerradas del estudiante.')
                    ->options(fn (Get $get): array => filled($get('persona_id')) ? HistorialDeNotas::idiomasDe((int) $get('persona_id')) : [])
                    ->visible(fn (Get $get): bool => in_array($get('tipo'), [TipoDocumento::ConstanciaNotas->value, TipoDocumento::CertificadoNivel->value], true))
                    ->required(),
                Select::make('nivel')
                    ->label(fn (Get $get): string => $get('tipo') === TipoDocumento::CertificadoNivel->value ? 'Nivel culminado' : 'Nivel')
                    ->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())
                    ->visible(fn (Get $get): bool => in_array($get('tipo'), [TipoDocumento::ConstanciaNotas->value, TipoDocumento::CertificadoNivel->value], true))
                    ->required(),
            ])
            ->action(function (Action $action, array $data): void {
                $documento = null;
                AccionDeNegocio::ejecutar($action, function (User $usuario) use ($data, &$documento): void {
                    $documento = self::emitirSegun($data, $usuario);
                }, 'Documento emitido');

                if ($documento instanceof Documento) {
                    $action->redirect(DocumentoResource::getUrl('view', ['record' => $documento]));
                }
            });
    }

    public static function emitirCertificadosDelPeriodo(): Action
    {
        return Action::make('emitirCertificadosDelPeriodo')
            ->label('Certificados del periodo')
            ->icon(Heroicon::OutlinedAcademicCap)
            ->color('gray')
            ->authorize('emitir', Documento::class)
            ->modalDescription('Emite el certificado de culminación de nivel a cada estudiante que completó su nivel con un acta cerrada del periodo. Quien ya tiene su certificado vigente no recibe otro.')
            ->schema([Select::make('periodo_id')->label('Periodo')->options(fn (): array => app(CatalogoDePeriodos::class)())->required()])
            ->action(function (Action $action, array $data): void {
                AccionDeNegocio::ejecutar($action, function (User $usuario) use ($data): void {
                    AvisoDeEmision::enviar(app(EmitirCertificadosDelPeriodo::class)((int) $data['periodo_id'], $usuario), ['certificado emitido', 'certificados emitidos'], 'Ya tenían uno vigente');
                }, 'Emisión terminada');
            });
    }

    public static function emitirConstanciasDeMatriculaDelGrupo(): Action
    {
        return Action::make('emitirConstanciasDeMatriculaDelGrupo')
            ->label('Constancias de matrícula del grupo')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->authorize('emitir', Documento::class)
            ->modalDescription('Emite la constancia de matrícula de cada matrícula activa del grupo. Quien ya tiene una vigente de esa matrícula no recibe otra.')
            ->schema([Select::make('grupo_id')->label('Grupo')->options(fn (): array => app(CatalogoDeGrupos::class)())->searchable()->required()])
            ->action(function (Action $action, array $data): void {
                AccionDeNegocio::ejecutar($action, function (User $usuario) use ($data): void {
                    AvisoDeEmision::enviar(app(EmitirConstanciasDeMatriculaDelGrupo::class)((int) $data['grupo_id'], $usuario), ['constancia emitida', 'constancias emitidas'], 'Ya tenían una vigente');
                }, 'Emisión terminada');
            });
    }

    public static function descargarPdf(): Action
    {
        return Action::make('descargarPdf')
            ->label('PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (Documento $record): bool => $record->estaVigente())
            ->url(fn (Documento $record): string => route('panel.documentos.pdf', $record))
            ->openUrlInNewTab();
    }

    public static function verificacionPublica(): Action
    {
        return Action::make('verificacionPublica')
            ->label('Ver verificación pública')
            ->icon(Heroicon::OutlinedQrCode)
            ->color('gray')
            ->url(fn (Documento $record): string => route('verificacion.documento', $record->codigo))
            ->openUrlInNewTab();
    }

    public static function anular(): Action
    {
        return Action::make('anular')
            ->label('Anular')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->authorize('anular')
            ->modalDescription('La verificación pública mostrará el documento como anulado. No se puede deshacer.')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo de la anulación')
                    ->required()
                    ->minLength(5)
                    ->maxLength(500)
                    ->rows(3)
                    ->live(debounce: 400)
                    ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 500'),
            ])
            ->action(fn (Action $action, Documento $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(AnularDocumento::class)($record->id, (string) $data['motivo'], $usuario),
                'Documento anulado',
            ))
            ->after(fn (Documento $record): Documento => $record->refresh());
    }

    /**
     * @return array<string, string>
     */
    private static function tipos(): array
    {
        return collect(TipoDocumento::cases())->mapWithKeys(fn (TipoDocumento $tipo): array => [$tipo->value => $tipo->etiqueta()])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function emitirSegun(array $data, User $usuario): Documento
    {
        $personaId = (int) $data['persona_id'];

        return match (TipoDocumento::from((string) $data['tipo'])) {
            TipoDocumento::ConstanciaMatricula => app(EmitirConstanciaDeMatricula::class)((int) $data['matricula_id'], $usuario),
            TipoDocumento::ConstanciaNotas => app(EmitirConstanciaDeNotas::class)($personaId, (string) $data['idioma'], NivelCurricular::from((string) $data['nivel']), $usuario),
            TipoDocumento::CertificadoNivel => app(EmitirCertificadoDeNivel::class)($personaId, (string) $data['idioma'], NivelCurricular::from((string) $data['nivel']), $usuario),
        };
    }
}
