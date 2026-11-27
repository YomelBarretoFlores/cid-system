<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Auth\Authenticatable;

// The person page gathers what each module knows about the person: modules add a tab or a shortcut here from their provider,
// so the Personas module never depends on them.
final class FichaDePersonaEnElPanel
{
    /** @var array<string, array{titulo: string, componente: class-string<TablaDeLaPersona>, permiso: string, orden: int}> */
    private array $pestanas = [];

    /** @var array<string, array{accion: Closure(int): ?Action, orden: int}> */
    private array $atajos = [];

    /**
     * @param  class-string<TablaDeLaPersona>  $componente
     */
    public function pestana(string $clave, string $titulo, string $componente, string $permiso, int $orden): void
    {
        $this->pestanas[$clave] = ['titulo' => $titulo, 'componente' => $componente, 'permiso' => $permiso, 'orden' => $orden];
    }

    /**
     * @param  Closure(int): ?Action  $accion  receives the person id; null hides the shortcut
     */
    public function atajo(string $clave, Closure $accion, int $orden): void
    {
        $this->atajos[$clave] = ['accion' => $accion, 'orden' => $orden];
    }

    /**
     * @return list<array{clave: string, titulo: string, componente: class-string<TablaDeLaPersona>}>
     */
    public function pestanas(?Authenticatable $usuario): array
    {
        $visibles = array_filter($this->pestanas, fn (array $pestana): bool => $usuario instanceof Authenticatable && method_exists($usuario, 'can') && $usuario->can($pestana['permiso']) === true);
        uasort($visibles, fn (array $a, array $b): int => $a['orden'] <=> $b['orden']);

        return array_map(
            fn (string $clave, array $pestana): array => ['clave' => $clave, 'titulo' => $pestana['titulo'], 'componente' => $pestana['componente']],
            array_keys($visibles),
            $visibles,
        );
    }

    /**
     * @return list<Action>
     */
    public function atajos(int $personaId): array
    {
        $atajos = $this->atajos;
        uasort($atajos, fn (array $a, array $b): int => $a['orden'] <=> $b['orden']);

        return array_values(array_filter(array_map(fn (array $atajo): ?Action => ($atajo['accion'])($personaId), $atajos)));
    }
}
