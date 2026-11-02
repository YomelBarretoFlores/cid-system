<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\CreateAula;
use App\Modules\Academico\Models\Aula;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

it('shows the built-in required message of an admin form in Spanish with the field label', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoAcademico::Gestionar->value]));

    $component = Livewire::test(CreateAula::class)
        ->fillForm(['codigo' => 'E-505', 'nombre' => '', 'capacidad' => 20])
        ->call('create')
        ->assertHasFormErrors(['nombre' => 'required']);

    expect($component->errors()->first('data.nombre'))->toBe('El campo nombre es obligatorio.')
        ->and(Aula::query()->count())->toBe(0);
});

it('translates the built-in validation rules to Spanish', function (): void {
    expect(Validator::make(['correo' => 'x'], ['correo' => 'email'])->errors()->first('correo'))
        ->toBe('El campo correo debe ser una dirección de correo electrónico válida.');
});
