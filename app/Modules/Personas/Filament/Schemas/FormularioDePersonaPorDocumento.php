<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Schemas;

use App\Modules\Personas\Actions\RegistrarPersona;
use App\Modules\Personas\Contracts\FormularioDePersona;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;

final class FormularioDePersonaPorDocumento implements FormularioDePersona
{
    /** @var array<string, ?Persona> */
    private array $encontradas = [];

    public function campos(): array
    {
        return [
            Group::make()
                ->statePath(self::CAMPO)
                ->schema([
                    Grid::make(2)->schema(PersonaForm::documento(buscar: true)),
                    Callout::make(fn (Get $get): ?string => $this->buscar($get('tipo_documento'), $get('numero_documento'))?->etiqueta)
                        ->description('Ya está registrada: se usarán sus datos.')
                        ->success()
                        ->visible(fn (Get $get): bool => $this->buscar($get('tipo_documento'), $get('numero_documento')) instanceof Persona),
                    Callout::make('Datos de la nueva persona')
                        ->description('No hay nadie registrado con ese documento. Completa sus datos para registrarla.')
                        ->info()
                        ->visible(fn (Get $get): bool => $this->esNueva($get) && self::puedeRegistrar()),
                    Grid::make(3)
                        ->schema([...PersonaForm::personales(), PersonaForm::vinculo(), PersonaForm::consentimiento()->columnSpanFull()])
                        ->visible(fn (Get $get): bool => $this->esNueva($get) && self::puedeRegistrar()),
                    Callout::make('No hay nadie registrado con ese documento')
                        ->description('Pide a quien gestiona personas que la registre y vuelve a buscarla.')
                        ->warning()
                        ->visible(fn (Get $get): bool => $this->esNueva($get) && ! $this->puedeRegistrar()),
                ]),
        ];
    }

    public function encontrada(mixed $estado): ?int
    {
        return is_array($estado) ? $this->buscar($estado['tipo_documento'] ?? null, $estado['numero_documento'] ?? null)?->id : null;
    }

    public function persona(array $datos, User $actor): int
    {
        $estado = is_array($datos[self::CAMPO] ?? null) ? $datos[self::CAMPO] : [];

        return $this->encontrada($estado) ?? app(RegistrarPersona::class)(PersonaForm::datos($estado), $actor)->id;
    }

    private function esNueva(Get $get): bool
    {
        $numero = $get('numero_documento');

        return is_string($numero) && trim($numero) !== '' && ! $this->buscar($get('tipo_documento'), $numero) instanceof Persona;
    }

    private function buscar(mixed $tipo, mixed $numero): ?Persona
    {
        if (! is_string($tipo) || ! is_string($numero) || trim($numero) === '') {
            return null;
        }

        $numero = Persona::normalizarDocumento($numero);

        return $this->encontradas["{$tipo}|{$numero}"] ??= Persona::query()->with('tipoDeDocumento')
            ->where(['tipo_documento' => $tipo, 'numero_documento' => $numero])->first();
    }

    private function puedeRegistrar(): bool
    {
        return auth()->user()?->can(PermisoPersona::Gestionar->value) === true;
    }
}
