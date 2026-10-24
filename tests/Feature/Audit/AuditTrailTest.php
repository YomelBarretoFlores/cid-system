<?php

declare(strict_types=1);

use App\Shared\Audit\ContextoAuditoria;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    DB::statement('CREATE TABLE aud_prueba (id bigserial PRIMARY KEY, valor text NOT NULL)');
    DB::statement('CREATE TRIGGER aud_prueba_auditoria AFTER INSERT OR UPDATE OR DELETE ON aud_prueba FOR EACH ROW EXECUTE FUNCTION aud_registrar()');
});

function registrosDeAuditoria(): array
{
    return DB::table('aud_registros')->where('tabla', 'aud_prueba')->orderBy('id')->get()->all();
}

it('records every insert, update and delete with user, IP and both row versions', function (): void {
    app(ContextoAuditoria::class)->establecer(7, '10.0.0.5');

    $id = DB::table('aud_prueba')->insertGetId(['valor' => 'pendiente']);
    DB::table('aud_prueba')->where('id', $id)->update(['valor' => 'validado']);
    DB::table('aud_prueba')->where('id', $id)->delete();

    [$insert, $update, $delete] = registrosDeAuditoria();

    expect([$insert->operacion, $update->operacion, $delete->operacion])->toBe(['INSERT', 'UPDATE', 'DELETE'])
        ->and($insert->registro_id)->toBe($id)
        ->and($insert->usuario_id)->toBe(7)
        ->and($insert->ip)->toBe('10.0.0.5')
        ->and($insert->datos_anteriores)->toBeNull()
        ->and(json_decode((string) $update->datos_anteriores, true)['valor'])->toBe('pendiente')
        ->and(json_decode((string) $update->datos_nuevos, true)['valor'])->toBe('validado')
        ->and(json_decode((string) $delete->datos_anteriores, true)['valor'])->toBe('validado')
        ->and($delete->datos_nuevos)->toBeNull();
});

it('records system changes without a user when no request context exists', function (): void {
    DB::table('aud_prueba')->insert(['valor' => 'desde consola']);

    expect(registrosDeAuditoria()[0])
        ->usuario_id->toBeNull()
        ->ip->toBeNull();
});

it('forbids changing or erasing the audit trail', function (Closure $manipulacion): void {
    DB::table('aud_prueba')->insert(['valor' => 'x']);

    expect($manipulacion)->toThrow(QueryException::class, 'aud_registros es inmutable');
})->with([
    'update' => [fn () => DB::table('aud_registros')->update(['usuario_id' => 99])],
    'delete' => [fn () => DB::table('aud_registros')->delete()],
    'truncate' => [fn () => DB::statement('TRUNCATE aud_registros')],
]);

it('takes the user and IP of web requests', function (): void {
    Route::middleware('web')->get('/auditoria-probe', fn () => DB::selectOne(
        "select current_setting('app.usuario_id', true) as usuario, current_setting('app.ip', true) as ip",
    ));
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/auditoria-probe', ['REMOTE_ADDR' => '190.12.34.56'])
        ->assertJson(['usuario' => (string) $user->id, 'ip' => '190.12.34.56']);
});

it('takes the user of API requests authenticated with Sanctum', function (): void {
    Route::middleware(['api', 'auth:sanctum'])->get('/api/v1/auditoria-probe', fn () => DB::selectOne(
        "select current_setting('app.usuario_id', true) as usuario",
    ));
    $user = Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/auditoria-probe')->assertJson(['usuario' => (string) $user->id]);
});

it('leaves the user empty for guests', function (): void {
    Route::middleware('web')->get('/auditoria-probe', fn () => DB::selectOne(
        "select current_setting('app.usuario_id', true) as usuario",
    ));

    $this->get('/auditoria-probe')->assertJson(['usuario' => '']);
});
