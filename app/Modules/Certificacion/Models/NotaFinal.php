<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Certificacion\Enums\EstadoFinal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, string> $notas grade per component id, blanks left out
 * @property ?string $promedio
 * @property EstadoFinal $estado_final
 */
#[Table('cer_notas_finales')]
#[Fillable(['acta_id', 'matricula_id', 'persona_id', 'estudiante_nombre', 'estudiante_documento', 'notas', 'promedio', 'estado_final'])]
final class NotaFinal extends Model
{
    /**
     * @return BelongsTo<Acta, $this>
     */
    public function acta(): BelongsTo
    {
        return $this->belongsTo(Acta::class);
    }

    protected function casts(): array
    {
        return [
            'notas' => 'array',
            'promedio' => 'decimal:2',
            'estado_final' => EstadoFinal::class,
        ];
    }
}
