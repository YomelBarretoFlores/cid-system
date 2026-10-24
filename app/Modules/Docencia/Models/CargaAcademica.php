<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Policies\CargaAcademicaPolicy;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('doc_cargas_academicas')]
#[Fillable(['grupo_id', 'docente_id', 'asignado_por'])]
#[UsePolicy(CargaAcademicaPolicy::class)]
final class CargaAcademica extends Model
{
    /**
     * @return BelongsTo<Docente, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function asignador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }
}
