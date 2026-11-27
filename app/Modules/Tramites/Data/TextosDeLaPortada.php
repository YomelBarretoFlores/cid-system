<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

use App\Modules\Tramites\Enums\SeccionDePortada;
use App\Modules\Tramites\Models\TextosDePortada;

final readonly class TextosDeLaPortada
{
    public const string LUGARES_DE_PAGO = '{lugares_de_pago}';

    public const string SUBTITULO = 'para estudiantes universitarios, profesionales y público en general.';

    public const string PIE = 'Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos por nivel.';

    /**
     * @param  list<array{titulo: string, detalle: string}>  $pasosMatricula
     * @param  list<array{titulo: string, detalle: string}>  $pasosClasificacion
     * @param  list<array{nombre: string, descripcion: string, logro: string}>  $niveles
     * @param  list<array{pregunta: string, respuesta: string}>  $preguntas
     * @param  array<string, array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}>  $secciones  keyed by SeccionDePortada value
     * @param  list<array{etiqueta: string, url: string}>  $redes
     */
    public function __construct(
        public string $nosotros,
        public array $pasosMatricula,
        public array $pasosClasificacion,
        public array $niveles,
        public array $preguntas,
        public ?string $fotoPortada = null,
        public string $subtitulo = self::SUBTITULO,
        public string $botonPrincipal = 'Ver horarios',
        public string $botonSecundario = 'Crear cuenta',
        public array $secciones = [],
        public string $pie = self::PIE,
        public ?string $correo = null,
        public ?string $horarioAtencion = null,
        public array $redes = [],
    ) {}

    public static function desde(TextosDePortada $textos): self
    {
        return new self(
            $textos->nosotros, $textos->pasos_matricula, $textos->pasos_clasificacion, $textos->niveles, $textos->preguntas,
            fotoPortada: $textos->foto_portada,
            subtitulo: $textos->subtitulo,
            botonPrincipal: $textos->boton_principal,
            botonSecundario: $textos->boton_secundario,
            secciones: self::completas($textos->secciones),
            pie: $textos->pie,
            correo: $textos->correo,
            horarioAtencion: $textos->horario_atencion,
            redes: $textos->redes,
        );
    }

    /**
     * Every section of the page in page order, the stored texts over the defaults.
     *
     * @param  array<string, array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}>  $guardadas
     * @return array<string, array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}>
     */
    public static function completas(array $guardadas = []): array
    {
        $secciones = [];

        foreach (SeccionDePortada::cases() as $seccion) {
            $secciones[$seccion->value] = [...$seccion->porDefecto(), ...$guardadas[$seccion->value] ?? []];
        }

        return $secciones;
    }

    // What the migration inserts, for a missing row (a test truncation).
    public static function porDefecto(): self
    {
        return new self(
            nosotros: "El Centro de Idiomas y Lenguas Nativas de la UNASAM enseña idiomas extranjeros y lenguas originarias del Perú. Cada idioma se estudia por ciclos, agrupados en los niveles básico, intermedio y avanzado.\n\nLa matrícula, los pagos, las notas y los certificados se gestionan en línea desde el portal del estudiante, y cada constancia o certificado lleva un código y un QR para verificar su autenticidad.",
            pasosMatricula: [
                ['titulo' => 'Cree su cuenta', 'detalle' => 'Regístrese con su documento de identidad y su correo personal.'],
                ['titulo' => 'Pague la matrícula', 'detalle' => 'Pague el derecho de matrícula de su vínculo en {lugares_de_pago}.'],
                ['titulo' => 'Registre su recibo', 'detalle' => 'Regístrelo en Registro de pagos con el número de operación. El CILN lo revisa y lo valida.'],
                ['titulo' => 'Elija su grupo', 'detalle' => 'Elija un horario con vacantes de su ciclo y descargue su ficha de matrícula.'],
            ],
            pasosClasificacion: [
                ['titulo' => 'Pague el examen', 'detalle' => 'En {lugares_de_pago}, y registre el recibo en el portal.'],
                ['titulo' => 'Solicítelo en el portal', 'detalle' => 'Ingrese al portal del estudiante, elija el idioma y el pago validado del examen.'],
                ['titulo' => 'Rinda el examen', 'detalle' => 'En la fecha y el lugar que le comunique la coordinación académica.'],
                ['titulo' => 'Reciba su ciclo', 'detalle' => 'La coordinación registra el resultado y le asigna el ciclo en el que puede matricularse.'],
            ],
            niveles: [
                ['nombre' => 'Básico', 'descripcion' => 'Para quien inicia el estudio del idioma: vocabulario, estructuras y expresiones de uso cotidiano.', 'logro' => 'Comprender y usar expresiones habituales, presentarse y desenvolverse en situaciones sencillas.'],
                ['nombre' => 'Intermedio', 'descripcion' => 'Amplía el vocabulario y la gramática para comunicarse con mayor precisión en el ámbito personal y académico.', 'logro' => 'Participar en conversaciones, comprender las ideas principales de textos claros y redactar textos breves.'],
                ['nombre' => 'Avanzado', 'descripcion' => 'Consolida el uso del idioma en contextos académicos y profesionales.', 'logro' => 'Expresarse con fluidez, comprender textos extensos y redactar documentos académicos y profesionales.'],
            ],
            preguntas: [
                ['pregunta' => '¿Cómo me matriculo?', 'respuesta' => 'Cree su cuenta, pague el derecho de matrícula de su vínculo, registre el recibo en Registro de pagos y, cuando el CILN lo valide, elija un grupo con vacantes. La ficha de matrícula se descarga al instante.'],
                ['pregunta' => '¿Dónde pago?', 'respuesta' => 'En {lugares_de_pago}. Luego registre el número de operación y el recibo en el portal.'],
                ['pregunta' => 'Ya conozco el idioma, ¿debo empezar en el ciclo I?', 'respuesta' => 'No. Rinda el examen de clasificación desde el portal del estudiante y, según su resultado, se matricula en un ciclo superior.'],
                ['pregunta' => '¿Cómo obtengo mi certificado?', 'respuesta' => 'Al aprobar los ciclos de un nivel, solicite el certificado como trámite desde el portal del estudiante. Cada certificado lleva un código y un QR verificables.'],
                ['pregunta' => '¿Cómo verifico una constancia o certificado?', 'respuesta' => 'Escriba el código impreso al pie del documento en la sección de verificación, al final de esta página, o escanee su código QR.'],
                ['pregunta' => 'Soy docente del CILN, ¿dónde ingreso?', 'respuesta' => 'En Ingresar, con el mismo correo y contraseña. El sistema lo lleva a su panel de docente.'],
            ],
            secciones: self::completas(),
        );
    }
}
