<?php

declare(strict_types=1);

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

it('opens the portal on the student profile with the personal data and status, like the SGA', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id, 'nombres' => 'Rosa Elena', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'numero_documento' => '41234567',
        'correo' => 'rosa@example.test', 'celular' => '987654321', 'direccion' => 'Jr. Los Pinos 123, Huaraz', 'fecha_nacimiento' => '2001-05-14', 'tipo_vinculo' => TipoVinculo::PublicoGeneral]);

    $this->actingAs($user)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['QUISPE MAMANI ROSA ELENA', 'DNI 41234567', 'Resumen académico', 'Editar perfil', 'Datos personales', 'rosa@example.test', '987654321', 'Dirección', 'Jr. Los Pinos 123, Huaraz', '14/05/2001', 'Estado', 'Público general'])
        ->assertSee('href="'.route('estudiante.perfil.datos').'"', false)
        ->assertSee(route('estudiante.reportes'))
        ->assertSee(route('estudiante.pagos'))
        ->assertSee(route('estudiante.matricula'))
        ->assertSee(route('estudiante.matriculas'))
        ->assertSee(route('estudiante.clasificacion'));
});

it('refuses the portal to an account without a student record', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('estudiante.inicio'))
        ->assertForbidden()
        ->assertSee('Tu cuenta no está vinculada a una ficha de estudiante del CILN.');
});

it('describes the student record of a user and nothing for other users', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id, 'nombres' => 'Rosa', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'numero_documento' => '41234567', 'correo' => 'rosa@example.test', 'celular' => '987654321', 'direccion' => 'Av. Centenario 200', 'foto' => 'fotos/01JAAAAAAAAAAAAAAAAAAAAAAA.jpg', 'fecha_nacimiento' => '2001-05-14', 'tipo_vinculo' => TipoVinculo::EstudianteUnasam]);

    expect(app(FichaDelUsuario::class)($user->id))->toEqual(new FichaDeEstudiante(
        personaId: $persona->id,
        nombres: 'Rosa',
        nombreCompleto: 'Quispe Mamani, Rosa',
        documento: 'DNI 41234567',
        correo: 'rosa@example.test',
        celular: '987654321',
        fechaNacimiento: '14/05/2001',
        vinculo: 'Estudiante UNASAM',
        direccion: 'Av. Centenario 200',
        versionDeFoto: hash('crc32b', 'fotos/01JAAAAAAAAAAAAAAAAAAAAAAA.jpg'),
    ))->and(app(FichaDelUsuario::class)(User::factory()->create()->id))->toBeNull();
});
