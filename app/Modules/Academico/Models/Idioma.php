<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\IdiomaFactory;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use App\Shared\Ui\BanderaDeIdioma;
use App\Shared\Ui\BanderasDeIdiomas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('aca_idiomas')]
/**
 * @property BanderaDeIdioma|null $bandera
 */
#[Fillable(['codigo', 'nombre', 'es_lengua_nativa', 'activo', 'bandera', 'curriculo'])]
#[UseFactory(IdiomaFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Idioma extends Model
{
    /** @use HasFactory<IdiomaFactory> */
    use HasFactory, NormalizaCodigo, RenuevaContenidoPublico;

    protected static function booted(): void
    {
        // The flag lookup keeps one map per request; a saved language must show its new flag right away.
        self::saved(fn () => app()->forgetInstance(BanderasDeIdiomas::class));
        self::deleted(fn () => app()->forgetInstance(BanderasDeIdiomas::class));
    }

    /**
     * @return HasMany<Ciclo, $this>
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class)->orderBy('numero');
    }

    protected function casts(): array
    {
        return [
            'es_lengua_nativa' => 'boolean',
            'activo' => 'boolean',
            'bandera' => BanderaDeIdioma::class,
        ];
    }
}
