<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The Anexo's trámites with its example stages; administrators then adjust them from the panel.
return new class extends Migration
{
    private const array CON_PAGO = [
        ['Solicitud recibida', 0, 'solicitud'],
        ['Pago validado', 33, 'pago_validado'],
        ['En firma de Dirección', 66, 'accion_del_personal'],
        ['Listo para recojo o descarga', 100, 'accion_del_personal'],
    ];

    private const array TIPOS = [
        ['CONSTANCIA-MATRICULA', 'Constancia de matrícula', 'Una matrícula activa y el pago de la tasa.', 'constancia', 'constancia_matricula'],
        ['CONSTANCIA-NOTAS', 'Constancia de notas', 'Al menos un ciclo con acta final cerrada en el idioma y el pago de la tasa.', 'constancia', 'constancia_notas'],
        ['CERTIFICADO-NIVEL', 'Certificado de culminación de nivel', 'Los cuatro ciclos del nivel aprobados en actas cerradas y el pago de la tasa.', 'certificado', 'certificado_nivel'],
        ['TRASLADO', 'Traslado de grupo', 'Una matrícula activa, el motivo y el pago de la tasa. El CILN elige el grupo de destino según las vacantes.', 'tramite', 'traslado'],
        ['RETIRO', 'Retiro del ciclo', 'Una matrícula activa y el motivo. El retiro no devuelve el pago de la matrícula.', null, 'retiro'],
    ];

    public function up(): void
    {
        $ahora = now();

        foreach (self::TIPOS as [$codigo, $nombre, $requisitos, $concepto, $accion]) {
            $tipoId = DB::table('tra_tipos_tramite')->insertGetId([
                'codigo' => $codigo, 'nombre' => $nombre, 'requisitos' => $requisitos, 'tipo_concepto' => $concepto,
                'accion_final' => $accion, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);

            $etapas = $concepto === null ? [['Solicitud recibida', 0, 'solicitud'], ['En revisión', 50, 'accion_del_personal'], ['Retiro registrado', 100, 'accion_del_personal']] : self::CON_PAGO;

            DB::table('tra_etapas')->insert(array_map(fn (array $etapa, int $indice): array => [
                'tipo_tramite_id' => $tipoId, 'orden' => $indice + 1, 'nombre' => $etapa[0], 'avance' => $etapa[1], 'condicion' => $etapa[2],
                'created_at' => $ahora, 'updated_at' => $ahora,
            ], $etapas, array_keys($etapas)));
        }
    }

    public function down(): void
    {
        $ids = DB::table('tra_tipos_tramite')->whereIn('codigo', array_column(self::TIPOS, 0))->pluck('id');

        DB::table('tra_etapas')->whereIn('tipo_tramite_id', $ids)->delete();
        DB::statement('ALTER TABLE tra_tipos_tramite DISABLE TRIGGER tra_tipos_tramite_bloqueo');
        DB::table('tra_tipos_tramite')->whereIn('id', $ids)->delete();
        DB::statement('ALTER TABLE tra_tipos_tramite ENABLE TRIGGER tra_tipos_tramite_bloqueo');
    }
};
