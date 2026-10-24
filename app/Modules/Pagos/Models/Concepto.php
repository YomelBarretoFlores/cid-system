<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Pagos\Database\Factories\ConceptoFactory;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Policies\ConceptoPolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TipoConcepto $tipo
 */
#[Table('pag_conceptos')]
#[Fillable(['codigo', 'nombre', 'tipo', 'fuente', 'activo'])]
#[UseFactory(ConceptoFactory::class)]
#[UsePolicy(ConceptoPolicy::class)]
final class Concepto extends Model
{
    /** @use HasFactory<ConceptoFactory> */
    use HasFactory, NormalizaCodigo;

    /**
     * @return HasMany<Tarifa, $this>
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class)->orderBy('vigente_desde');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoConcepto::class,
            'fuente' => FuenteConcepto::class,
            'activo' => 'boolean',
        ];
    }
}
