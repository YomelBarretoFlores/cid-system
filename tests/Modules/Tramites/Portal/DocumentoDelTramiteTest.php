<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
});

function constanciaDe(Persona $persona, User $emisor): Documento
{
    return app(EmitirConstanciaDeMatricula::class)(Matricula::factory()->activa()->create(['persona_id' => $persona->id])->id, $emisor);
}

it('lets the student download the document a finished trámite issued, from the document column of its row', function (): void {
    $documento = constanciaDe($this->persona, $this->emisor);
    $tramite = Tramite::factory()->for(TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->sole())->create(['persona_id' => $this->persona->id]);
    DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'finalizado', 'etapa_actual' => 3, 'finalizado_en' => now(), 'documento_id' => $documento->id]);

    $this->actingAs($this->user)->get(route('estudiante.tramites'))->assertOk()
        ->assertSeeInOrder(['Documento</th>', $tramite->codigo, 'href="'.route('estudiante.documentos.pdf', $documento->id).'"', 'Descargar</a>'], false);
    $this->actingAs($this->user)->get(route('estudiante.documentos.pdf', $documento->id))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('answers 404 for someone else\'s document or a voided one', function (): void {
    $ajeno = constanciaDe(Persona::factory()->create(), $this->emisor);
    $anulado = constanciaDe($this->persona, $this->emisor);
    app(AnularDocumento::class)($anulado->id, 'Datos del estudiante incorrectos.', $this->emisor);

    $this->actingAs($this->user)->get(route('estudiante.documentos.pdf', $ajeno->id))->assertNotFound();
    $this->actingAs($this->user)->get(route('estudiante.documentos.pdf', $anulado->id))->assertNotFound();
});
