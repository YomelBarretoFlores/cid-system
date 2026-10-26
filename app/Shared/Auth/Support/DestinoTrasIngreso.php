<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;

// Where a signed-in user lands when no page was requested before: one entry and one account, the teacher module first for teachers who also study.
final readonly class DestinoTrasIngreso
{
    public function __construct(
        private DocenteDelUsuario $docenteDelUsuario,
        private FichaDelUsuario $fichaDelUsuario,
    ) {}

    private const string PANEL = 'admin';

    // The page asked for before signing in wins, except a panel page: the panel only accepts sessions opened by its own sign-in.
    public function trasIngresar(User $usuario, ?string $pedido): string
    {
        return $pedido === null || $pedido === '' || $this->esDelPanel($pedido) ? $this->para($usuario) : $pedido;
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

    private function esDelPanel(string $pedido): bool
    {
        $ruta = (string) parse_url($pedido, PHP_URL_PATH);
        $panel = '/'.trim(Filament::getPanel(self::PANEL)->getPath(), '/');

        return $ruta === $panel || str_starts_with($ruta, $panel.'/');
    }
}
