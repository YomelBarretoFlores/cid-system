<?php

declare(strict_types=1);

it('reports the application as healthy', function (): void {
    $this->get('/up')->assertOk();
});

it('serves the public home page', function (): void {
    $this->get('/')->assertOk();
});
