<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ListSolicitudesClasificacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Ui\BanderaDeIdioma;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoClasificacion::Ver->value, PermisoMatricula::Ver->value,
    ]));
    $this->japones = Idioma::factory()->create(['nombre' => 'Japonés', 'bandera' => BanderaDeIdioma::Japon]);
    $this->grupo = Grupo::factory()->for(Ciclo::factory()->for($this->japones))->create(['codigo' => 'JAP-1-M']);
});

it('shows the flag next to the language in the groups table', function (): void {
    Livewire::test(ListGrupos::class)
        ->assertSeeHtml('data-idioma="Japonés"')
        ->assertSeeHtml('#BC002D');
});

it('shows the flag next to the language in the placement requests table', function (): void {
    SolicitudClasificacion::factory()->create(['idioma_id' => $this->japones->id]);

    Livewire::test(ListSolicitudesClasificacion::class)
        ->assertSeeHtml('data-idioma="Japonés"')
        ->assertSeeHtml('#BC002D');
});

it('shows the language flag next to the group in the enrollments table', function (): void {
    Matricula::factory()->create(['grupo_id' => $this->grupo->id]);

    Livewire::test(ListMatriculas::class)
        ->assertSeeHtml('data-idioma="Japonés"')
        ->assertSee('JAP-1-M');
});
