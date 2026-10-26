<?php

declare(strict_types=1);

use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->estudiante = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->estudiante->id]);
});

it('shows the owner their receipt inline', function (): void {
    $pago = Pago::factory()->create(['persona_id' => $this->persona->id, 'recibo_path' => 'recibos/2027/propio.pdf']);
    Storage::disk('local')->put('recibos/2027/propio.pdf', '%PDF-1.4 recibo propio');

    $respuesta = $this->actingAs($this->estudiante)->get(route('estudiante.pagos.recibo', $pago));

    $respuesta->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($respuesta->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($respuesta->streamedContent())->toBe('%PDF-1.4 recibo propio');
});

it('answers not found for a receipt of another student', function (): void {
    $ajeno = Pago::factory()->create(['recibo_path' => 'recibos/2027/ajeno.pdf']);
    Storage::disk('local')->put('recibos/2027/ajeno.pdf', '%PDF-1.4 recibo ajeno');

    $this->actingAs($this->estudiante)
        ->get(route('estudiante.pagos.recibo', $ajeno))
        ->assertNotFound();
});

it('answers not found when the receipt file is missing', function (): void {
    $pago = Pago::factory()->create(['persona_id' => $this->persona->id, 'recibo_path' => 'recibos/2027/perdido.pdf']);

    $this->actingAs($this->estudiante)
        ->get(route('estudiante.pagos.recibo', $pago))
        ->assertNotFound();
});

it('sends guests to the portal sign-in instead of the receipt', function (): void {
    $pago = Pago::factory()->create(['persona_id' => $this->persona->id]);

    $this->get(route('estudiante.pagos.recibo', $pago))->assertRedirect(route('ingresar'));
});
