<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use App\Modules\Matricula\Database\Factories\HabilitacionFactory;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The cycle a person is enabled to enroll in for a language. No row means the first cycle.
 *
 * @property int $ciclo_id
 * @property OrigenHabilitacion $origen
 */
#[Table('mat_habilitaciones')]
#[Fillable(['persona_id', 'idioma_id', 'ciclo_id', 'origen', 'actualizado_por'])]
#[UseFactory(HabilitacionFactory::class)]
final class Habilitacion extends Model
{
    /** @use HasFactory<HabilitacionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'origen' => OrigenHabilitacion::class,
        ];
    }
}
