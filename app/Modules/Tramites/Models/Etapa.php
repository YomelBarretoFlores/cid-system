<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Tramites\Enums\CondicionDeEtapa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CondicionDeEtapa $condicion
 */
#[Table('tra_etapas')]
#[Fillable(['tipo_tramite_id', 'orden', 'nombre', 'avance', 'condicion'])]
final class Etapa extends Model
{
    /**
     * @return BelongsTo<TipoTramite, $this>
     */
    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'avance' => 'integer',
            'condicion' => CondicionDeEtapa::class,
        ];
    }
}
