<?php

declare(strict_types=1);

it('publishes the privacy notice to guests with its data controller and the ARCO rights', function (): void {
    $this->get(route('privacidad'))
        ->assertOk()
        ->assertSee('Política de privacidad y tratamiento de datos personales')
        ->assertSee('Universidad Nacional Santiago Antúnez de Mayolo')
        ->assertSee('Av. Centenario N.° 200, Independencia, Huaraz, Áncash')
        ->assertSee('Ley N.° 29733')
        ->assertSee('Derechos de acceso, rectificación, cancelación y oposición (ARCO)')
        ->assertSee('Mesa de Partes de la UNASAM')
        ->assertSee('Modificar datos')
        ->assertSee('Última actualización');
});

it('links the privacy notice from the sign-up consent, the sign-in page and the public footer', function (string $ruta): void {
    $this->get(route($ruta))
        ->assertOk()
        ->assertSee('href="'.route('privacidad').'"', false);
})->with(['estudiante.registro', 'ingresar', 'portada']);

it('opens the privacy notice from the sign-up consent in a new tab', function (): void {
    $this->get(route('estudiante.registro'))
        ->assertSee('href="'.route('privacidad').'" target="_blank" rel="noopener"', false);
});
