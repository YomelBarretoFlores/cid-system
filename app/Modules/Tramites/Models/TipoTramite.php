<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Database\Factories\TipoTramiteFactory;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Policies\TipoTramitePolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ?TipoConcepto $tipo_concepto
 * @property AccionFinal $accion_final
 * @property-read Collection<int, Etapa> $etapas
 */
#[Table('tra_tipos_tramite')]
#[Fillable(['codigo', 'nombre', 'requisitos', 'tipo_concepto', 'accion_final', 'activo'])]
#[UseFactory(TipoTramiteFactory::class)]
#[UsePolicy(TipoTramitePolicy::class)]
final class TipoTramite extends Model
{
    /** @use HasFactory<TipoTramiteFactory> */
    use HasFactory, NormalizaCodigo;

    /**
     * @return HasMany<Etapa, $this>
     */
    public function etapas(): HasMany
    {
        return $this->hasMany(Etapa::class)->orderBy('orden');
    }

    public function cobraTasa(): bool
    {
        return $this->tipo_concepto !== null;
    }

    protected function casts(): array
    {
        return [
            'tipo_concepto' => TipoConcepto::class,
            'accion_final' => AccionFinal::class,
            'activo' => 'boolean',
        ];
    }
}
