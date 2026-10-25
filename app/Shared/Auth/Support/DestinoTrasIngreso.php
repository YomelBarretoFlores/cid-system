<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Shared\Auth\Enums\EntradaDelPortal;
use App\Shared\Auth\Models\User;

// Where a signed-in user lands when no page was requested before: one account, a module per profile.
final readonly class DestinoTrasIngreso
{
    public function __construct(
        private DocenteDelUsuario $docenteDelUsuario,
        private FichaDelUsuario $fichaDelUsuario,
    ) {}

    public function para(User $usuario, EntradaDelPortal $entrada): string
    {
        return match (true) {
            $entrada === EntradaDelPortal::Docente && ($this->docenteDelUsuario)($usuario->id) !== null => route('docente.grupos'),
            ($this->fichaDelUsuario)($usuario->id) instanceof FichaDeEstudiante => route('estudiante.inicio'),
            $usuario->can(User::ADMIN_PANEL_PERMISSION) => route('filament.admin.pages.dashboard'),
            default => route('estudiante.inicio'),
        };
    }
}
