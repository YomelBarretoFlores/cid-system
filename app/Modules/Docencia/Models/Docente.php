<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Database\Factories\DocenteFactory;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Policies\DocentePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $codigo
 * @property string $cuenta_bancaria
 * @property CondicionLaboral $condicion_laboral
 * @property EstadoDocente $estado
 */
#[Table('doc_docentes')]
#[RouteKey('ulid')]
#[Fillable(['persona_id', 'correo_institucional', 'ruc', 'banco', 'cuenta_bancaria', 'condicion_laboral', 'estado'])]
#[UseFactory(DocenteFactory::class)]
#[UsePolicy(DocentePolicy::class)]
final class Docente extends Model
{
    /** @use HasFactory<DocenteFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'estado' => 'activo',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return HasMany<DocenteIdioma, $this>
     */
    public function idiomas(): HasMany
    {
        return $this->hasMany(DocenteIdioma::class);
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function cuentaEnmascarada(): Attribute
    {
        return Attribute::get(fn (): string => '•••• '.substr(str_replace('-', '', $this->cuenta_bancaria), -4));
    }

    protected function casts(): array
    {
        return [
            'cuenta_bancaria' => 'encrypted',
            'condicion_laboral' => CondicionLaboral::class,
            'estado' => EstadoDocente::class,
        ];
    }
}
