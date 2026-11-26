<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row: the editable texts of the public home page; the defaults are the wording it printed before.
        Schema::create('tra_textos_portada', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);
            $table->text('nosotros');
            $table->jsonb('pasos_matricula');
            $table->jsonb('pasos_clasificacion');
            $table->jsonb('niveles');
            $table->jsonb('preguntas');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE tra_textos_portada ADD CONSTRAINT tra_textos_portada_unica_check CHECK (id = 1)');
        DB::statement("ALTER TABLE tra_textos_portada ADD CONSTRAINT tra_textos_portada_nosotros_check CHECK (btrim(nosotros) <> '')");
        DB::statement("ALTER TABLE tra_textos_portada ADD CONSTRAINT tra_textos_portada_listas_check CHECK (jsonb_typeof(pasos_matricula) = 'array' AND jsonb_typeof(pasos_clasificacion) = 'array' AND jsonb_typeof(niveles) = 'array' AND jsonb_typeof(preguntas) = 'array')");
        DB::statement('CREATE TRIGGER tra_textos_portada_auditoria AFTER INSERT OR UPDATE OR DELETE ON tra_textos_portada FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('tra_textos_portada')->insert([
            'id' => 1,
            'nosotros' => "El Centro de Idiomas y Lenguas Nativas de la UNASAM enseña idiomas extranjeros y lenguas originarias del Perú. Cada idioma se estudia por ciclos, agrupados en los niveles básico, intermedio y avanzado.\n\nLa matrícula, los pagos, las notas y los certificados se gestionan en línea desde el portal del estudiante, y cada constancia o certificado lleva un código y un QR para verificar su autenticidad.",
            'pasos_matricula' => json_encode([
                ['titulo' => 'Cree su cuenta', 'detalle' => 'Regístrese con su documento de identidad y su correo personal.'],
                ['titulo' => 'Pague la matrícula', 'detalle' => 'Pague el derecho de matrícula de su vínculo en {lugares_de_pago}.'],
                ['titulo' => 'Registre su recibo', 'detalle' => 'Regístrelo en Registro de pagos con el número de operación. El CILN lo revisa y lo valida.'],
                ['titulo' => 'Elija su grupo', 'detalle' => 'Elija un horario con vacantes de su ciclo y descargue su ficha de matrícula.'],
            ], JSON_UNESCAPED_UNICODE),
            'pasos_clasificacion' => json_encode([
                ['titulo' => 'Pague el examen', 'detalle' => 'En {lugares_de_pago}, y registre el recibo en el portal.'],
                ['titulo' => 'Solicítelo en el portal', 'detalle' => 'Ingrese al portal del estudiante, elija el idioma y el pago validado del examen.'],
                ['titulo' => 'Rinda el examen', 'detalle' => 'En la fecha y el lugar que le comunique la coordinación académica.'],
                ['titulo' => 'Reciba su ciclo', 'detalle' => 'La coordinación registra el resultado y le asigna el ciclo en el que puede matricularse.'],
            ], JSON_UNESCAPED_UNICODE),
            'niveles' => json_encode([
                ['nombre' => 'Básico', 'descripcion' => 'Para quien inicia el estudio del idioma: vocabulario, estructuras y expresiones de uso cotidiano.', 'logro' => 'Comprender y usar expresiones habituales, presentarse y desenvolverse en situaciones sencillas.'],
                ['nombre' => 'Intermedio', 'descripcion' => 'Amplía el vocabulario y la gramática para comunicarse con mayor precisión en el ámbito personal y académico.', 'logro' => 'Participar en conversaciones, comprender las ideas principales de textos claros y redactar textos breves.'],
                ['nombre' => 'Avanzado', 'descripcion' => 'Consolida el uso del idioma en contextos académicos y profesionales.', 'logro' => 'Expresarse con fluidez, comprender textos extensos y redactar documentos académicos y profesionales.'],
            ], JSON_UNESCAPED_UNICODE),
            'preguntas' => json_encode([
                ['pregunta' => '¿Cómo me matriculo?', 'respuesta' => 'Cree su cuenta, pague el derecho de matrícula de su vínculo, registre el recibo en Registro de pagos y, cuando el CILN lo valide, elija un grupo con vacantes. La ficha de matrícula se descarga al instante.'],
                ['pregunta' => '¿Dónde pago?', 'respuesta' => 'En {lugares_de_pago}. Luego registre el número de operación y el recibo en el portal.'],
                ['pregunta' => 'Ya conozco el idioma, ¿debo empezar en el ciclo I?', 'respuesta' => 'No. Rinda el examen de clasificación desde el portal del estudiante y, según su resultado, se matricula en un ciclo superior.'],
                ['pregunta' => '¿Cómo obtengo mi certificado?', 'respuesta' => 'Al aprobar los ciclos de un nivel, solicite el certificado como trámite desde el portal del estudiante. Cada certificado lleva un código y un QR verificables.'],
                ['pregunta' => '¿Cómo verifico una constancia o certificado?', 'respuesta' => 'Escriba el código impreso al pie del documento en la sección de verificación, al final de esta página, o escanee su código QR.'],
                ['pregunta' => 'Soy docente del CILN, ¿dónde ingreso?', 'respuesta' => 'En Ingresar, con el mismo correo y contraseña. El sistema lo lleva a su panel de docente.'],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::unprepared(<<<'SQL'
            COMMENT ON TABLE tra_textos_portada IS 'Textos editables de la portada pública (nosotros, pasos, niveles, preguntas frecuentes); una sola fila.';
            COMMENT ON COLUMN tra_textos_portada.id IS 'Identificador de la única fila; siempre 1.';
            COMMENT ON COLUMN tra_textos_portada.nosotros IS 'Presentación del centro; los párrafos se separan con una línea en blanco.';
            COMMENT ON COLUMN tra_textos_portada.pasos_matricula IS 'Pasos para matricularse: lista de {titulo, detalle}; {lugares_de_pago} se reemplaza por los medios de pago activos.';
            COMMENT ON COLUMN tra_textos_portada.pasos_clasificacion IS 'Pasos del examen de clasificación: lista de {titulo, detalle}, con la misma marca {lugares_de_pago}.';
            COMMENT ON COLUMN tra_textos_portada.niveles IS 'Descripción de los niveles en la ruta de aprendizaje: lista de {nombre, descripcion, logro}.';
            COMMENT ON COLUMN tra_textos_portada.preguntas IS 'Preguntas frecuentes: lista de {pregunta, respuesta}, con la misma marca {lugares_de_pago}.';
            COMMENT ON COLUMN tra_textos_portada.created_at IS 'Fecha y hora de creación de la fila.';
            COMMENT ON COLUMN tra_textos_portada.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('tra_textos_portada');
    }
};
