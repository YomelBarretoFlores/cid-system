<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The data dictionary is read from these catalog comments, so DSTI also sees them in any PostgreSQL client.
return new class extends Migration
{
    private const array COLUMNAS_COMUNES = [
        'id' => 'Identificador interno (llave primaria).',
        'ulid' => 'Identificador público no secuencial (ULID) usado en URL y en la API.',
        'created_at' => 'Fecha y hora de creación del registro.',
        'updated_at' => 'Fecha y hora de la última modificación del registro.',
        'deleted_at' => 'Fecha y hora de eliminación lógica; nulo si el registro está vigente.',
    ];

    public function up(): void
    {
        foreach ($this->descripciones() as $tabla => $columnas) {
            $this->comentarTabla($tabla, $columnas['@']);

            foreach ($this->columnasDe($tabla) as $columna) {
                $this->comentarColumna($tabla, $columna, $columnas[$columna] ?? self::COLUMNAS_COMUNES[$columna] ?? null);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->descripciones()) as $tabla) {
            $this->comentarTabla($tabla, null);

            foreach ($this->columnasDe($tabla) as $columna) {
                $this->comentarColumna($tabla, $columna, null);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function columnasDe(string $tabla): array
    {
        return array_values(DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', $tabla)
            ->orderBy('ordinal_position')
            ->pluck('column_name')
            ->map(strval(...))
            ->all());
    }

    private function comentarTabla(string $tabla, ?string $texto): void
    {
        DB::statement(sprintf('COMMENT ON TABLE %s IS %s', $this->identificador($tabla), $this->literal($texto)));
    }

    private function comentarColumna(string $tabla, string $columna, ?string $texto): void
    {
        DB::statement(sprintf('COMMENT ON COLUMN %s.%s IS %s', $this->identificador($tabla), $this->identificador($columna), $this->literal($texto)));
    }

    private function identificador(string $nombre): string
    {
        return '"'.str_replace('"', '""', $nombre).'"';
    }

    private function literal(?string $texto): string
    {
        return $texto === null ? 'NULL' : DB::getPdo()->quote($texto);
    }

    /**
     * @return array<string, array<string, string>> table => ['@' => table description, column => description]
     */
    private function descripciones(): array
    {
        return [
            'users' => [
                '@' => 'Cuentas de acceso al sistema (personal, docentes y estudiantes).',
                'name' => 'Nombre visible de la cuenta.',
                'email' => 'Correo electrónico con el que se inicia sesión; único.',
                'email_verified_at' => 'Fecha y hora en que se verificó el correo.',
                'password' => 'Contraseña cifrada con hash (bcrypt/argon); nunca se guarda en claro.',
                'remember_token' => 'Token de la opción "recordarme".',
                'app_authentication_secret' => 'Secreto cifrado del segundo factor (aplicación autenticadora).',
                'app_authentication_recovery_codes' => 'Códigos de recuperación cifrados del segundo factor.',
                'desactivado_en' => 'Fecha y hora en que la cuenta fue desactivada; nulo si está activa.',
                'tema' => 'Preferencia de apariencia de la cuenta (claro, oscuro o del sistema).',
            ],
            'password_reset_tokens' => [
                '@' => 'Tokens temporales para restablecer contraseñas.',
                'email' => 'Correo de la cuenta que solicitó el restablecimiento.',
                'token' => 'Token cifrado con hash enviado por correo.',
            ],
            'sessions' => [
                '@' => 'Sesiones web activas (contenido cifrado).',
                'user_id' => 'Cuenta dueña de la sesión; nulo para visitantes.',
                'ip_address' => 'Dirección IP desde la que se abrió la sesión.',
                'user_agent' => 'Navegador o cliente de la sesión.',
                'payload' => 'Datos cifrados de la sesión.',
                'last_activity' => 'Última actividad, en tiempo Unix.',
            ],
            'personal_access_tokens' => [
                '@' => 'Tokens de acceso a la API REST /api/v1 (Laravel Sanctum).',
                'tokenable_type' => 'Tipo de entidad dueña del token.',
                'tokenable_id' => 'Identificador de la entidad dueña del token.',
                'name' => 'Nombre descriptivo del token.',
                'token' => 'Token cifrado con hash SHA-256.',
                'abilities' => 'Permisos concedidos al token.',
                'last_used_at' => 'Fecha y hora del último uso.',
                'expires_at' => 'Fecha y hora de vencimiento.',
            ],
            'cache' => [
                '@' => 'Caché de la aplicación.',
                'key' => 'Clave de la entrada.',
                'value' => 'Valor serializado.',
                'expiration' => 'Vencimiento, en tiempo Unix.',
            ],
            'cache_locks' => [
                '@' => 'Bloqueos atómicos de la caché.',
                'key' => 'Clave del bloqueo.',
                'owner' => 'Proceso dueño del bloqueo.',
                'expiration' => 'Vencimiento, en tiempo Unix.',
            ],
            'jobs' => [
                '@' => 'Cola de trabajos en segundo plano (correos y tareas diferidas).',
                'queue' => 'Nombre de la cola.',
                'payload' => 'Trabajo serializado.',
                'attempts' => 'Intentos realizados.',
                'reserved_at' => 'Momento en que un proceso tomó el trabajo, en tiempo Unix.',
                'available_at' => 'Momento desde el que el trabajo puede ejecutarse, en tiempo Unix.',
            ],
            'job_batches' => [
                '@' => 'Lotes de trabajos en segundo plano.',
                'name' => 'Nombre del lote.',
                'total_jobs' => 'Total de trabajos del lote.',
                'pending_jobs' => 'Trabajos pendientes.',
                'failed_jobs' => 'Trabajos fallidos.',
                'failed_job_ids' => 'Identificadores de los trabajos fallidos.',
                'options' => 'Opciones serializadas del lote.',
                'cancelled_at' => 'Momento de cancelación, en tiempo Unix.',
                'finished_at' => 'Momento de término, en tiempo Unix.',
            ],
            'failed_jobs' => [
                '@' => 'Trabajos en segundo plano que fallaron, para revisión y reintento.',
                'uuid' => 'Identificador único del trabajo fallido.',
                'connection' => 'Conexión de cola usada.',
                'queue' => 'Nombre de la cola.',
                'payload' => 'Trabajo serializado.',
                'exception' => 'Error que produjo la falla.',
                'failed_at' => 'Fecha y hora de la falla.',
            ],
            'migrations' => [
                '@' => 'Control de versiones del esquema: migraciones ya aplicadas.',
                'migration' => 'Nombre del archivo de migración.',
                'batch' => 'Lote en que se aplicó.',
            ],
            'activity_log' => [
                '@' => 'Registro de actividad de la aplicación (spatie/laravel-activitylog). La pista oficial de auditoría es aud_registros.',
                'log_name' => 'Nombre del registro.',
                'description' => 'Descripción de la actividad.',
                'subject_type' => 'Tipo de entidad afectada.',
                'subject_id' => 'Identificador de la entidad afectada.',
                'event' => 'Evento registrado.',
                'causer_type' => 'Tipo de entidad que causó la actividad.',
                'causer_id' => 'Identificador de quien causó la actividad.',
                'attribute_changes' => 'Cambios de atributos.',
                'properties' => 'Datos adicionales.',
            ],
            'permissions' => [
                '@' => 'Permisos del control de acceso por roles (RBAC).',
                'name' => 'Nombre del permiso (por ejemplo, pagos.validar).',
                'guard_name' => 'Guardia de autenticación a la que pertenece.',
            ],
            'roles' => [
                '@' => 'Roles del control de acceso (RBAC).',
                'name' => 'Nombre del rol.',
                'guard_name' => 'Guardia de autenticación a la que pertenece.',
            ],
            'model_has_roles' => [
                '@' => 'Roles asignados a cada cuenta.',
                'role_id' => 'Rol asignado.',
                'model_type' => 'Tipo de entidad que recibe el rol.',
                'model_id' => 'Identificador de la cuenta que recibe el rol.',
            ],
            'model_has_permissions' => [
                '@' => 'Permisos asignados directamente a una cuenta.',
                'permission_id' => 'Permiso asignado.',
                'model_type' => 'Tipo de entidad que recibe el permiso.',
                'model_id' => 'Identificador de la cuenta que recibe el permiso.',
            ],
            'role_has_permissions' => [
                '@' => 'Permisos que incluye cada rol.',
                'permission_id' => 'Permiso incluido.',
                'role_id' => 'Rol que lo incluye.',
            ],
            'aud_registros' => [
                '@' => 'Pista de auditoría oficial e inmutable: un registro por cada alta, cambio o baja en las tablas críticas, escrito por triggers de PostgreSQL.',
                'tabla' => 'Tabla afectada.',
                'registro_id' => 'Identificador del registro afectado.',
                'operacion' => 'Operación realizada: INSERT, UPDATE o DELETE.',
                'datos_anteriores' => 'Valores antes del cambio (nulo en un alta).',
                'datos_nuevos' => 'Valores después del cambio (nulo en una baja).',
                'usuario_id' => 'Cuenta que hizo el cambio, tomada de la petición web.',
                'ip' => 'Dirección IP desde la que se hizo el cambio.',
                'ocurrido_en' => 'Fecha y hora exactas del cambio.',
                'solicitud_id' => 'Identificador de la petición web, para agrupar los cambios de una misma operación.',
            ],
            'per_personas' => [
                '@' => 'Identidad única de cada persona: postulantes, estudiantes y docentes. Contiene datos personales protegidos por la Ley 29733.',
                'user_id' => 'Cuenta de acceso de la persona; una cuenta por persona.',
                'tipo_documento' => 'Tipo de documento de identidad.',
                'numero_documento' => 'Número de documento: DNI de 8 dígitos; CE o pasaporte de 6 a 12 caracteres.',
                'apellido_paterno' => 'Apellido paterno.',
                'apellido_materno' => 'Apellido materno.',
                'nombres' => 'Nombres.',
                'fecha_nacimiento' => 'Fecha de nacimiento.',
                'correo' => 'Correo electrónico de contacto.',
                'celular' => 'Número de celular (7 a 15 dígitos, prefijo + opcional).',
                'tipo_vinculo' => 'Vínculo con la UNASAM, que define la tarifa: estudiante, docente o administrativo, o público general.',
                'direccion' => 'Dirección domiciliaria.',
                'foto' => 'Ruta de la fotografía en el almacenamiento privado.',
                'tratamiento_aceptado_en' => 'Fecha y hora en que aceptó el tratamiento de sus datos personales (Ley 29733) al registrarse.',
            ],
            'aca_idiomas' => [
                '@' => 'Idiomas extranjeros y lenguas nativas que ofrece el CILN.',
                'codigo' => 'Código corto del idioma; único y fijo tras crearse.',
                'nombre' => 'Nombre del idioma; único.',
                'es_lengua_nativa' => 'Indica si es una lengua originaria del Perú.',
                'activo' => 'Indica si el idioma ofrece ciclos para grupos nuevos.',
                'bandera' => 'Identificador de la bandera o emblema que se muestra en el portal.',
            ],
            'aca_ciclos' => [
                '@' => 'Plan de estudios de cada idioma: ciclos I a XII con su nivel y horas lectivas.',
                'idioma_id' => 'Idioma al que pertenece el ciclo.',
                'numero' => 'Número del ciclo (1 a 12); único por idioma.',
                'nivel' => 'Nivel curricular del ciclo.',
                'nombre' => 'Nombre del ciclo.',
                'horas_lectivas' => 'Horas lectivas del ciclo.',
                'activo' => 'Indica si el ciclo se puede ofertar.',
            ],
            'aca_periodos' => [
                '@' => 'Periodos académicos con sus fechas de clases, matrícula y registro de actas.',
                'anio' => 'Año del periodo.',
                'numero' => 'Número del periodo dentro del año (1 a 12).',
                'nombre' => 'Nombre del periodo.',
                'fecha_inicio' => 'Inicio de clases.',
                'fecha_fin' => 'Fin de clases.',
                'matricula_inicio' => 'Inicio de la matrícula.',
                'matricula_fin' => 'Fin de la matrícula.',
                'estado' => 'Estado del periodo.',
                'actas_inicio' => 'Inicio del plazo para registrar notas y cerrar actas.',
                'actas_fin' => 'Fin del plazo para registrar notas y cerrar actas.',
            ],
            'aca_aulas' => [
                '@' => 'Aulas físicas con su capacidad.',
                'codigo' => 'Código del aula; único y fijo tras crearse.',
                'nombre' => 'Nombre del aula.',
                'capacidad' => 'Número máximo de estudiantes.',
                'ubicacion' => 'Ubicación (pabellón, piso).',
                'activo' => 'Indica si el aula se puede asignar.',
            ],
            'aca_grupos' => [
                '@' => 'Oferta académica: grupo de un ciclo en un periodo, con modalidad, aula, cupos y vacantes.',
                'periodo_id' => 'Periodo en que se dicta.',
                'ciclo_id' => 'Ciclo que dicta.',
                'aula_id' => 'Aula física asignada; nulo en la modalidad virtual.',
                'codigo' => 'Código del grupo; único dentro del periodo.',
                'modalidad' => 'Modalidad de dictado.',
                'es_acelerado' => 'Indica si el grupo es de régimen acelerado.',
                'enlace_virtual' => 'Enlace de la clase virtual.',
                'cupo_maximo' => 'Cupo máximo de estudiantes.',
                'cupo_minimo' => 'Cupo mínimo para abrir el grupo.',
                'matriculados' => 'Matriculados activos; las vacantes son cupo_maximo menos este valor. Solo lo cambia el sistema con bloqueo de fila.',
                'estado' => 'Estado del grupo.',
            ],
            'aca_horarios' => [
                '@' => 'Días y horas de clase de cada grupo.',
                'grupo_id' => 'Grupo al que pertenece el horario.',
                'dia_semana' => 'Día de la semana (1 = lunes a 7 = domingo).',
                'hora_inicio' => 'Hora de inicio.',
                'hora_fin' => 'Hora de fin.',
            ],
            'aca_esquemas_evaluacion' => [
                '@' => 'Sistemas de evaluación configurables por idioma, nivel y vigencia.',
                'nombre' => 'Nombre del sistema de evaluación.',
                'idioma_id' => 'Idioma al que aplica; nulo si aplica a todos.',
                'nivel' => 'Nivel al que aplica; nulo si aplica a todos.',
                'nota_maxima' => 'Nota máxima de la escala.',
                'nota_minima_aprobatoria' => 'Nota mínima para aprobar.',
                'vigente_desde' => 'Inicio de vigencia.',
                'vigente_hasta' => 'Fin de vigencia; nulo si sigue vigente.',
            ],
            'aca_componentes_evaluacion' => [
                '@' => 'Componentes de un sistema de evaluación con su peso; los pesos suman 100.',
                'esquema_id' => 'Sistema de evaluación al que pertenece.',
                'codigo' => 'Código del componente.',
                'nombre' => 'Nombre del componente (por ejemplo, examen parcial).',
                'peso' => 'Peso porcentual del componente.',
                'orden' => 'Orden de presentación.',
            ],
            'mat_solicitudes_clasificacion' => [
                '@' => 'Solicitudes y resultados del examen de clasificación (placement test).',
                'persona_id' => 'Persona que solicita el examen.',
                'idioma_id' => 'Idioma del examen.',
                'estado' => 'Estado de la solicitud.',
                'vence_en' => 'Fecha en que vence la solicitud si no se evalúa.',
                'fecha_examen' => 'Fecha en que se rindió el examen.',
                'puntaje' => 'Puntaje obtenido (0 a 100).',
                'ciclo_asignado_id' => 'Ciclo en el que se ubica a la persona; pertenece al idioma de la solicitud.',
                'evaluado_por' => 'Cuenta del personal que registró el resultado.',
                'evaluado_en' => 'Fecha y hora del registro del resultado.',
                'observaciones' => 'Observaciones del evaluador.',
                'pago_id' => 'Pago validado que habilitó la solicitud; un pago habilita una sola solicitud.',
                'corregido_por' => 'Cuenta que corrigió el resultado.',
                'corregido_en' => 'Fecha y hora de la corrección.',
                'motivo_correccion' => 'Motivo de la corrección.',
            ],
            'mat_habilitaciones' => [
                '@' => 'Ciclo en el que cada persona puede matricularse en cada idioma. Sin fila, la persona empieza en el ciclo I.',
                'persona_id' => 'Persona habilitada.',
                'idioma_id' => 'Idioma de la habilitación.',
                'ciclo_id' => 'Ciclo habilitado; pertenece al idioma.',
                'origen' => 'Origen de la habilitación: clasificación, aprobación del ciclo anterior o ajuste administrativo.',
                'actualizado_por' => 'Cuenta que hizo el último ajuste.',
                'motivo' => 'Motivo, obligatorio en un ajuste administrativo.',
            ],
            'mat_matriculas' => [
                '@' => 'Matrícula de una persona en un grupo.',
                'persona_id' => 'Estudiante matriculado.',
                'grupo_id' => 'Grupo en el que se matricula.',
                'condicion' => 'Condición del estudiante en el ciclo: regular o repitente.',
                'estado' => 'Estado de la matrícula.',
                'matriculado_en' => 'Fecha y hora de la matrícula.',
                'retirado_en' => 'Fecha y hora del retiro; solo si la matrícula está retirada.',
                'pago_id' => 'Pago validado que habilitó la matrícula; un pago habilita una sola matrícula.',
            ],
            'mat_movimientos' => [
                '@' => 'Traslados y retiros de matrícula con su sustento. Inmutables.',
                'matricula_id' => 'Matrícula afectada.',
                'tipo' => 'Tipo de movimiento: traslado o retiro.',
                'grupo_origen_id' => 'Grupo de origen.',
                'grupo_destino_id' => 'Grupo de destino; solo en un traslado.',
                'motivo' => 'Sustento del movimiento.',
                'autorizado_por' => 'Cuenta que autorizó el movimiento.',
                'efectuado_en' => 'Fecha y hora del movimiento.',
            ],
            'mat_fichas' => [
                '@' => 'Código de la ficha de matrícula de cada persona en cada periodo, impreso con QR para verificarla.',
                'codigo' => 'Código único de la ficha.',
                'persona_id' => 'Estudiante de la ficha.',
                'periodo_id' => 'Periodo de la ficha.',
            ],
            'pag_fuentes_concepto' => [
                '@' => 'Fuentes normativas de los conceptos de pago (TUPA, TUSNE).',
                'codigo' => 'Código de la fuente.',
                'nombre' => 'Nombre de la fuente.',
                'activo' => 'Indica si se puede usar en conceptos nuevos.',
                'orden' => 'Orden de presentación.',
            ],
            'pag_conceptos' => [
                '@' => 'Conceptos de pago del TUPA/TUSNE (matrícula, pensión, examen, certificado, constancia, trámite).',
                'codigo' => 'Código del concepto en el TUPA/TUSNE.',
                'nombre' => 'Nombre del concepto.',
                'tipo' => 'Tipo de concepto, que define qué habilita el pago.',
                'activo' => 'Indica si se puede usar en pagos nuevos.',
                'fuente_id' => 'Fuente normativa del concepto.',
            ],
            'pag_tarifas' => [
                '@' => 'Monto de cada concepto según el vínculo de la persona, con vigencia; no se superponen.',
                'concepto_id' => 'Concepto al que se aplica.',
                'tipo_vinculo' => 'Vínculo de la persona al que se aplica.',
                'monto' => 'Monto en soles.',
                'vigente_desde' => 'Inicio de vigencia.',
                'vigente_hasta' => 'Fin de vigencia; nulo si sigue vigente.',
            ],
            'pag_medios_pago' => [
                '@' => 'Medios de pago aceptados (agencia bancaria, transferencia, caja).',
                'codigo' => 'Código del medio.',
                'nombre' => 'Nombre del medio.',
                'activo' => 'Indica si se acepta en pagos nuevos.',
                'orden' => 'Orden de presentación.',
            ],
            'pag_pagos' => [
                '@' => 'Pagos con su comprobante y su validación. Nunca se borran: un pago validado se anula con motivo.',
                'persona_id' => 'Persona que paga.',
                'concepto_id' => 'Concepto pagado.',
                'tarifa_id' => 'Tarifa vigente aplicada.',
                'monto' => 'Monto pagado en soles.',
                'numero_operacion' => 'Número de operación del voucher, en mayúsculas; único por medio entre pagos no rechazados.',
                'fecha_pago' => 'Fecha de la operación bancaria.',
                'recibo_path' => 'Ruta del comprobante digitalizado en el almacenamiento privado.',
                'estado' => 'Estado de validación del pago.',
                'registrado_por' => 'Cuenta que registró el pago.',
                'validado_por' => 'Cuenta de tesorería que validó o rechazó el pago.',
                'validado_en' => 'Fecha y hora de la validación o el rechazo.',
                'motivo_rechazo' => 'Motivo del rechazo.',
                'anulado_por' => 'Cuenta que anuló el pago.',
                'anulado_en' => 'Fecha y hora de la anulación.',
                'motivo_anulacion' => 'Motivo de la anulación.',
                'exige_boleta_original' => 'Indica si la validación exige verificar la boleta original.',
                'boleta_original_verificada_por' => 'Cuenta que verificó la boleta original.',
                'boleta_original_verificada_en' => 'Fecha y hora de la verificación de la boleta original.',
                'justificacion_monto' => 'Justificación cuando el monto difiere de la tarifa.',
                'recibo_sha256' => 'Huella SHA-256 del comprobante, para impedir que el mismo archivo sustente dos pagos.',
                'medio_pago_id' => 'Medio de pago usado.',
            ],
            'doc_condiciones_laborales' => [
                '@' => 'Catálogo de condiciones laborales del docente (nombrado, contratado, etc.).',
                'codigo' => 'Código de la condición.',
                'nombre' => 'Nombre de la condición.',
                'activo' => 'Indica si se puede asignar.',
                'orden' => 'Orden de presentación.',
            ],
            'doc_grados_academicos' => [
                '@' => 'Catálogo de grados académicos del docente.',
                'codigo' => 'Código del grado.',
                'nombre' => 'Nombre del grado.',
                'activo' => 'Indica si se puede asignar.',
                'orden' => 'Orden de presentación.',
            ],
            'doc_docentes' => [
                '@' => 'Legajo del docente: datos laborales, académicos y bancarios.',
                'persona_id' => 'Persona del docente.',
                'codigo' => 'Código del docente (DOC-NNNN), generado a partir del identificador.',
                'correo_institucional' => 'Correo institucional.',
                'ruc' => 'RUC de 11 dígitos.',
                'banco' => 'Banco de la cuenta de abono.',
                'cuenta_bancaria' => 'Cuenta bancaria de abono, cifrada.',
                'estado' => 'Estado del docente.',
                'condicion_laboral_id' => 'Condición laboral.',
                'grado_academico_id' => 'Grado académico.',
            ],
            'doc_docente_idiomas' => [
                '@' => 'Idiomas que puede dictar cada docente y hasta qué nivel.',
                'docente_id' => 'Docente.',
                'idioma_id' => 'Idioma que dicta.',
                'nivel_maximo' => 'Nivel máximo que puede dictar.',
            ],
            'doc_disponibilidades' => [
                '@' => 'Franjas horarias en que cada docente está disponible.',
                'docente_id' => 'Docente.',
                'dia_semana' => 'Día de la semana (1 = lunes a 7 = domingo).',
                'hora_inicio' => 'Hora de inicio.',
                'hora_fin' => 'Hora de fin.',
            ],
            'doc_cargas_academicas' => [
                '@' => 'Asignación de un docente a un grupo; un docente no tiene dos grupos a la misma hora.',
                'grupo_id' => 'Grupo asignado.',
                'docente_id' => 'Docente asignado.',
                'asignado_por' => 'Cuenta que hizo la asignación.',
            ],
            'doc_costos_hora' => [
                '@' => 'Costo por hora lectiva según la condición laboral o el grado académico, con vigencia.',
                'costo_hora' => 'Costo por hora en soles.',
                'vigente_desde' => 'Inicio de vigencia.',
                'vigente_hasta' => 'Fin de vigencia; nulo si sigue vigente.',
                'cerro_costo_id' => 'Costo anterior que este registro cerró.',
                'registrado_por' => 'Cuenta que registró el costo.',
                'condicion_laboral_id' => 'Condición laboral a la que aplica (excluyente con el grado).',
                'grado_academico_id' => 'Grado académico al que aplica (excluyente con la condición).',
            ],
            'doc_clases_dictadas' => [
                '@' => 'Clases dictadas por grupo: fecha, horas, tema, llamados de asistencia y confirmación del delegado.',
                'grupo_id' => 'Grupo de la clase.',
                'fecha' => 'Fecha de la clase.',
                'hora_inicio' => 'Hora de inicio.',
                'hora_fin' => 'Hora de fin.',
                'docente_id' => 'Docente que dictó la clase.',
                'registrado_por' => 'Cuenta que registró la clase.',
                'llamado_inicio_en' => 'Fecha y hora del llamado de asistencia al inicio.',
                'llamado_cierre_en' => 'Fecha y hora del llamado de asistencia al cierre.',
                'registro_posterior' => 'Indica si la clase se registró después de dictada, sin llamados en vivo.',
                'confirmacion' => 'Revisión del delegado del grupo sobre la clase.',
                'confirmada_por_matricula_id' => 'Matrícula del delegado que confirmó u observó la clase.',
                'confirmada_en' => 'Fecha y hora de la confirmación u observación.',
                'observacion' => 'Observación del delegado; obligatoria si la clase fue observada.',
                'tema' => 'Tema desarrollado en la clase.',
                'silabo_semana_id' => 'Semana del sílabo que corresponde a la clase.',
            ],
            'doc_asistencias' => [
                '@' => 'Asistencia de cada matrícula en cada clase dictada.',
                'clase_id' => 'Clase dictada.',
                'matricula_id' => 'Matrícula del estudiante.',
                'estado' => 'Asistencia en el llamado de inicio.',
                'estado_cierre' => 'Asistencia en el llamado de cierre; solo para quienes estuvieron presentes al inicio.',
            ],
            'doc_delegados' => [
                '@' => 'Delegado de cada grupo, que confirma las clases dictadas.',
                'grupo_id' => 'Grupo.',
                'matricula_id' => 'Matrícula del estudiante delegado.',
                'designado_por' => 'Cuenta que designó al delegado.',
            ],
            'doc_notas_parciales' => [
                '@' => 'Notas parciales (pre-acta) de cada matrícula en cada componente de evaluación.',
                'grupo_id' => 'Grupo.',
                'matricula_id' => 'Matrícula del estudiante.',
                'componente_id' => 'Componente de evaluación calificado.',
                'nota' => 'Nota obtenida.',
                'registrado_por' => 'Cuenta que registró la nota.',
            ],
            'doc_silabos' => [
                '@' => 'Sílabo de cada grupo.',
                'grupo_id' => 'Grupo del sílabo.',
                'estado' => 'Estado del sílabo: borrador o publicado.',
                'sumilla' => 'Sumilla del curso.',
                'competencias' => 'Competencias.',
                'capacidades' => 'Capacidades.',
                'metodologia' => 'Metodología.',
                'bibliografia' => 'Bibliografía.',
                'publicado_en' => 'Fecha y hora de publicación.',
                'publicado_por' => 'Cuenta que publicó el sílabo.',
            ],
            'doc_silabo_semanas' => [
                '@' => 'Programación semanal del sílabo.',
                'silabo_id' => 'Sílabo.',
                'semana' => 'Número de semana.',
                'unidad' => 'Unidad didáctica.',
                'saber' => 'Contenidos conceptuales (saber).',
                'saber_hacer' => 'Contenidos procedimentales (saber hacer).',
                'saber_ser' => 'Contenidos actitudinales (saber ser).',
                'actividades' => 'Actividades.',
                'recursos' => 'Recursos.',
            ],
            'doc_silabo_evaluaciones' => [
                '@' => 'Evaluaciones programadas en el sílabo.',
                'silabo_id' => 'Sílabo.',
                'componente_codigo' => 'Código del componente de evaluación.',
                'indicadores' => 'Indicadores de logro.',
                'instrumento' => 'Instrumento de evaluación.',
                'semana' => 'Semana en que se evalúa.',
            ],
            'cer_actas' => [
                '@' => 'Actas finales de notas por grupo. Guardan una copia de los datos del grupo y se bloquean al cerrarse.',
                'grupo_id' => 'Grupo del acta.',
                'periodo_id' => 'Periodo del acta.',
                'estado' => 'Estado del acta: abierta o cerrada.',
                'grupo_codigo' => 'Código del grupo al emitir el acta.',
                'idioma' => 'Idioma al emitir el acta.',
                'nivel' => 'Nivel al emitir el acta.',
                'ciclo_numero' => 'Número de ciclo al emitir el acta.',
                'ciclo_nombre' => 'Nombre del ciclo al emitir el acta.',
                'horas_lectivas' => 'Horas lectivas del ciclo.',
                'periodo' => 'Nombre del periodo al emitir el acta.',
                'docente_id' => 'Docente responsable.',
                'docente_nombre' => 'Nombre del docente al emitir el acta.',
                'esquema_id' => 'Sistema de evaluación aplicado.',
                'esquema_nombre' => 'Nombre del sistema de evaluación.',
                'nota_maxima' => 'Nota máxima de la escala.',
                'nota_minima_aprobatoria' => 'Nota mínima para aprobar.',
                'componentes' => 'Componentes y pesos aplicados (JSON).',
                'validada_por' => 'Cuenta que validó y cerró el acta.',
                'validada_en' => 'Fecha y hora del cierre.',
                'firmas' => 'Firmantes del acta al cerrarse (JSON).',
                'denominacion' => 'Denominación oficial del año al cerrarse.',
            ],
            'cer_notas_finales' => [
                '@' => 'Nota final de cada estudiante en un acta.',
                'acta_id' => 'Acta.',
                'matricula_id' => 'Matrícula del estudiante.',
                'persona_id' => 'Estudiante.',
                'estudiante_nombre' => 'Nombre del estudiante al emitir el acta.',
                'estudiante_documento' => 'Documento del estudiante al emitir el acta.',
                'notas' => 'Notas por componente (JSON).',
                'promedio' => 'Promedio ponderado; nulo si no se presentó.',
                'estado_final' => 'Resultado: aprobado, desaprobado o NSP (no se presentó).',
            ],
            'cer_rectificaciones' => [
                '@' => 'Rectificaciones de actas cerradas, autorizadas por el administrador.',
                'acta_id' => 'Acta rectificada.',
                'motivo' => 'Motivo de la rectificación.',
                'autorizada_por' => 'Cuenta que autorizó la rectificación.',
                'autorizada_en' => 'Fecha y hora de la autorización.',
                'cerrada_en' => 'Fecha y hora en que se volvió a cerrar el acta.',
                'notas_previas' => 'Notas antes de la rectificación (JSON).',
            ],
            'cer_firmantes' => [
                '@' => 'Autoridades que firman actas, constancias y certificados, con vigencia.',
                'cargo' => 'Cargo del firmante.',
                'nombre' => 'Nombre del firmante.',
                'firma_certificados' => 'Indica si firma certificados.',
                'firma_constancias' => 'Indica si firma constancias.',
                'firma_actas' => 'Indica si firma actas.',
                'orden' => 'Orden de las firmas en el documento.',
                'vigente_desde' => 'Inicio de vigencia.',
                'vigente_hasta' => 'Fin de vigencia; nulo si sigue vigente.',
            ],
            'cer_denominaciones' => [
                '@' => 'Denominación oficial de cada año, impresa en los documentos.',
                'anio' => 'Año.',
                'texto' => 'Texto de la denominación.',
            ],
            'cer_correlativos' => [
                '@' => 'Último número emitido por tipo de documento y año, para la numeración correlativa sin saltos.',
                'tipo' => 'Tipo de documento.',
                'anio' => 'Año.',
                'ultimo' => 'Último número emitido.',
            ],
            'cer_documentos' => [
                '@' => 'Constancias y certificados emitidos, verificables por código y QR. Se anulan, nunca se borran.',
                'codigo' => 'Código único de verificación impreso junto al QR.',
                'tipo' => 'Tipo de documento.',
                'persona_id' => 'Estudiante.',
                'matricula_id' => 'Matrícula que sustenta una constancia de matrícula.',
                'estudiante_nombre' => 'Nombre del estudiante al emitir.',
                'estudiante_documento' => 'Documento del estudiante al emitir.',
                'idioma' => 'Idioma.',
                'nivel' => 'Nivel certificado.',
                'horas_lectivas' => 'Horas lectivas acumuladas del nivel.',
                'nota_final' => 'Nota final del nivel.',
                'detalle' => 'Contenido del documento al emitir (JSON).',
                'emitido_por' => 'Cuenta que emitió el documento.',
                'emitido_en' => 'Fecha y hora de emisión.',
                'anulado_por' => 'Cuenta que anuló el documento.',
                'anulado_en' => 'Fecha y hora de la anulación.',
                'motivo_anulacion' => 'Motivo de la anulación.',
                'anio_numero' => 'Año de la numeración correlativa.',
                'numero' => 'Número correlativo dentro del año.',
                'revisar_desde' => 'Fecha y hora desde la que el documento debe revisarse porque se rectificó el acta de la que tomó sus notas.',
                'motivo_revision' => 'Motivo de la revisión.',
            ],
            'tra_tipos_tramite' => [
                '@' => 'Tipos de trámite configurables (constancia, certificado, traslado, retiro).',
                'codigo' => 'Código del tipo de trámite.',
                'nombre' => 'Nombre del tipo de trámite.',
                'requisitos' => 'Requisitos que se muestran al solicitante.',
                'tipo_concepto' => 'Tipo de concepto de pago que exige.',
                'accion_final' => 'Acción que ejecuta el sistema al finalizar el trámite.',
                'activo' => 'Indica si se puede solicitar.',
            ],
            'tra_etapas' => [
                '@' => 'Etapas de cada tipo de trámite con su porcentaje de avance y la condición para pasar.',
                'tipo_tramite_id' => 'Tipo de trámite.',
                'orden' => 'Orden de la etapa.',
                'nombre' => 'Nombre de la etapa.',
                'avance' => 'Porcentaje de avance al llegar a la etapa (0 a 100).',
                'condicion' => 'Condición para llegar a la etapa: solicitud, pago validado o acción del personal.',
            ],
            'tra_tramites' => [
                '@' => 'Trámites solicitados, con su estado, etapa actual y una copia de las etapas al solicitarse.',
                'codigo' => 'Código único de seguimiento.',
                'persona_id' => 'Solicitante.',
                'tipo_tramite_id' => 'Tipo de trámite.',
                'tipo_nombre' => 'Nombre del tipo al solicitarse.',
                'accion_final' => 'Acción final al solicitarse.',
                'tipo_concepto' => 'Tipo de concepto de pago exigido.',
                'etapas' => 'Etapas del trámite al solicitarse (JSON).',
                'etapa_actual' => 'Índice de la etapa actual.',
                'estado' => 'Estado del trámite.',
                'datos' => 'Datos ingresados por el solicitante (JSON).',
                'pago_id' => 'Pago que desbloquea el trámite.',
                'observacion' => 'Observación del personal; obligatoria si el trámite está observado.',
                'documento_id' => 'Documento emitido al finalizar.',
                'finalizado_en' => 'Fecha y hora de finalización.',
                'cerrado_en' => 'Fecha y hora de cierre sin completarse.',
                'motivo_cierre' => 'Motivo del cierre.',
            ],
            'tra_movimientos' => [
                '@' => 'Historial inmutable de cambios de estado y etapa de cada trámite.',
                'tramite_id' => 'Trámite.',
                'estado' => 'Estado al que pasó.',
                'etapa' => 'Índice de la etapa a la que pasó.',
                'etapa_nombre' => 'Nombre de la etapa.',
                'comentario' => 'Comentario del cambio.',
                'actor_id' => 'Cuenta que hizo el cambio; nulo si fue el sistema.',
            ],
            'tra_notificaciones' => [
                '@' => 'Registro de correos enviados por cada cambio de estado de un trámite.',
                'tramite_id' => 'Trámite.',
                'movimiento_id' => 'Cambio de estado que originó el correo.',
                'destinatario' => 'Correo del destinatario.',
                'asunto' => 'Asunto del correo.',
                'estado' => 'Estado de entrega.',
                'intentos' => 'Intentos de envío.',
                'enviado_en' => 'Fecha y hora de envío.',
                'error' => 'Último error de envío.',
            ],
        ];
    }
};
