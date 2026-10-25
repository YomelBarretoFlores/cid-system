<?php

declare(strict_types=1);

namespace Tests\Concurrency;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

/**
 * Payments and the audit trail reject TRUNCATE, so only the academic offer (and what cascades from it) is truncated.
 */
trait TruncatesAcademicOffer
{
    use DatabaseTruncation;

    /**
     * @var list<string>
     */
    protected array $tablesToTruncate = ['aca_idiomas', 'aca_ciclos', 'aca_periodos', 'aca_aulas', 'aca_grupos', 'aca_horarios', 'doc_docentes', 'doc_costos_hora'];

    /**
     * Closed actas and issued documents reject TRUNCATE with rows; only the test cleanup lifts that guard, for its own statement.
     *
     * @var list<string>
     */
    private array $tablasDeCertificacion = ['cer_actas', 'cer_notas_finales', 'cer_rectificaciones', 'cer_documentos'];

    protected function beforeTruncatingDatabase(): void
    {
        foreach ($this->tablasDeCertificacion as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DISABLE TRIGGER {$tabla}_sin_truncate");
        }
    }

    protected function afterTruncatingDatabase(): void
    {
        foreach ($this->tablasDeCertificacion as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ENABLE TRIGGER {$tabla}_sin_truncate");
        }
    }
}
