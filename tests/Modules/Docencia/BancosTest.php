<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\GuardarDocente;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Bancos\BancoResource;
use App\Modules\Docencia\Filament\Resources\Bancos\Pages\CreateBanco;
use App\Modules\Docencia\Filament\Resources\Bancos\Pages\EditBanco;
use App\Modules\Docencia\Filament\Resources\Bancos\Pages\ListBancos;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\EditDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use App\Modules\Docencia\Models\Banco;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Forms\Components\Select;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
});

function docenteConBanco(string $banco, ?int $personaId = null): DatosDeDocente
{
    return new DatosDeDocente(
        $personaId ?? Persona::factory()->create()->id,
        'banco.docente@unasam.edu.pe',
        '10456789012',
        $banco,
        '04-123-456789',
        CondicionLaboral::idDe('contratado'),
        null,
        [new IdiomaHabilitado(test()->ingles->id, NivelCurricular::Avanzado)],
    );
}

it('ships the twelve banks suggested before, in the same order', function (): void {
    expect(Banco::query()->activos()->pluck('nombre')->all())->toBe([
        'Banco de la Nación', 'BCP', 'BBVA', 'Interbank', 'Scotiabank', 'Banco Pichincha', 'BanBif',
        'Caja Arequipa', 'Caja Huancayo', 'Caja Piura', 'Caja Trujillo', 'Caja Cusco',
    ])->and(config('ciln.docencia'))->toBeNull();
});

it('registers a teacher with a bank added to the catalog and shows its name', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateBanco::class)->fillForm(['nombre' => 'Caja Sullana', 'orden' => 13])->call('create')->assertHasNoFormErrors();

    $docente = app(GuardarDocente::class)(docenteConBanco('caja_sullana'));

    expect($docente->banco)->toBe('caja_sullana');

    Livewire::test(ViewDocente::class, ['record' => $docente->getRouteKey()])->assertSeeText('Caja Sullana');
});

it('refuses an inactive or unknown bank for a teacher but keeps the inactive one already stored', function (): void {
    $docente = app(GuardarDocente::class)(docenteConBanco('caja_cusco'));
    Banco::query()->where('codigo', 'caja_cusco')->update(['activo' => false]);

    expect(fn () => app(GuardarDocente::class)(docenteConBanco('caja_cusco')))->toThrow(ReglaDeNegocioIncumplida::class, 'El banco elegido no está disponible.')
        ->and(fn () => app(GuardarDocente::class)(docenteConBanco('banco_inventado')))->toThrow(ReglaDeNegocioIncumplida::class, 'El banco elegido no está disponible.')
        ->and(app(GuardarDocente::class)(docenteConBanco('caja_cusco', $docente->persona_id), $docente->id)->banco)->toBe('caja_cusco')
        ->and(Docente::query()->count())->toBe(1);
});

it('offers the inactive bank a teacher already has when staff edit the teacher, and keeps it on save', function (): void {
    $docente = app(GuardarDocente::class)(docenteConBanco('caja_cusco'));
    Banco::query()->where('codigo', 'caja_cusco')->update(['activo' => false]);

    Livewire::actingAs($this->gestor)->test(EditDocente::class, ['record' => $docente->getRouteKey()])
        ->assertSchemaStateSet(['banco' => 'caja_cusco'])
        ->assertFormFieldExists('banco', fn (Select $campo): bool => array_key_exists('caja_cusco', $campo->getOptions()))
        ->fillForm(['ruc' => '10456789099'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($docente->refresh())->banco->toBe('caja_cusco')->ruc->toBe('10456789099');
});

it('makes the database refuse a teacher bank outside the catalog', function (): void {
    expect(fn () => DB::transaction(fn () => Docente::factory()->create(['banco' => 'Caja Sullana'])))->toThrow(QueryException::class, 'doc_docentes_banco_foreign');
});

it('lets teaching managers rename and deactivate a bank whose code never changes, never delete it', function (): void {
    $this->actingAs($this->gestor);
    $bcp = Banco::query()->where('codigo', 'bcp')->sole();

    Livewire::test(EditBanco::class, ['record' => $bcp->getRouteKey()])->fillForm(['nombre' => 'Banco de Crédito del Perú', 'activo' => false])->call('save')->assertHasNoFormErrors();

    expect($bcp->refresh())->nombre->toBe('Banco de Crédito del Perú')->codigo->toBe('bcp')->activo->toBeFalse();

    Livewire::test(CreateBanco::class)->fillForm(['nombre' => 'BBVA', 'orden' => 20])->call('create')->assertHasFormErrors(['nombre']);
    Livewire::test(ListBancos::class)->assertCanSeeTableRecords(Banco::query()->orderBy('orden')->limit(5)->get())->assertTableActionDoesNotExist('delete');
});

it('keeps the bank screens away from staff who cannot manage teachers', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);

    $this->actingAs($lector)->get(BancoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->gestor)->get(BancoResource::getUrl('index'))->assertOk()->assertSeeText('Banco de la Nación');
});

it('moves free-typed banks into the catalog and back to their names on rollback', function (): void {
    $migracion = require base_path('app/Modules/Docencia/Database/Migrations/2026_12_01_110200_turn_teacher_banks_into_a_catalog.php');
    $docente = Docente::factory()->create(['banco' => 'bbva']);

    $migracion->down();

    expect($docente->refresh()->banco)->toBe('BBVA');
    DB::table('doc_docentes')->where('id', $docente->id)->update(['banco' => 'Caja Rural Los Andes']);

    $migracion->up();

    expect($docente->refresh()->banco)->toBe('caja_rural_los_andes')
        ->and($docente->bancoDeLaCuenta->nombre)->toBe('Caja Rural Los Andes');
});
