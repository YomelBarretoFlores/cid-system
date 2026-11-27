<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Shared\Auth\Models\User;
use Filament\Schemas\Components\Component;

// Lets another module's panel form find a person by document, or register them in the same form when nobody has it.
interface FormularioDePersona
{
    public const string CAMPO = 'persona';

    /**
     * @return list<Component> fields kept under the form's `persona` key
     */
    public function campos(): array;

    /**
     * The id of the person the fields found, or null while nobody matches.
     *
     * @param  mixed  $estado  the form's `persona` state
     */
    public function encontrada(mixed $estado): ?int;

    /**
     * The state that shows an already registered person in the fields, to open a form with them chosen.
     *
     * @return array{tipo_documento: string, numero_documento: string}|array{}
     */
    public function estadoDe(int $personaId): array;

    /**
     * The id of the person found, or of the person registered with the fields. Call it inside the caller's transaction.
     *
     * @param  array<string, mixed>  $datos  the whole form state
     */
    public function persona(array $datos, User $actor): int;
}
