<?php

declare(strict_types=1);

use App\Modules\Personas\Livewire\SubirFotografia;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
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

// A real JPEG carrying an EXIF block: orientation always, and a GPS latitude when asked.
function jpegConExif(int $ancho, int $alto, int $orientacion = 1, bool $gps = false): UploadedFile
{
    $imagen = imagecreatetruecolor($ancho, $alto);
    ob_start();
    imagejpeg($imagen, null, 90);
    $jpeg = (string) ob_get_clean();

    $entradas = $gps ? 2 : 1;
    $tiff = 'II*'."\0".pack('V', 8).pack('v', $entradas).pack('vvVv', 0x0112, 3, 1, $orientacion)."\0\0";
    if ($gps) {
        $tiff .= pack('vvVV', 0x8825, 4, 1, 8 + 2 + $entradas * 12 + 4);
    }
    $tiff .= pack('V', 0);
    if ($gps) {
        $tiff .= pack('v', 1).pack('vvV', 0x0001, 2, 2).'S'."\0\0\0".pack('V', 0);
    }
    $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\0\0".$tiff;

    return UploadedFile::fake()->createWithContent('foto.jpg', "\xFF\xD8".$app1.substr($jpeg, 2));
}

function fotoGuardada(): string
{
    return Storage::disk('local')->get((string) test()->ficha->fresh()->foto);
}

it('strips the location and every other metadata from the stored photo', function (): void {
    $foto = jpegConExif(400, 300, gps: true);
    expect(file_get_contents($foto->getRealPath()))->toContain("Exif\0\0");

    subirFoto($foto)->assertHasNoErrors();

    expect(fotoGuardada())->not->toContain("Exif\0\0")
        ->and(getimagesizefromstring(fotoGuardada())[2])->toBe(IMAGETYPE_JPEG);
});

it('turns a phone photo upright using its orientation tag', function (): void {
    subirFoto(jpegConExif(400, 200, orientacion: 6))->assertHasNoErrors();

    expect(array_slice(getimagesizefromstring(fotoGuardada()), 0, 2))->toBe([200, 400]);
});

it('reduces a large photo to at most 800 pixels on its longest side, keeping its proportions and format', function (string $archivo, int $tipo): void {
    subirFoto(UploadedFile::fake()->image($archivo, 2000, 1500))->assertHasNoErrors();

    $medidas = getimagesizefromstring(fotoGuardada());
    expect([$medidas[0], $medidas[1], $medidas[2]])->toBe([800, 600, $tipo]);
})->with([
    'jpg' => ['grande.jpg', IMAGETYPE_JPEG],
    'png' => ['grande.png', IMAGETYPE_PNG],
]);

it('keeps a photo that is already small at its own size', function (): void {
    subirFoto(UploadedFile::fake()->image('foto.jpg', 300, 400))->assertHasNoErrors();

    expect(array_slice(getimagesizefromstring(fotoGuardada()), 0, 2))->toBe([300, 400]);
});

it('takes a bigger photo when the system parameters allow it and states the new limits', function (): void {
    app(GuardarParametrosDelSistema::class)(['foto_maximo_mb' => 4, 'foto_lado_maximo_px' => 4000], User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value));

    subirFoto(UploadedFile::fake()->image('foto.jpg', 400, 400)->size(4097))
        ->assertHasErrors(['foto' => 'La fotografía debe ser una imagen JPG o PNG de hasta 4 MB, entre 200 y 4000 píxeles por lado.']);
    subirFoto(UploadedFile::fake()->image('foto.png', 3500, 400)->size(3000))->assertHasNoErrors();

    expect($this->ficha->refresh()->foto)->not->toBeNull();
    Livewire::actingAs($this->alumno)->test(SubirFotografia::class)->assertSee('JPG o PNG · hasta 4 MB · entre 200 y 4000 píxeles por lado');
});
