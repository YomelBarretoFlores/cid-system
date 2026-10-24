<?php

declare(strict_types=1);

namespace Tests\Concurrency;

use Illuminate\Foundation\Testing\DatabaseTruncation;

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
}
