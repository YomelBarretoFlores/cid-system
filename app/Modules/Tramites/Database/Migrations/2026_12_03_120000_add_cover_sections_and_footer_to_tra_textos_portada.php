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
        Schema::table('tra_textos_portada', function (Blueprint $table): void {
            $table->string('foto_portada')->nullable();
            $table->string('subtitulo', 200)->default('para estudiantes universitarios, profesionales y público en general.');
            $table->string('boton_principal', 40)->default('Ver horarios');
            $table->string('boton_secundario', 40)->default('Crear cuenta');
            $table->string('pie', 300)->default('Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos por nivel.');
            $table->string('correo', 120)->nullable();
            $table->string('horario_atencion', 150)->nullable();
            $table->jsonb('redes')->default('[]');
        });

        // A column default fills the existing row without an UPDATE, so the audit trail records only staff edits.
        DB::statement('ALTER TABLE tra_textos_portada ADD COLUMN secciones jsonb NOT NULL DEFAULT '.DB::getPdo()->quote((string) json_encode($this->secciones(), JSON_UNESCAPED_UNICODE)).'::jsonb');
        DB::statement("ALTER TABLE tra_textos_portada ADD CONSTRAINT tra_textos_portada_portada_check CHECK (btrim(subtitulo) <> '' AND btrim(boton_principal) <> '' AND btrim(boton_secundario) <> '' AND btrim(pie) <> '')");
        DB::statement("ALTER TABLE tra_textos_portada ADD CONSTRAINT tra_textos_portada_estructura_check CHECK (jsonb_typeof(secciones) = 'object' AND jsonb_typeof(redes) = 'array')");

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN tra_textos_portada.foto_portada IS 'Ruta en el disco público (portada/) de la foto de portada subida; vacía usa la foto incluida en el sistema.';
            COMMENT ON COLUMN tra_textos_portada.subtitulo IS 'Texto que sigue a la lista de idiomas del catálogo bajo el nombre del centro.';
            COMMENT ON COLUMN tra_textos_portada.boton_principal IS 'Texto del botón principal de la portada, que lleva a los horarios.';
            COMMENT ON COLUMN tra_textos_portada.boton_secundario IS 'Texto del botón secundario de la portada, que lleva a crear la cuenta.';
            COMMENT ON COLUMN tra_textos_portada.secciones IS 'Por sección de la portada (centro, idiomas, requisitos, oferta, clasificacion, ruta, costos, fechas, preguntas, empieza): {etiqueta, titulo, introduccion, visible}.';
            COMMENT ON COLUMN tra_textos_portada.pie IS 'Descripción del centro en el pie de página.';
            COMMENT ON COLUMN tra_textos_portada.correo IS 'Correo de contacto del pie de página; la dirección y el teléfono vienen de los datos institucionales.';
            COMMENT ON COLUMN tra_textos_portada.horario_atencion IS 'Horario de atención del pie de página.';
            COMMENT ON COLUMN tra_textos_portada.redes IS 'Enlaces del pie de página (redes sociales, web): lista de {etiqueta, url}, solo https, hasta 6.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tra_textos_portada DROP CONSTRAINT IF EXISTS tra_textos_portada_portada_check');
        DB::statement('ALTER TABLE tra_textos_portada DROP CONSTRAINT IF EXISTS tra_textos_portada_estructura_check');

        Schema::table('tra_textos_portada', function (Blueprint $table): void {
            $table->dropColumn(['foto_portada', 'subtitulo', 'boton_principal', 'boton_secundario', 'secciones', 'pie', 'correo', 'horario_atencion', 'redes']);
        });
    }

    /**
     * @return array<string, array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}>
     */
    private function secciones(): array
    {
        $secciones = [
            'centro' => ['El centro', 'Idiomas para la universidad y la región', null],
            'idiomas' => ['Oferta académica', 'Idiomas', 'Cada idioma se estudia por ciclos, organizados en niveles. Al aprobar un nivel se solicita el certificado correspondiente.'],
            'requisitos' => ['Matrícula', 'Cómo matricularse', 'Todo el proceso es en línea: cree su cuenta, registre su pago y elija un grupo con vacantes.'],
            'oferta' => ['Oferta', 'Horarios y vacantes', null],
            'clasificacion' => ['Examen de clasificación', '¿Ya conoce el idioma?', 'Rinda el examen de clasificación y, según su resultado, empiece en un ciclo superior sin cursar desde el ciclo I.'],
            'ruta' => ['Plan de estudios', 'Ruta de aprendizaje', 'Cada nivel agrupa varios ciclos. El estudiante avanza de un ciclo al siguiente al aprobarlo.'],
            'costos' => ['TUPA / TUSNE', 'Costos según su vínculo con la UNASAM', 'Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.'],
            'fechas' => ['Calendario', 'Fechas del periodo', null],
            'preguntas' => ['Orientación', 'Preguntas frecuentes', 'Lo que más consultan los postulantes y estudiantes del CILN.'],
            'empieza' => ['Huaraz, Áncash', 'Matrícula en línea', 'Cree su cuenta, registre su pago y elija su horario. El CILN valida su recibo y su ficha de matrícula se descarga al instante.'],
        ];

        return array_map(fn (array $textos): array => ['etiqueta' => $textos[0], 'titulo' => $textos[1], 'introduccion' => $textos[2], 'visible' => true], $secciones);
    }
};
