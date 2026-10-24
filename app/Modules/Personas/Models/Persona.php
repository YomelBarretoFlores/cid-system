<?php

declare(strict_types=1);

namespace App\Modules\Personas\Models;

use App\Modules\Personas\Database\Factories\PersonaFactory;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('per_personas')]
#[RouteKey('ulid')]
#[Fillable([
    'user_id', 'tipo_documento', 'numero_documento', 'apellido_paterno', 'apellido_materno',
    'nombres', 'fecha_nacimiento', 'correo', 'celular', 'tipo_vinculo',
])]
#[UseFactory(PersonaFactory::class)]
final class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'tipo_vinculo' => 'publico_general',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->apellido_paterno} {$this->apellido_materno}").", {$this->nombres}");
    }

    /**
     * @return Attribute<string, string>
     */
    protected function numeroDocumento(): Attribute
    {
        return Attribute::set(fn (string $value): string => mb_strtoupper(trim($value)));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function correo(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : mb_strtolower(trim($value)));
    }

    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
            'tipo_vinculo' => TipoVinculo::class,
            'fecha_nacimiento' => 'date',
        ];
    }
}
