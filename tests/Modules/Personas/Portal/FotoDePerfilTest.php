<?php

declare(strict_types=1);

use App\Modules\Personas\Livewire\SubirFotografia;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->alumno = User::factory()->create(['name' => 'Rosa Quispe']);
    $this->ficha = Persona::factory()->create(['user_id' => $this->alumno->id]);
});

function subirFoto(UploadedFile $foto): Testable
{
    return Livewire::actingAs(test()->alumno)->test(SubirFotografia::class)
        ->set('foto', $foto)
        ->call('guardar');
}

it('stores a JPG or PNG photo privately and links it to the student', function (string $archivo, string $extension): void {
    subirFoto(UploadedFile::fake()->image($archivo, 400, 500))
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.perfil.foto'));

    $foto = (string) $this->ficha->fresh()->foto;
    expect($foto)->toMatch("#^fotos/[0-9A-Z]{26}\\.{$extension}$#")
        ->and(Storage::disk('local')->allFiles('fotos'))->toBe([$foto]);
})->with([
    'jpg' => ['foto.jpg', 'jpg'],
    'png' => ['foto.png', 'png'],
]);

it('rejects files that are not a JPG or PNG photo within the limits and stores nothing', function (UploadedFile $archivo, string $mensaje): void {
    subirFoto($archivo)
        ->assertHasErrors(['foto'])
        ->assertSee($mensaje);

    expect($this->ficha->fresh()->foto)->toBeNull()
        ->and(Storage::disk('local')->allFiles('fotos'))->toBe([]);
})->with([
    'a PDF' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('foto.pdf', "%PDF-1.4\n%%EOF\n"),
    'a script renamed as jpg' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('foto.jpg', '<?php system($_GET["c"]);'),
    'a GIF' => fn (): UploadedFile => UploadedFile::fake()->image('foto.gif', 400, 400),
    'over 2 MB' => fn (): UploadedFile => UploadedFile::fake()->image('foto.jpg', 400, 400)->size(2049),
    'smaller than 200 px' => fn (): UploadedFile => UploadedFile::fake()->image('foto.jpg', 150, 400),
    'larger than 3000 px' => fn (): UploadedFile => UploadedFile::fake()->image('foto.png', 3001, 400),
])->with(['La fotografía debe ser una imagen JPG o PNG de hasta 2 MB, entre 200 y 3000 píxeles por lado.']);

it('replaces the previous photo and deletes its file', function (): void {
    subirFoto(UploadedFile::fake()->image('primera.jpg', 400, 400));
    $primera = (string) $this->ficha->fresh()->foto;

    subirFoto(UploadedFile::fake()->image('segunda.png', 400, 400))->assertHasNoErrors();

    $segunda = (string) $this->ficha->fresh()->foto;
    expect($segunda)->not->toBe($primera)
        ->and(Storage::disk('local')->allFiles('fotos'))->toBe([$segunda]);
});

it('removes the photo and its file', function (): void {
    subirFoto(UploadedFile::fake()->image('foto.jpg', 400, 400));

    Livewire::actingAs($this->alumno)->test(SubirFotografia::class)
        ->call('quitar')
        ->assertRedirect(route('estudiante.perfil.foto'));

    expect($this->ficha->fresh()->foto)->toBeNull()
        ->and(Storage::disk('local')->allFiles('fotos'))->toBe([]);
});

it('limits how many photos an account uploads in an hour', function (): void {
    foreach (range(1, 10) as $intento) {
        subirFoto(UploadedFile::fake()->image('foto.jpg', 400, 400))->assertHasNoErrors();
    }

    subirFoto(UploadedFile::fake()->image('otra.jpg', 400, 400))
        ->assertHasErrors(['foto'])
        ->assertSee('Demasiados intentos.');

    expect(Storage::disk('local')->allFiles('fotos'))->toHaveCount(1);
    RateLimiter::clear("foto-de-perfil|{$this->alumno->id}");
});

it('serves the own photo privately and never as a public file', function (): void {
    subirFoto(UploadedFile::fake()->image('foto.png', 400, 400));

    $respuesta = $this->actingAs($this->alumno)->get(route('estudiante.perfil.foto.ver'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($respuesta->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
});

it('answers not found to a student without photo', function (): void {
    $this->actingAs($this->alumno)->get(route('estudiante.perfil.foto.ver'))->assertNotFound();
});

it('sends a guest to sign in instead of serving a photo', function (): void {
    $this->get(route('estudiante.perfil.foto.ver'))->assertRedirect(route('ingresar'));
});

it('serves only the signed-in student photo, whatever the request asks for', function (): void {
    $otra = Persona::factory()->create(['user_id' => User::factory()->create()->id, 'foto' => 'fotos/ajena.jpg']);
    Storage::disk('local')->put('fotos/ajena.jpg', 'foto ajena');

    $this->actingAs($this->alumno)->get(route('estudiante.perfil.foto.ver', ['persona' => $otra->ulid, 'foto' => $otra->foto]))->assertNotFound();
    $this->actingAs($this->alumno)->get('/estudiante/perfil/foto/imagen/'.$otra->ulid)->assertNotFound();
});

it('shows the photo instead of the initials in the sidebar and the profile header', function (): void {
    $this->actingAs($this->alumno)->get(route('estudiante.inicio'))->assertDontSee(route('estudiante.perfil.foto.ver'), false);

    subirFoto(UploadedFile::fake()->image('foto.jpg', 400, 400));

    $this->actingAs($this->alumno)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('src="'.route('estudiante.perfil.foto.ver').'?v=', false);
});

it('lays out the photo page like the SGA, with the upload on one side and the photo requirements on the other', function (): void {
    $this->actingAs($this->alumno)->get(route('estudiante.perfil.foto'))
        ->assertOk()
        ->assertSeeInOrder([
            'Fotografía personal', 'Sube y administra tu fotografía',
            'Subir foto', 'Sin fotografía', 'Sube tu fotografía aquí', 'Guardar fotografía',
            'Especificaciones', 'Requisitos', 'Imagen a color con fondo blanco', 'Tomada de frente',
            'Sin gafas ni lentes oscuros', 'Sin gorra ni sombrero', 'El rostro desde los hombros', 'JPG o PNG, de hasta 2 MB',
        ]);
});
