<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Pages\Schemas;

use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Enums\SeccionDePortada;
use App\Modules\Tramites\Support\FotoDePortada;
use App\Modules\Tramites\Support\LimpiezaDeTextosDePortada;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;

// Each section carries the id "seccion-<key>" of its public section, so a link can open the form at it.
final class FormularioDeContenidoDePortada
{
    private const string AYUDA_LUGARES = '{lugares_de_pago} se reemplaza por los medios de pago activos del catálogo.';

    /**
     * @return array<string, mixed>
     */
    public static function estado(TextosDeLaPortada $textos): array
    {
        return [
            'nosotros' => $textos->nosotros, 'pasos_matricula' => $textos->pasosMatricula, 'pasos_clasificacion' => $textos->pasosClasificacion,
            'niveles' => $textos->niveles, 'preguntas' => $textos->preguntas,
            'foto_portada' => $textos->fotoPortada, 'subtitulo' => $textos->subtitulo,
            'boton_principal' => $textos->botonPrincipal, 'boton_secundario' => $textos->botonSecundario,
            'secciones' => $textos->secciones,
            'pie' => $textos->pie, 'correo' => $textos->correo, 'horario_atencion' => $textos->horarioAtencion, 'redes' => $textos->redes,
        ];
    }

    /**
     * @return list<Component>
     */
    public static function componentes(): array
    {
        return [
            self::portada(),
            ...array_map(self::seccion(...), SeccionDePortada::cases()),
            self::pie(),
        ];
    }

    private static function portada(): Section
    {
        return self::bloque('Portada', 'inicio')->schema([
            FileUpload::make('foto_portada')->label('Foto de portada (JPG, PNG o WebP)')
                ->image()->disk(FotoDePortada::DISCO)->acceptedFileTypes(array_keys(FotoDePortada::EXTENSIONES))->maxSize(FotoDePortada::MAXIMO_KB)
                ->storeFiles(false)
                ->helperText('Hasta '.(FotoDePortada::MAXIMO_KB / 1024).' MB, horizontal y de al menos 2000 píxeles de ancho. Sin foto se usa la incluida en el sistema.'),
            TextInput::make('subtitulo')->label('Texto después de la lista de idiomas')->required()->maxLength(200)
                ->helperText('La lista de idiomas se arma con el catálogo de idiomas.'),
            TextInput::make('boton_principal')->label('Botón principal (lleva a los horarios)')->required()->maxLength(40),
            TextInput::make('boton_secundario')->label('Botón secundario (lleva a crear la cuenta)')->required()->maxLength(40),
        ]);
    }

    private static function seccion(SeccionDePortada $seccion): Section
    {
        $titulos = [
            TextInput::make('etiqueta')->label('Etiqueta sobre el título')->required()->maxLength(60),
            TextInput::make('titulo')->label('Título')->required()->maxLength(120),
        ];

        if ($seccion->tieneIntroduccion()) {
            $titulos[] = Textarea::make('introduccion')->label('Introducción')->maxLength(400)->rows(2)->columnSpanFull();
        }

        return self::bloque($seccion->nombre(), $seccion->value)->collapsible()->schema([
            Fieldset::make('Títulos y visibilidad')->statePath("secciones.{$seccion->value}")->schema([
                ...$titulos,
                Toggle::make('visible')->label('Mostrar sección')->default(true),
            ]),
            ...self::contenido($seccion),
        ]);
    }

    /**
     * @return list<Component>
     */
    private static function contenido(SeccionDePortada $seccion): array
    {
        return match ($seccion) {
            SeccionDePortada::Centro => [Textarea::make('nosotros')->label('Presentación del centro')->required()->maxLength(2000)->rows(5)
                ->helperText('Separe los párrafos con una línea en blanco.')],
            SeccionDePortada::Requisitos => [self::pasos('pasos_matricula')],
            SeccionDePortada::Clasificacion => [self::pasos('pasos_clasificacion')],
            SeccionDePortada::Ruta => [Repeater::make('niveles')->label('Niveles')->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar nivel')->schema([
                TextInput::make('nombre')->label('Nivel')->required()->maxLength(40),
                Textarea::make('descripcion')->label('Descripción')->required()->maxLength(300)->rows(2),
                Textarea::make('logro')->label('Al concluir el nivel podrá')->required()->maxLength(300)->rows(2),
            ])],
            SeccionDePortada::Preguntas => [Repeater::make('preguntas')->label('Preguntas')->helperText(self::AYUDA_LUGARES)->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar pregunta')->schema([
                TextInput::make('pregunta')->label('Pregunta')->required()->maxLength(150),
                Textarea::make('respuesta')->label('Respuesta')->required()->maxLength(600)->rows(3),
            ])],
            default => [],
        };
    }

    private static function pie(): Section
    {
        return self::bloque('Pie de página y contacto', 'pie')
            ->description('La dirección y el teléfono se toman de los datos institucionales.')
            ->schema([
                Textarea::make('pie')->label('Descripción del centro')->required()->maxLength(300)->rows(2),
                TextInput::make('correo')->label('Correo de contacto')->email()->maxLength(120),
                TextInput::make('horario_atencion')->label('Horario de atención')->maxLength(150),
                Repeater::make('redes')->label('Enlaces (redes sociales, web)')->maxItems(LimpiezaDeTextosDePortada::MAXIMO_DE_REDES)->reorderable()->addActionLabel('Agregar enlace')
                    ->defaultItems(0)->columns(2)->schema([
                        TextInput::make('etiqueta')->label('Nombre')->required()->maxLength(40),
                        TextInput::make('url')->label('Dirección')->required()->url()->startsWith(['https://'])->maxLength(255),
                    ]),
            ]);
    }

    private static function bloque(string $titulo, string $clave): Section
    {
        return Section::make($titulo)->id("seccion-{$clave}")->key("seccion-{$clave}");
    }

    private static function pasos(string $lista): Repeater
    {
        return Repeater::make($lista)->label('Pasos')->reorderable()->minItems(1)->maxItems(12)->addActionLabel('Agregar paso')
            ->helperText(self::AYUDA_LUGARES)
            ->schema([
                TextInput::make('titulo')->label('Paso')->required()->maxLength(80),
                Textarea::make('detalle')->label('Detalle')->required()->maxLength(300)->rows(2),
            ]);
    }
}
