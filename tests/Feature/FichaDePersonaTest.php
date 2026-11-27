<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\CreateDocente;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Personas\MatriculasDeLaPersona;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Personas\PagosDeLaPersona;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\CreatePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;

function personalDeLaFicha(string ...$permisos): User
{
    return User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value, ...$permisos]);
}

beforeEach(function (): void {
    $this->persona = Persona::factory()->create(['numero_documento' => '40123456', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Ana']);
});

it('titles the person page with the name and shows only the tabs of the modules the staff member can see', function (): void {
    $this->actingAs(personalDeLaFicha(PermisoMatricula::Ver->value))
        ->get(PersonaResource::getUrl('view', ['record' => $this->persona]))
        ->assertOk()
        ->assertSee(['Quispe Mamani, Ana', 'DNI 40123456', 'Datos personales', 'Matrículas'])
        ->assertDontSee(['Pagos', 'Documentos', 'Trámites']);
});

it('lists in each tab only the records of the person', function (): void {
    $suya = Matricula::factory()->create(['persona_id' => $this->persona->id]);
    $ajena = Matricula::factory()->create();
    $suyo = Pago::factory()->create(['persona_id' => $this->persona->id]);
    $ajeno = Pago::factory()->create();

    Livewire::actingAs(personalDeLaFicha(PermisoMatricula::Ver->value))->test(MatriculasDeLaPersona::class, ['personaId' => $this->persona->id])
        ->assertCanSeeTableRecords([$suya])
        ->assertCanNotSeeTableRecords([$ajena]);

    Livewire::actingAs(personalDeLaFicha(PermisoPago::Ver->value))->test(PagosDeLaPersona::class, ['personaId' => $this->persona->id])
        ->assertCanSeeTableRecords([$suyo])
        ->assertCanNotSeeTableRecords([$ajeno]);
});

it('refuses a tab to staff without the permission of its module', function (): void {
    Livewire::actingAs(personalDeLaFicha())->test(PagosDeLaPersona::class, ['personaId' => $this->persona->id])->assertForbidden();
});

it('offers to register the person as a teacher with the form opened on them, or to open the teacher record', function (): void {
    $gestor = personalDeLaFicha(...PermisoDocencia::valores());

    Livewire::actingAs($gestor)->test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertActionVisible('registrarComoDocente')
        ->assertActionDoesNotExist('verDocente');

    Livewire::actingAs($gestor)->withQueryParams(['persona' => (string) $this->persona->id])->test(CreateDocente::class)
        ->assertSchemaStateSet(['persona.tipo_documento' => 'dni', 'persona.numero_documento' => '40123456'])
        ->assertSee('Quispe Mamani, Ana');

    Docente::factory()->create(['persona_id' => $this->persona->id]);

    Livewire::actingAs($gestor)->test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertActionVisible('verDocente')
        ->assertActionDoesNotExist('registrarComoDocente');
});

it('offers to register a payment with the payer already chosen, only to staff who register payments', function (): void {
    Livewire::actingAs(personalDeLaFicha(PermisoPago::Ver->value))->test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertActionDoesNotExist('registrarPago');

    $cajero = personalDeLaFicha(PermisoPago::Ver->value, PermisoPago::Registrar->value);

    Livewire::actingAs($cajero)->test(ViewPersona::class, ['record' => $this->persona->getRouteKey()])
        ->assertActionVisible('registrarPago');

    Livewire::actingAs($cajero)->withQueryParams(['persona' => (string) $this->persona->id])->test(CreatePago::class)
        ->assertSchemaStateSet(['persona_id' => $this->persona->id]);
});
