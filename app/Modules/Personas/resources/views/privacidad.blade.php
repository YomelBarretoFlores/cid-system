@php
    $datos = [
        ['Identificación', 'Tipo y número de documento de identidad, apellidos, nombres, fecha de nacimiento, fotografía y tipo de vínculo con la UNASAM.'],
        ['Contacto', 'Correo electrónico, número de celular y dirección.'],
        ['Cuenta de acceso', 'Correo de ingreso y contraseña, que se guarda cifrada de forma irreversible.'],
        ['Académicos', 'Resultados de clasificación, matrículas, asistencias, notas, actas, constancias y certificados.'],
        ['Pagos', 'Concepto, monto, medio y fecha de pago, número de operación e imagen del comprobante.'],
        ['Trámites y comunicaciones', 'Solicitudes, su historial y el registro de los correos enviados (destinatario, asunto, fecha y estado de entrega, sin su contenido).'],
    ];
    $apartado = 'flex flex-col gap-2 border-t border-piedra-300 pt-5';
    $subtitulo = 'text-[17px] font-semibold text-marino-900';
@endphp
<x-layouts.base title="Política de privacidad" :marco="false">
<div class="tema-publico flex min-h-screen flex-col bg-papel text-marino-900">
    <x-publico.cabecera />
    <main id="contenido" class="mx-auto flex w-full max-w-[760px] flex-1 flex-col px-4 pt-10 pb-24 sm:px-6 sm:pt-16">
        <article class="flex flex-col gap-5 border border-piedra-300 bg-white p-5 text-[15px] leading-relaxed text-pretty sm:p-8">
            <header class="flex flex-col gap-1 border-b border-piedra-300 pb-4">
                <p class="text-[13.5px] text-marino-800/80">Universidad Nacional Santiago Antúnez de Mayolo · Centro de Idiomas y Lenguas Nativas</p>
                <h1 class="font-display text-[clamp(24px,2.4vw,30px)] leading-tight font-semibold">Política de privacidad y tratamiento de datos personales</h1>
                <p class="text-[13.5px] text-marino-800/80">Última actualización: 19 de noviembre de 2026</p>
            </header>

            <p>Esta política informa cómo el Sistema de Gestión Académica y Administrativa del Centro de Idiomas y Lenguas Nativas trata los datos personales de estudiantes, docentes, personal y público en general, conforme a la Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento.</p>

            <section class="{{ $apartado }}" aria-labelledby="titular">
                <h2 id="titular" class="{{ $subtitulo }}">1. Titular del banco de datos</h2>
                <p>Universidad Nacional Santiago Antúnez de Mayolo – Centro de Idiomas y Lenguas Nativas (CILN), con domicilio en Av. Centenario N.° 200, Independencia, Huaraz, Áncash.</p>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="finalidad">
                <h2 id="finalidad" class="{{ $subtitulo }}">2. Finalidad</h2>
                <p>Los datos se tratan para la gestión académica y administrativa del CILN: registro de la persona, examen de clasificación, matrícula, validación de pagos, evaluación y registro de notas, emisión y verificación de constancias y certificados, atención de trámites y envío de comunicaciones relacionadas con estos servicios.</p>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="datos">
                <h2 id="datos" class="{{ $subtitulo }}">3. Datos que se tratan</h2>
                <div class="overflow-x-auto">
                    <table class="tabla-formal">
                        <thead>
                            <tr><th scope="col">Categoría</th><th scope="col">Datos</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($datos as [$categoria, $detalle])
                                <tr><td class="etiqueta">{{ $categoria }}</td><td>{{ $detalle }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="base-legal">
                <h2 id="base-legal" class="{{ $subtitulo }}">4. Base legal y consentimiento</h2>
                <p>El tratamiento se sustenta en la Ley N.° 29733 y su Reglamento, y en el consentimiento que la persona otorga al crear su cuenta en el portal o, cuando la registra el personal del CILN, al firmar su autorización. La fecha y hora del consentimiento quedan registradas en su ficha.</p>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="conservacion">
                <h2 id="conservacion" class="{{ $subtitulo }}">5. Conservación</h2>
                <p>Los datos se conservan mientras dure la relación académica con el CILN y, después, durante el plazo que exijan las normas de archivo y de certificación aplicables a la universidad. Los registros académicos y financieros no se eliminan: se anulan dejando constancia, para garantizar la validez de los documentos emitidos.</p>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="transferencias">
                <h2 id="transferencias" class="{{ $subtitulo }}">6. Transferencias</h2>
                <p>Los datos no se venden ni se ceden a terceros. Solo se comunican por mandato legal o a las áreas de la UNASAM que los requieran para cumplir sus funciones. La verificación pública de constancias y certificados muestra únicamente los datos mínimos del documento, con el número de documento de identidad enmascarado.</p>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="seguridad">
                <h2 id="seguridad" class="{{ $subtitulo }}">7. Medidas de seguridad</h2>
                <ul class="flex list-none flex-col gap-1.5 pl-4">
                    <li>a) Conexión cifrada (HTTPS), sesiones cifradas y contraseñas guardadas con cifrado irreversible.</li>
                    <li>b) Acceso por roles y permisos: cada persona del CILN ve solo lo que su función requiere, y el panel del personal admite doble factor de autenticación.</li>
                    <li>c) Registro de auditoría inalterable de los cambios en los datos, con la fecha, la hora y el usuario que los hizo.</li>
                    <li>d) Fotografías y comprobantes guardados fuera del acceso público y entregados solo a quien está autorizado.</li>
                </ul>
            </section>

            <section class="{{ $apartado }}" aria-labelledby="arco">
                <h2 id="arco" class="{{ $subtitulo }}">8. Derechos de acceso, rectificación, cancelación y oposición (ARCO)</h2>
                <p>Toda persona puede solicitar el acceso a sus datos, su rectificación cuando sean inexactos, su cancelación cuando ya no sean necesarios para la finalidad indicada, y oponerse a su tratamiento, dentro de los límites que establece la ley.</p>
                <p>Para ejercer estos derechos, presente una solicitud dirigida a la Dirección del CILN mediante la Mesa de Partes de la UNASAM, con su nombre, su documento de identidad y el derecho que desea ejercer. Además, los estudiantes pueden actualizar sus datos de contacto desde la opción «Modificar datos» del portal del estudiante.</p>
                <p>Si considera que su solicitud no fue atendida, puede recurrir a la Autoridad Nacional de Protección de Datos Personales del Ministerio de Justicia y Derechos Humanos.</p>
            </section>
        </article>
    </main>
    <x-publico.pie :verificar="false" />
</div>
</x-layouts.base>
