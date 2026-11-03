<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use App\Modules\Matricula\Database\Factories\HabilitacionFactory;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Policies\HabilitacionPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The cycle a person is enabled to enroll in for a language. No row means the first cycle.
 *
 * @property int $persona_id
 * @property int $idioma_id
 * @property int $ciclo_id
 * @property OrigenHabilitacion $origen
 * @property ?string $motivo
 * @property ?int $actualizado_por
 * @property CarbonImmutable $updated_at
 * @property-read ?User $actualizador
 */
#[Table('mat_habilitaciones')]
#[Fillable(['persona_id', 'idioma_id', 'ciclo_id', 'origen', 'motivo', 'actualizado_por'])]
#[UseFactory(HabilitacionFactory::class)]
#[UsePolicy(HabilitacionPolicy::class)]
final class Habilitacion extends Model
{
    /** @use HasFactory<HabilitacionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    protected function casts(): array
    {
        return [
            'origen' => OrigenHabilitacion::class,
        ];
    }
}
