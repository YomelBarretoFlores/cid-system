<?php

declare(strict_types=1);

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->get('/api/v1/sonda-de-regla', fn () => throw new ReglaDeNegocioIncumplida('El periodo ya está cerrado.'));
    Route::middleware('web')->get('/sonda-de-regla-web', fn () => throw new ReglaDeNegocioIncumplida('El periodo ya está cerrado.'));
});

it('answers a business refusal on the API with 422 and its message only', function (): void {
    $this->get('/api/v1/sonda-de-regla', ['Accept' => 'text/html'])
        ->assertUnprocessable()
        ->assertExactJson(['message' => 'El periodo ya está cerrado.']);
});

it('keeps the web behavior for business refusals outside the API', function (): void {
    $this->get('/sonda-de-regla-web')->assertServerError();
});

it('answers an unknown API address with a JSON 404', function (): void {
    $this->get('/api/v1/no-existe')->assertNotFound()->assertJsonStructure(['message']);
});
