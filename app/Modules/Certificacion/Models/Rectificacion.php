<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $autorizada_en
 * @property ?CarbonImmutable $cerrada_en
 */
#[Table('cer_rectificaciones')]
#[Fillable(['acta_id', 'motivo', 'autorizada_por', 'autorizada_en', 'cerrada_en'])]
final class Rectificacion extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizada_por');
    }

    protected function casts(): array
    {
        return [
            'autorizada_en' => 'immutable_datetime',
            'cerrada_en' => 'immutable_datetime',
        ];
    }
}
