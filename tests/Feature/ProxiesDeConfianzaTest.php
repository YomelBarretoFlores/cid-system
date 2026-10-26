<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/prueba-de-proxy', fn (Request $request): array => ['ip' => $request->ip(), 'segura' => $request->secure()]);
});

$cabecerasDelProxy = ['X-Forwarded-For' => '198.51.100.7', 'X-Forwarded-Proto' => 'https'];

it('ignores the forwarded headers when no proxy is trusted', function () use ($cabecerasDelProxy): void {
    expect(config('trustedproxy.proxies'))->toBeNull();

    $this->withHeaders($cabecerasDelProxy)->get('/prueba-de-proxy')->assertExactJson(['ip' => '127.0.0.1', 'segura' => false]);
});

it('takes the client address and https from the trusted proxy', function () use ($cabecerasDelProxy): void {
    config(['trustedproxy.proxies' => '*']);

    $this->withHeaders($cabecerasDelProxy)->get('/prueba-de-proxy')->assertExactJson(['ip' => '198.51.100.7', 'segura' => true]);
});
