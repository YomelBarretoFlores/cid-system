<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarLlamadoDeCierre;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Filament\Docente\Pages\AsistenciaDelGrupo;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

// Wednesday 18:00–20:00 group of a period that starts on Monday 2027-03-01, so 2027-03-10 falls in week 2.
beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    [$this->ana, $this->luis] = collect(['Quispe', 'Zapata'])->map(fn (string $apellido): Matricula => Matricula::factory()->create([
        'grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => $apellido])->id, 'matriculado_en' => '2027-02-20 15:00:00+00',
    ]))->all();
    $this->todosPresentes = [$this->ana->id => 'presente', $this->luis->id => 'presente'];
});

function aLaHora(string $hora, string $fecha = '2027-03-10'): void
{
    test()->travelTo(CarbonImmutable::parse("{$fecha} {$hora}", 'America/Lima'));
}

function primerLlamado(?string $tema, string $fecha = '2027-03-10'): ClaseDictada
{
    return app(RegistrarAsistencia::class)(test()->grupo->id, test()->docente->id, $fecha, '18:00', test()->todosPresentes, test()->usuario, $tema);
}

function segundoLlamado(?string $tema): ClaseDictada
{
    return app(RegistrarLlamadoDeCierre::class)(test()->grupo->id, test()->docente->id, '2027-03-10', '18:00', test()->todosPresentes, test()->usuario, $tema);
}

function silaboDelGrupo(EstadoSilabo $estado): Silabo
{
    $publicado = $estado === EstadoSilabo::Publicado ? ['publicado_en' => '2027-02-25 10:00:00+00', 'publicado_por' => test()->usuario->id] : [];
    $silabo = Silabo::query()->create(['grupo_id' => test()->grupo->id, 'estado' => $estado, ...$publicado]);

    foreach (range(1, 8) as $semana) {
        SemanaDeSilabo::query()->create(['silabo_id' => $silabo->id, 'semana' => $semana, 'unidad' => "Unidad {$semana}", 'saber' => "Vocabulario {$semana}"]);
    }

    return $silabo;
}

it('saves the topic of the first call trimmed, and leaves it empty when the teacher has not written it yet', function (?string $tema, ?string $guardado): void {
    aLaHora('18:05');

    expect(primerLlamado($tema)->refresh()->tema)->toBe($guardado);
})->with([
    'written' => ['  Saludos   y presentaciones ', 'Saludos y presentaciones'],
    'not yet' => [null, null],
    'blank' => ['   ', null],
]);

it('refuses the closing call without a topic and changes nothing', function (): void {
    aLaHora('18:05');
    primerLlamado(null);
    aLaHora('19:50');

    expect(fn (): ClaseDictada => segundoLlamado('  '))->toThrow(ReglaDeNegocioIncumplida::class, 'Escribe el tema que dictaste en esta clase.');

    $clase = ClaseDictada::query()->sole();
    expect([$clase->llamado_cierre_en, $clase->confirmacion, $clase->tema])->toBe([null, null, null])
        ->and(Asistencia::query()->whereNotNull('estado_cierre')->count())->toBe(0);
});

it('keeps the topic of the first call in the closing call unless the teacher changes it', function (?string $enElCierre, string $guardado): void {
    aLaHora('18:05');
    primerLlamado('Saludos');
    aLaHora('19:50');

    expect(segundoLlamado($enElCierre)->refresh()->tema)->toBe($guardado);
})->with([
    'unchanged' => [null, 'Saludos'],
    'changed' => ['Saludos y despedidas', 'Saludos y despedidas'],
]);

it('requires the topic of a past class recorded later and saves it', function (): void {
    aLaHora('18:05');

    expect(fn (): ClaseDictada => primerLlamado(null, '2027-03-03'))->toThrow(ReglaDeNegocioIncumplida::class, 'Escribe el tema que dictaste en esta clase.')
        ->and(ClaseDictada::query()->count())->toBe(0);

    $clase = primerLlamado('El alfabeto', '2027-03-03')->refresh();

    expect([$clase->registro_posterior, $clase->tema])->toBe([true, 'El alfabeto']);
});

it('refuses a topic longer than a thousand characters', function (): void {
    aLaHora('18:05');

    expect(fn (): ClaseDictada => primerLlamado(str_repeat('a', 1001)))->toThrow(ReglaDeNegocioIncumplida::class, 'El tema de la clase no puede pasar de 1000 caracteres.');
});

it('links the class to the week of the published syllabus that covers its date', function (EstadoSilabo $estado, ?int $semana): void {
    $silabo = silaboDelGrupo($estado);
    aLaHora('18:05');

    $clase = primerLlamado('Saludos')->refresh();

    expect($clase->silabo_semana_id)->toBe($semana === null ? null : $silabo->semanas()->where('semana', $semana)->value('id'));
})->with([
    'published' => [EstadoSilabo::Publicado, 2],
    'draft' => [EstadoSilabo::Borrador, null],
]);

it('suggests the content of the syllabus week as the topic and saves what the teacher writes', function (): void {
    Filament::setCurrentPanel('docente');
    silaboDelGrupo(EstadoSilabo::Publicado);
    aLaHora('18:05');

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('tema', 'Unidad 2: Vocabulario 2')
        ->set('tema', 'Unidad 2: Vocabulario de la familia')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(ClaseDictada::query()->sole()->tema)->toBe('Unidad 2: Vocabulario de la familia');
});

it('loads the saved topic instead of the suggestion when the class is chosen again', function (): void {
    Filament::setCurrentPanel('docente');
    silaboDelGrupo(EstadoSilabo::Publicado);
    aLaHora('18:05');
    primerLlamado('Repaso general');

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('tema', 'Repaso general');
});
