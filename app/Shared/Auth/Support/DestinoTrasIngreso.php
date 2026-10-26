<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Shared\Auth\Models\User;

// Where a signed-in user lands when no page was requested before: one entry and one account, the teacher module first for teachers who also study.
final readonly class DestinoTrasIngreso
{
    public function __construct(
        private DocenteDelUsuario $docenteDelUsuario,
        private FichaDelUsuario $fichaDelUsuario,
    ) {}

    // The page asked for before signing in wins, except a panel page: the panel only accepts its own sign-in.
    public function trasIngresar(User $usuario, mixed $pedido): string
    {
        $ruta = is_string($pedido) ? (string) parse_url($pedido, PHP_URL_PATH) : '';
        $esDelPanel = $ruta === '/admin' || str_starts_with($ruta, '/admin/');

        return is_string($pedido) && $pedido !== '' && ! $esDelPanel ? $pedido : $this->para($usuario);
    }

    public function para(User $usuario): string
    {
        return match (true) {
            ($this->docenteDelUsuario)($usuario->id) !== null => route('docente.grupos'),
            ($this->fichaDelUsuario)($usuario->id) instanceof FichaDeEstudiante => route('estudiante.inicio'),
            $usuario->can(User::ADMIN_PANEL_PERMISSION) => route('filament.admin.pages.dashboard'),
            default => route('estudiante.inicio'),
        };
    }
}
