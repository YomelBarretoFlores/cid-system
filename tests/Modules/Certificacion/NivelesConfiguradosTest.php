<?php

declare(strict_types=1);

use App\Modules\Academico\Database\Seeders\AcademicoDemoSeeder;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirCertificadosDelPeriodo;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\FaltantesDelNivel;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 11:30', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->ana = Persona::factory()->create();
});

function faltantesDe(Persona $persona): array
{
    return array_map(fn (FaltantesDelNivel $nivel): array => [$nivel->nivel, $nivel->ciclos], app(RecordDeNotas::class)($persona->id)[0]->faltantes);
}

it('certifies a level with the cycles the language configures for it, not a fixed four', function (): void {
    ActasFinales::planDeEstudios('Inglés', [3, 4, 4]);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado'], [3, '16.00', 'aprobado'], [4, '17.00', 'aprobado']]);

    expect(app(NivelesPorCertificar::class)($this->ana->id))->toEqual([new NivelPorCertificar('Inglés', NivelCurricular::Basico)])
        ->and(faltantesDe($this->ana))->toBe([['Básico', []], ['Intermedio', [5, 6, 7]]]);

    $certificado = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    expect($certificado)
        ->horas_lectivas->toBe(180)
        ->nota_final->toBe('15.00')
        ->and(array_column($certificado->detalle['ciclos'], 'ciclo_numero'))->toBe([1, 2, 3])
        ->and(fn () => app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Intermedio, $this->emisor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La persona aún no aprueba todos los ciclos del nivel Intermedio de Inglés en actas cerradas.');
});

it('leaves out of the level a cycle the CILN deactivated', function (): void {
    ActasFinales::planDeEstudios('Inglés');
    Ciclo::query()->where('numero', 3)->update(['activo' => false]);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado'], [4, '16.00', 'aprobado']]);

    expect(faltantesDe($this->ana))->toBe([['Básico', []]])
        ->and(app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor)->detalle['ciclos'])->toHaveCount(3);
});

it('keeps the four cycles per level of the seeded study plan', function (): void {
    $this->seed(AcademicoDemoSeeder::class);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado'], [3, '16.00', 'aprobado'], [5, '13.00', 'aprobado']]);

    expect(faltantesDe($this->ana))->toBe([['Básico', [4]], ['Intermedio', [6, 7, 8]]])
        ->and(app(NivelesPorCertificar::class)($this->ana->id))->toBe([])
        ->and(fn () => app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La persona aún no aprueba todos los ciclos del nivel Básico de Inglés en actas cerradas.');

    ActasFinales::historial($this->ana, 'Inglés', [[4, '17.00', 'aprobado']], periodoId: $periodo = Periodo::factory()->create()->id);

    expect(app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor))->toHaveCount(1)
        ->and(Documento::query()->sole())
        ->horas_lectivas->toBe(240)
        ->nota_final->toBe('15.50')
        ->and(array_column(Documento::query()->sole()->detalle['ciclos'], 'ciclo_numero'))->toBe([1, 2, 3, 4]);
});
