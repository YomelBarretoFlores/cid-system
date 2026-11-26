<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The FUT procedures of the CILN posters as default types; administrators adjust fees, concepts and texts afterwards. A code that already exists is left as it is.
return new class extends Migration
{
    private const string IDENTIDAD = 'Datos del estudiante registrados en su cuenta';

    private const array EXAMEN = [
        ['Solicitud recibida', 0, 'solicitud'],
        ['Pago validado', 33, 'pago_validado'],
        ['Examen programado', 66, 'accion_del_personal'],
        ['Resultado registrado', 100, 'accion_del_personal'],
    ];

    private const array DOCUMENTO = [
        ['Solicitud recibida', 0, 'solicitud'],
        ['Pago validado', 33, 'pago_validado'],
        ['En firma de Dirección', 66, 'accion_del_personal'],
        ['Listo para recojo o descarga', 100, 'accion_del_personal'],
    ];

    private const array RESOLUCION = [
        ['Solicitud recibida', 0, 'solicitud'],
        ['En revisión', 50, 'accion_del_personal'],
        ['Resuelto', 100, 'accion_del_personal'],
    ];

    public function up(): void
    {
        $posgrado = DB::table('aca_programas')->where('codigo', 'POSGRADO')->value('id');

        foreach ($this->tipos() as $tipo) {
            if (DB::table('tra_tipos_tramite')->where('codigo', $tipo['codigo'])->exists()) {
                continue;
            }

            $this->insertar($tipo, $posgrado === null ? null : (int) $posgrado);
        }
    }

    public function down(): void
    {
        $codigos = array_column($this->tipos(), 'codigo');
        $ids = DB::table('tra_tipos_tramite')->whereIn('codigo', $codigos)
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('tra_tramites')->whereColumn('tra_tramites.tipo_tramite_id', 'tra_tipos_tramite.id'))
            ->orderByDesc('id')
            ->pluck('id')
            ->all();

        DB::table('tra_etapas')->whereIn('tipo_tramite_id', $ids)->delete();
        DB::table('tra_tipo_tramite_programa')->whereIn('tipo_tramite_id', $ids)->delete();
        DB::table('tra_tipos_tramite')->whereIn('requiere_tipo_previo_id', $ids)->whereNotIn('id', $ids)->update(['requiere_tipo_previo_id' => null]);
        DB::table('tra_tipos_tramite')->whereIn('id', $ids)->update(['requiere_tipo_previo_id' => null]);
        DB::statement('ALTER TABLE tra_tipos_tramite DISABLE TRIGGER tra_tipos_tramite_bloqueo');
        DB::table('tra_tipos_tramite')->whereIn('id', $ids)->delete();
        DB::statement('ALTER TABLE tra_tipos_tramite ENABLE TRIGGER tra_tipos_tramite_bloqueo');
    }

    /**
     * @param  array{codigo: string, nombre: string, requisitos: string, concepto: ?string, accion: string, campos: array<string, bool>, etapas: list<array{string, int, string}>, posgrado: bool, previo: ?string, meses?: int}  $tipo
     */
    private function insertar(array $tipo, ?int $posgrado): void
    {
        $ahora = now();
        $tipoId = DB::table('tra_tipos_tramite')->insertGetId([
            'codigo' => $tipo['codigo'], 'nombre' => $tipo['nombre'], 'requisitos' => $tipo['requisitos'], 'tipo_concepto' => $tipo['concepto'],
            'accion_final' => $tipo['accion'], 'activo' => true, 'adjuntos' => '[]', 'meses_sin_estudiar' => $tipo['meses'] ?? null,
            'campos' => json_encode(array_map(fn (string $clave, bool $obligatorio): array => ['clave' => $clave, 'obligatorio' => $obligatorio], array_keys($tipo['campos']), $tipo['campos']), JSON_THROW_ON_ERROR),
            'requiere_tipo_previo_id' => $tipo['previo'] === null ? null : DB::table('tra_tipos_tramite')->where('codigo', $tipo['previo'])->value('id'),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        DB::table('tra_etapas')->insert(array_map(fn (array $etapa, int $indice): array => [
            'tipo_tramite_id' => $tipoId, 'orden' => $indice + 1, 'nombre' => $etapa[0], 'avance' => $etapa[1], 'condicion' => $etapa[2],
            'created_at' => $ahora, 'updated_at' => $ahora,
        ], $tipo['etapas'], array_keys($tipo['etapas'])));

        if ($tipo['posgrado'] && $posgrado !== null) {
            DB::table('tra_tipo_tramite_programa')->insert(['tipo_tramite_id' => $tipoId, 'programa_id' => $posgrado, 'created_at' => $ahora, 'updated_at' => $ahora]);
        }
    }

    /**
     * @return list<array{codigo: string, nombre: string, requisitos: string, concepto: ?string, accion: string, campos: array<string, bool>, etapas: list<array{string, int, string}>, posgrado: bool, previo: ?string, meses?: int}>
     */
    private function tipos(): array
    {
        $identidad = self::IDENTIDAD;

        return [
            [
                'codigo' => 'SUFICIENCIA', 'nombre' => 'Examen de suficiencia', 'concepto' => 'otro', 'accion' => 'suficiencia',
                'requisitos' => "FUT digital con el idioma y el nivel. {$identidad}. Pago de la tasa del examen. El CILN programa el examen y registra la nota: si aprueba, se le reconocen los ciclos del nivel.",
                'campos' => ['idioma' => true, 'nivel' => true], 'etapas' => self::EXAMEN, 'posgrado' => false, 'previo' => null,
            ],
            [
                'codigo' => 'RECLASIFICACION', 'nombre' => 'Examen de reclasificación', 'concepto' => 'otro', 'accion' => 'reclasificacion', 'meses' => 2,
                'requisitos' => "Para alumnos del Centro de Idiomas que dejaron de estudiar 2 o más meses. FUT digital con el idioma y, si desea, el nivel que pide evaluar. {$identidad}. Pago de la tasa del examen de clasificación o reclasificación.",
                'campos' => ['idioma' => true, 'nivel' => false], 'etapas' => self::EXAMEN, 'posgrado' => false, 'previo' => null,
            ],
            [
                'codigo' => 'ACREDITACION', 'nombre' => 'Acreditación (posgrado)', 'concepto' => 'constancia', 'accion' => 'acreditacion',
                'requisitos' => "Modalidad posgrado. FUT digital indicando la MENCIÓN del grado de maestría o doctorado. {$identidad}. Al menos un ciclo aprobado del nivel. Pago de la tasa por cada nivel.",
                'campos' => ['idioma' => true, 'nivel' => true, 'mencion' => true], 'etapas' => self::DOCUMENTO, 'posgrado' => true, 'previo' => null,
            ],
            [
                'codigo' => 'CERTIFICADO-POSGRADO', 'nombre' => 'Certificado de estudios (posgrado)', 'concepto' => 'certificado', 'accion' => 'certificado_nivel',
                'requisitos' => "Modalidad posgrado. La acreditación del mismo idioma y nivel tramitada en el sistema. {$identidad}. 02 fotografías tamaño carné por nivel (fondo blanco, con terno a colores). Todos los ciclos del nivel aprobados. Pago de la tasa por cada nivel.",
                'campos' => ['idioma' => true, 'nivel' => true], 'etapas' => self::DOCUMENTO, 'posgrado' => true, 'previo' => 'ACREDITACION',
            ],
            [
                'codigo' => 'MEDIA-BECA', 'nombre' => 'Media beca', 'concepto' => null, 'accion' => 'resolucion',
                'requisitos' => "FUT digital con la modalidad y la exposición de motivos. {$identidad}. El CILN evalúa la solicitud y responde con un comentario.",
                'campos' => ['modalidad' => true, 'exposicion' => true], 'etapas' => self::RESOLUCION, 'posgrado' => false, 'previo' => null,
            ],
            [
                'codigo' => 'OTRO', 'nombre' => 'Otro', 'concepto' => null, 'accion' => 'resolucion',
                'requisitos' => "FUT digital con la exposición de lo que solicita y, si desea, su celular. {$identidad}. El CILN responde con un comentario.",
                'campos' => ['exposicion' => true, 'celular' => false], 'etapas' => self::RESOLUCION, 'posgrado' => false, 'previo' => null,
            ],
        ];
    }
};
