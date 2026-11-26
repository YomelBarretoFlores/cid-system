<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

use App\Modules\Personas\Models\TextosLegales;
use Carbon\CarbonImmutable;

final readonly class TextosLegalesVigentes
{
    public const string POLITICA = <<<'MARKDOWN'
        Esta política informa cómo el Sistema de Gestión Académica y Administrativa del {centro} trata los datos personales de estudiantes, docentes, personal y público en general, conforme a la Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento.

        ## 1. Titular del banco de datos

        {universidad} – {centro} (CILN), con domicilio en {domicilio}.

        ## 2. Finalidad

        Los datos se tratan para la gestión académica y administrativa del CILN: registro de la persona, examen de clasificación, matrícula, validación de pagos, evaluación y registro de notas, emisión y verificación de constancias y certificados, atención de trámites y envío de comunicaciones relacionadas con estos servicios.

        ## 3. Datos que se tratan

        | Categoría | Datos |
        |:--|:--|
        | Identificación | Tipo y número de documento de identidad, apellidos, nombres, fecha de nacimiento, fotografía y tipo de vínculo con la UNASAM. |
        | Contacto | Correo electrónico, número de celular y dirección. |
        | Cuenta de acceso | Correo de ingreso y contraseña, que se guarda cifrada de forma irreversible. |
        | Académicos | Resultados de clasificación, matrículas, asistencias, notas, actas, constancias y certificados. |
        | Pagos | Concepto, monto, medio y fecha de pago, número de operación e imagen del comprobante. |
        | Trámites y comunicaciones | Solicitudes, su historial y el registro de los correos enviados (destinatario, asunto, fecha y estado de entrega, sin su contenido). |

        ## 4. Base legal y consentimiento

        El tratamiento se sustenta en la Ley N.° 29733 y su Reglamento, y en el consentimiento que la persona otorga al crear su cuenta en el portal o, cuando la registra el personal del CILN, al firmar su autorización. La fecha y hora del consentimiento quedan registradas en su ficha.

        ## 5. Conservación

        Los datos se conservan mientras dure la relación académica con el CILN y, después, durante el plazo que exijan las normas de archivo y de certificación aplicables a la universidad. Los registros académicos y financieros no se eliminan: se anulan dejando constancia, para garantizar la validez de los documentos emitidos.

        ## 6. Transferencias

        Los datos no se venden ni se ceden a terceros. Solo se comunican por mandato legal o a las áreas de la UNASAM que los requieran para cumplir sus funciones. La verificación pública de constancias y certificados muestra únicamente los datos mínimos del documento, con el número de documento de identidad enmascarado.

        ## 7. Medidas de seguridad

        - a) Conexión cifrada (HTTPS), sesiones cifradas y contraseñas guardadas con cifrado irreversible.
        - b) Acceso por roles y permisos: cada persona del CILN ve solo lo que su función requiere, y el panel del personal admite doble factor de autenticación.
        - c) Registro de auditoría inalterable de los cambios en los datos, con la fecha, la hora y el usuario que los hizo.
        - d) Fotografías y comprobantes guardados fuera del acceso público y entregados solo a quien está autorizado.

        ## 8. Derechos de acceso, rectificación, cancelación y oposición (ARCO)

        Toda persona puede solicitar el acceso a sus datos, su rectificación cuando sean inexactos, su cancelación cuando ya no sean necesarios para la finalidad indicada, y oponerse a su tratamiento, dentro de los límites que establece la ley.

        Para ejercer estos derechos, presente una solicitud dirigida a la Dirección del CILN mediante la Mesa de Partes de la UNASAM, con su nombre, su documento de identidad y el derecho que desea ejercer. Además, los estudiantes pueden actualizar sus datos de contacto desde la opción «Modificar datos» del portal del estudiante.

        Si considera que su solicitud no fue atendida, puede recurrir a la Autoridad Nacional de Protección de Datos Personales del Ministerio de Justicia y Derechos Humanos.
        MARKDOWN;

    // The university's legal domicile, the data controller; the centre's own address (institutional data) is a different one.
    public const string DOMICILIO = 'Av. Centenario N.° 200, Independencia, Huaraz, Áncash';

    public const string CONSENTIMIENTO = 'Acepto que el CILN de la UNASAM trate mis datos personales para gestionar mi matrícula, pagos y certificados, conforme a la Ley N° 29733, Ley de Protección de Datos Personales, y según su {politica}.';

    public const string ACTUALIZADA_EL = '2026-11-19';

    public function __construct(
        public string $politica = self::POLITICA,
        public string $domicilio = self::DOMICILIO,
        public string $consentimiento = self::CONSENTIMIENTO,
        public string $actualizadaEl = self::ACTUALIZADA_EL,
    ) {}

    public static function desde(TextosLegales $textos): self
    {
        return new self($textos->politica_privacidad, $textos->domicilio_titular, $textos->consentimiento_registro, $textos->politica_actualizada_el->toDateString());
    }

    public function fechaDeActualizacion(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->actualizadaEl, 'America/Lima');
    }
}
