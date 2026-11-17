<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Tests\Support\ReportesDelEstudiante;

it('shows each group as a checklist of attendance, grades and acta with the next step to take', function (): void {
    $escenario = ReportesDelEstudiante::escenario();

    $this->actingAs($escenario['curso']['usuario'])
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSeeInOrder(['Grupo ING-1-M', 'Asistencia', '3 de 10 clases', 'Notas', '2 de 3 notas', 'Acta final', 'Del 29 mar al 5 abr', 'Completar notas'])
        ->assertDontSee('Sílabo');
});

it('marks the acta as closed and stops asking for it once the teacher closes it', function (): void {
    $escenario = ReportesDelEstudiante::escenario();
    ReportesDelEstudiante::cerrarActa($escenario['curso']);

    $this->actingAs($escenario['curso']['usuario'])
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSeeInOrder(['Acta final', 'Cerrada']);
});

it('names the classroom of the next class on the group card', function (): void {
    $escenario = ReportesDelEstudiante::escenario();
    $this->travelTo(CarbonImmutable::parse('2027-03-15 12:00', 'America/Lima'));

    $this->actingAs($escenario['curso']['usuario'])
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSeeTextInOrder(['Grupo ING-1-M', 'Hoy · 18:00–20:00 · A-101']);
});
