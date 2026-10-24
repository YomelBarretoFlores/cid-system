<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware(['api', 'auth:sanctum'])->get('/api/v1/probe', fn (): string => 'ok');
});

it('answers unauthenticated API requests with 401 even when HTML is accepted', function (): void {
    $this->get('/api/v1/probe', ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('answers unauthenticated JSON API requests with 401', function (): void {
    $this->getJson('/api/v1/probe')->assertUnauthorized();
});
