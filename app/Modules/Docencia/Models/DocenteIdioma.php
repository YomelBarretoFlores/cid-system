<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Academico\Enums\NivelCurricular;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $idioma_id
 * @property NivelCurricular $nivel_maximo
 */
#[Table('doc_docente_idiomas')]
#[Fillable(['docente_id', 'idioma_id', 'nivel_maximo'])]
final class DocenteIdioma extends Model
{
    /**
     * @return BelongsTo<Docente, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    protected function casts(): array
    {
        return [
            'nivel_maximo' => NivelCurricular::class,
        ];
    }
}
