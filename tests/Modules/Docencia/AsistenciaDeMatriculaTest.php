<?php

declare(strict_types=1);

use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Docencia\Data\AsistenciaDeClase;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;
use Tests\Support\ReportesDelEstudiante;

it('lists every class of the student with both calls, the effective state and the class state, and totals over held and programmed classes', function (): void {
    $escenario = ReportesDelEstudiante::escenario();

    $asistencia = app(AsistenciaDeMatricula::class)($escenario['matricula']->id, $escenario['matricula']->grupo_id);

    expect(array_map(fn (AsistenciaDeClase $clase): array => [
        $clase->fecha->toDateString(), $clase->horario, $clase->inicio->etiqueta(), $clase->cierre?->etiqueta(), $clase->efectivo->etiqueta(), $clase->estadoDeLaClase,
    ], $asistencia->clases))->toBe([
        ['2027-03-01', '18:00 – 20:00', 'Presente', 'Presente', 'Presente', 'sin confirmar'],
        ['2027-03-03', '18:00 – 20:00', 'Presente', 'Ausente', 'Ausente', 'sin confirmar'],
        ['2027-03-08', '18:00 – 20:00', 'Presente', 'Presente', 'Presente', 'sin confirmar'],
    ])->and($asistencia)
        ->programadas->toBe(10)
        ->dictadas->toBe(3)
        ->presentes->toBe(2)
        ->faltas->toBe(1)
        ->and($asistencia->porcentajeSobreDictadas())->toBe(67)
        ->and($asistencia->porcentajeSobreProgramadas())->toBe(20);
});

it('counts the programmed classes of the whole period before it ends and leaves out other students', function (): void {
    [$usuario, $docente] = PortalDocente::docente();
    [$grupo, [$ana, $beto]] = PortalDocente::grupoConEstudiantes($docente, 'Alva', 'Bravo');
    PortalDocente::dictarClase($grupo, $docente, $usuario, [$ana->id => 'presente', $beto->id => 'falta']);
    $this->travelTo(CarbonImmutable::parse('2027-03-11 09:00', 'America/Lima'));

    $asistencia = app(AsistenciaDeMatricula::class)($beto->id, $grupo->id);

    expect($asistencia)->programadas->toBe(16)->dictadas->toBe(1)->faltas->toBe(1)->presentes->toBe(0)
        ->and($asistencia->clases[0]->cierre)->toBeNull()
        ->and($asistencia->clases[0]->estadoDeLaClase)->toBe('sin llamado de cierre')
        ->and($asistencia->porcentajeSobreDictadas())->toBe(0);
});

it('has no percentages without classes', function (): void {
    [, $docente] = PortalDocente::docente();
    [$grupo, [$ana]] = PortalDocente::grupoConEstudiantes($docente, 'Alva');

    $asistencia = app(AsistenciaDeMatricula::class)($ana->id, $grupo->id);

    expect($asistencia)->dictadas->toBe(0)->programadas->toBe(16)
        ->and($asistencia->porcentajeSobreDictadas())->toBeNull()
        ->and($asistencia->porcentajeSobreProgramadas())->toBe(0);
});
