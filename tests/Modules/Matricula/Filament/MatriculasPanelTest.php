<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ViewMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    $this->gestor = User::factory()->create(['name' => 'Coordinación Demo'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoMatricula::valores()]);
    $this->lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]);

    $periodo = Periodo::factory()->create(['nombre' => 'Enero 2027']);
    $ciclo = Ciclo::factory()->create();
    $this->origen = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => 'ING-I-A', 'matriculados' => 1]);
    $this->destino = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => 'ING-I-B', 'cupo_maximo' => 20, 'matriculados' => 5]);
    $this->otroCiclo = Grupo::factory()->for($periodo)->create(['codigo' => 'ING-II-A']);

    $this->persona = Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    $this->matricula = Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $this->origen->id]);
});

it('keeps the enrollments screen away from users without the enrollment permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($cajero)->get(MatriculaResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->lector)->get(MatriculaResource::getUrl('index'))->assertOk();
});

it('lists enrollments with the student and group and finds them by the student', function (): void {
    $ajena = Matricula::factory()->create(['grupo_id' => $this->destino->id]);
    $this->actingAs($this->lector);

    Livewire::test(ListMatriculas::class)
        ->assertCanSeeTableRecords([$this->matricula, $ajena])
        ->assertSee('DNI 41234567 · Quispe Mamani, Rosa')
        ->assertSee('ING-I-A · Enero 2027')
        ->searchTable('41234567')
        ->assertCanSeeTableRecords([$this->matricula])
        ->assertCanNotSeeTableRecords([$ajena]);
});

it('filters enrollments by state and group', function (): void {
    $retirada = Matricula::factory()->retirada()->create(['grupo_id' => $this->destino->id]);
    $this->actingAs($this->lector);

    Livewire::test(ListMatriculas::class)
        ->filterTable('estado', EstadoMatricula::Retirada->value)
        ->assertCanSeeTableRecords([$retirada])
        ->assertCanNotSeeTableRecords([$this->matricula])
        ->resetTableFilters()
        ->filterTable('grupo_id', $this->origen->id)
        ->assertCanSeeTableRecords([$this->matricula])
        ->assertCanNotSeeTableRecords([$retirada]);
});

it('shows the movement history of an enrollment', function (): void {
    Movimiento::factory()->create([
        'matricula_id' => $this->matricula->id, 'tipo' => TipoMovimiento::Traslado,
        'grupo_origen_id' => $this->destino->id, 'grupo_destino_id' => $this->origen->id,
        'motivo' => 'Cruce de horarios', 'autorizado_por' => $this->gestor->id,
    ]);
    $this->actingAs($this->lector);

    Livewire::test(ViewMatricula::class, ['record' => $this->matricula->getRouteKey()])
        ->assertSee(['Quispe Mamani, Rosa', 'ING-I-A', 'Traslado', 'ING-I-B', 'Cruce de horarios', 'Coordinación Demo']);
});

it('transfers an enrollment to a group of the same period and cycle', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ViewMatricula::class, ['record' => $this->matricula->getRouteKey()])
        ->assertActionVisible('trasladar')
        ->callAction('trasladar', data: ['grupo_destino_id' => $this->destino->id, 'motivo' => 'Cruce de horarios'])
        ->assertHasNoFormErrors()
        ->assertNotified('Matrícula trasladada');

    expect($this->matricula->refresh()->grupo_id)->toBe($this->destino->id)
        ->and(Movimiento::query()->sole()->autorizado_por)->toBe($this->gestor->id);
});

it('withdraws an enrollment with a reason', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ViewMatricula::class, ['record' => $this->matricula->getRouteKey()])
        ->callAction('retirar', data: ['motivo' => 'Viaje por trabajo'])
        ->assertNotified('Matrícula retirada');

    expect($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Retirada);
});

it('offers as destinations only groups of the same period and cycle with vacancies', function (): void {
    Grupo::factory()->for($this->origen->periodo)->for($this->origen->ciclo)->create(['codigo' => 'ING-I-LLENO', 'cupo_maximo' => 3, 'cupo_minimo' => 1, 'matriculados' => 3]);
    $this->actingAs($this->gestor);

    Livewire::test(ViewMatricula::class, ['record' => $this->matricula->getRouteKey()])
        ->callAction('trasladar', data: ['grupo_destino_id' => $this->otroCiclo->id, 'motivo' => 'Cruce de horarios'])
        ->assertHasFormErrors(['grupo_destino_id']);

    expect(app(GruposParaTraslado::class)($this->origen->id))->toBe([$this->destino->id => 'ING-I-B · 15 vacantes'])
        ->and($this->matricula->refresh()->grupo_id)->toBe($this->origen->id);
});

it('shows the business refusal and changes nothing', function (): void {
    $this->origen->update(['estado' => EstadoGrupo::Finalizado]);
    $this->actingAs($this->gestor);

    Livewire::test(ViewMatricula::class, ['record' => $this->matricula->getRouteKey()])
        ->callAction('retirar', data: ['motivo' => 'Viaje por trabajo'])
        ->assertNotified('El grupo ING-I-A está Finalizado y ya no admite retiros.');

    expect($this->matricula->refresh()->estado)->toBe(EstadoMatricula::Activa)
        ->and(Movimiento::query()->count())->toBe(0);
});

it('offers transfer and withdrawal only to managers and only for active enrollments', function (): void {
    $retirada = Matricula::factory()->retirada()->create(['grupo_id' => $this->origen->id]);

    $this->actingAs($this->lector);
    Livewire::test(ListMatriculas::class)
        ->assertActionHidden(TestAction::make('trasladar')->table($this->matricula))
        ->assertActionHidden(TestAction::make('retirar')->table($this->matricula));

    $this->actingAs($this->gestor);
    Livewire::test(ListMatriculas::class)
        ->assertActionVisible(TestAction::make('trasladar')->table($this->matricula))
        ->assertActionVisible(TestAction::make('retirar')->table($this->matricula))
        ->assertActionHidden(TestAction::make('trasladar')->table($retirada))
        ->assertActionHidden(TestAction::make('retirar')->table($retirada));
});

it('lets staff with the enrollment permission download the enrollment record', function (): void {
    Pdf::fake();

    $this->actingAs($this->lector)->get(route('panel.matriculas.ficha', $this->matricula))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains(['FICHA DE MATRÍCULA', 'Quispe Mamani, Rosa', 'ING-I-A']));

    $this->actingAs(User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION))
        ->get(route('panel.matriculas.ficha', $this->matricula))
        ->assertForbidden();
});

it('keeps the staff enrollment record behind admin panel access', function (): void {
    $sinPanel = User::factory()->create()->givePermissionTo(PermisoMatricula::Ver->value);

    $this->actingAs($sinPanel)->get(route('panel.matriculas.ficha', $this->matricula))->assertForbidden();
});
