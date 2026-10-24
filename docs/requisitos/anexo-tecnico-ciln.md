> Transcripción en Markdown de `anexo-tecnico-ciln.docx` (fuente oficial). Ante cualquier diferencia, prevalece el .docx.

# Anexo Técnico
**REQUERIMIENTOS DEL SISTEMA DE GESTIÓN ACADÉMICA Y ADMINISTRATIVA DEL CILN**

## I. JUSTIFICACIÓN
Conforme al Informe Técnico N° 023-2026-FMAJ de la DSTI, el sistema actual del CILN se encuentra en desfase tecnológico (PHP 5.6, sin soporte desde 2018), con vulnerabilidades críticas de seguridad y deficiencias funcionales y arquitectónicas, por lo que se recomienda su rediseño y desarrollo integral. El presente Anexo define los requisitos técnicos y funcionales del nuevo sistema, que debe integrar tecnologías actuales, eliminar los procesos manuales propensos a errores y permitir una gestión sin papeles, con trazabilidad auditable.

## II. REQUISITOS TÉCNICOS GENERALES  (ARQUITECTURA, SEGURIDAD Y DISEÑO)
Los siguientes requisitos son de cumplimiento obligatorio y se alinean con las recomendaciones del Informe Técnico N° 023-2026-FMAJ de la DSTI:
### 1. Arquitectura y Tecnología
- Lenguaje y entorno: Uso de PHP 8.5 o superior (o lenguaje backend equivalente en rendimiento y seguridad). Es obligatorio emplear versiones con soporte oficial activo (LTS) y parches de seguridad vigentes.
- Framework y arquitectura: Implementación mediante un framework backend consolidado (referencia: Laravel, Symfony o equivalente tecnológico) estructurado obligatoriamente bajo el patrón MVC o multicapa análoga.
- Exposición de servicios mediante API REST y diseño de servicios desacoplados para futuras implementaciones.
- Base de datos relacional optimizada (modelado normalizado e índices adecuados).
- La solución debe ser compatible con el entorno de despliegue y los estándares de la DSTI; cualquier tecnología equivalente propuesta será coordinada y aprobada por dicha dirección.
### 2. Seguridad de la Información (estándares OWASP)
- Cumplimiento del OWASP Top 10.
- Validación robusta de entradas y protección frente a inyección SQL, XSS y CSRF.
- Gestión segura de sesiones y cifrado de datos sensibles.
- Tratamiento de datos personales conforme a la Ley N° 29733.
- Pistas de auditoría (Log Triggers) inmutables para operaciones críticas (cambio de notas, validación de pagos, modificación de actas), registrando fecha, hora, IP, ID de usuario y detalle de la operación.
### 3. Experiencia de Usuario y Diseño
- Interfaz responsive, intuitiva, accesible y dinámica, adaptable a dispositivos móviles y centrada en la experiencia de usuario.
### 4. Arquitectura Escalable
- Plataforma preparada para integraciones futuras, crecimiento institucional, interoperabilidad, alta disponibilidad y escalabilidad tecnológica.

## III. PROPUESTA DE SOLUCIÓN Y MÓDULOS REQUERIDOS

## MÓDULO 1: GESTIÓN DE REGISTRO DE ESTUDIANTES, EXÁMENES DE CLASIFICACIÓN Y MATRÍCULA
Objetivo: Automatizar el flujo de ingreso de nuevos estudiantes y regulares, gestionando desde la evaluación de su nivel de idioma hasta su inscripción formal en un grupo y horario específico, con validación de prerrequisitos.
### 1. Registro y Examen de Clasificación (Placement Test)
**Funcionalidad:**
- Registro de postulantes nuevos que desean ingresar a un nivel superior al básico.
- Gestión de solicitudes para rendir el examen de clasificación.
- Ingreso de resultados por la coordinación académica y asignación automática del nivel en el perfil del usuario.
**Campos Requeridos:**
- Datos del Postulante: Tipo y Número de Documento (DNI/CE/Pasaporte), Apellidos, Nombres, Fecha de Nacimiento, Correo Personal, Celular.
- Datos de Vínculo: Tipo de usuario (Estudiante UNASAM, Docente/Administrativo UNASAM, Público General). Crítico para tarifas diferenciadas.
- Datos del Examen: Idioma, Fecha, Resultado (Puntaje), Nivel Asignado (Ej. Intermedio II).
- Estado de Solicitud: Pendiente de Evaluación, Evaluado, Expirado.
### 2. Configuración de Oferta Académica (Apertura de Ciclos)
**Funcionalidad:**
- Crear y ofertar los cursos del mes/bimestre, definiendo cupos, horarios, idiomas y modalidades (Presencial, Virtual, Semipresencial; Acelerado).
**Campos Requeridos:**
- Detalle del Ciclo: Año, Mes/Periodo, Fecha de Inicio y Fin.
- Detalle del Grupo: Código, Idioma, Nivel (Básico, Intermedio, Avanzado), Ciclo (I a XII).
- Capacidad: Cupo Máximo, Cupo Mínimo de apertura, Vacantes Disponibles (en tiempo real).
- Asignación: Docente, Aula (física o enlace virtual), Horario (días y rango de horas).
### 3. Matrícula Autogestionada y Validación de Requisitos
**Funcionalidad:**
- Interfaz para que el estudiante seleccione su grupo y se matricule de forma autónoma.
- Bloqueos automáticos: el sistema impedirá la matrícula si el estudiante (a) no aprobó el ciclo anterior o no tiene el nivel requerido; (b) no cuenta con la validación de pago registrada en el Módulo de Gestión de Pagos; o (c) intenta matricularse en un grupo sin vacantes.
- Generación automática de la Ficha de Matrícula en PDF.
**Campos Requeridos:**
- Datos de Inscripción: ID Estudiante, Grupo, Fecha y Hora de Matrícula.
- Validación de Pago: Estado (Verificado/Pendiente) según el Módulo de Gestión de Pagos.
- Validación Académica: Condición (Regular, Repitente).
- Documentos Generados: Ficha de Matrícula (PDF), Constancia de Inscripción.
### 4. Gestión de Traslados y Retiros (Administrativo)
**Funcionalidad:**
- Herramienta administrativa para cambiar a un alumno de grupo/horario por motivos excepcionales (cierre de grupo, cruce de horarios).
- Registro de retiros oficiales del ciclo para evitar penalidades académicas.
**Campos Requeridos:**
- Datos de Movimiento: Grupo Origen, Grupo Destino, Fecha.
- Sustento: Motivo del traslado o retiro, Usuario administrativo que autoriza.

## MÓDULO 2: GESTIÓN Y VALIDACIÓN DE PAGOS
Objetivo: Registrar, validar y reportar de forma ordenada y auditable los pagos por derechos académicos de los usuarios del CILN, de modo que el sistema genere los reportes y expedientes que se presentan a las áreas correspondientes. La validación se realiza dentro del sistema, sin requerir integración automática con sistemas externos.
### 1. Registro y Validación de Pagos
**Funcionalidad:**
- Registro de pagos por derechos académicos (matrícula, pensión, examen de clasificación, certificados).
- Validación del pago por el administrador o cajero del CILN, con carga del recibo en formato digital (JPG/PNG o PDF) como evidencia.
- Desbloqueo automático de los módulos condicionados (matrícula, constancias, solicitud de exámenes) una vez validado el pago.
- Registro de auditoría que indica qué usuario validó cada pago y en qué fecha.
**Campos Requeridos (Tabla de Pagos):**
- Identificador de Pago: Número de Recibo / Código de Operación.
- Datos del Pagador: Tipo y Número de Documento (DNI/CE/Pasaporte).
- Detalle Financiero: Monto Total, Fecha de Pago, Medio de Pago (caja presencial, Págalo.pe, etc.).
- Concepto TUPA/TUSNE, según corresponda: Código del concepto (Matrícula, Pensión, Examen de Clasificación, Certificados).
- Evidencia y Trazabilidad: Recibo escaneado, Estado (Pendiente, Validado, Rechazado), Usuario que validó, Fecha de Validación.
### 2. Reportes y Expedientes de Recaudación
**Funcionalidad:**
- Generación de reportes consolidados de pagos validados por periodo, idioma, nivel y concepto TUPA/TUSNE, según corresponda.
- Exportación en PDF y/o hoja de cálculo (Excel/CSV) para su impresión y presentación como expediente ante las áreas correspondientes (DGF / Tesorería).
**Campos Requeridos (Reporte):**
- Periodo, Concepto, Monto total recaudado, Número de operaciones, Detalle por estudiante.

## MÓDULO 3: GESTIÓN DOCENTE Y REPORTES DE CARGA ACADÉMICA
Objetivo: Administrar el padrón y la carga académica de los docentes del CILN y generar, sobre la base de la información de horas dictadas y tarifas, los reportes y expedientes por docente que se presentan a las áreas correspondientes. El sistema no procesa la planilla de pago ni las retenciones de ley, que son competencia de la Dirección de Gestión Financiera (DGF).
### 1. Registro y Legajo Docente
**Funcionalidad:**
- Mantenimiento del padrón de docentes (especialidad/idiomas y disponibilidad horaria).
- Gestión del estado del docente (Activo, Inactivo, De Licencia).
**Campos Requeridos (Perfil Docente):**
- Datos Personales: ID Docente, Nombres Completos, DNI/CE, Correo Institucional y Personal, Celular.
- Datos para el Expediente: RUC, Número de Cuenta Bancaria, Banco, Condición Laboral (Nombrado, Contratado, Tercero).
- Perfil Académico: Idiomas habilitados para dictar, Nivel máximo autorizado.
### 2. Asignación de Carga Académica
**Funcionalidad:**
- Vincular a un docente con los grupos/ciclos aperturados en el Módulo 1.
- Cálculo automático del total de horas asignadas en el periodo.
- Control de cruces de horarios para evitar asignaciones superpuestas.
- Llamado de asistencia y registro de calificaciones (pre-actas) durante las clases, según el cronograma académico.
**Campos Requeridos:**
- Detalle del Grupo: Código, Nivel, Idioma.
- Carga Horaria: Número de semanas, Fechas de inicio y fin, Días de dictado, Horas totales por grupo.
### 3. Reporte / Expediente de Horas por Docente
**Funcionalidad:**
- Consolidación, por docente y periodo, de las horas dictadas multiplicadas por la tarifa o costo por hora (dato provisto según la categoría del docente), obteniendo el monto bruto referencial.
- Generación del reporte/expediente por docente en PDF y/o hoja de cálculo (Excel/CSV), para su presentación ante la DGF / Tesorería.
- El sistema no calcula retenciones de ley ni procesa el pago: entrega únicamente la información consolidada de horas y costos.
**Campos Requeridos (Reporte por Docente):**
- Periodo: Mes y año.
- Detalle: Nombres del docente, Idioma/Nivel dictado, Total de horas, Costo por hora, Monto Bruto Referencial.

## MÓDULO 4: EVALUACIÓN Y CERTIFICACIÓN DIGITAL
Objetivo: Registrar de forma segura las calificaciones, automatizar la emisión de actas finales y emitir constancias y certificados con Código QR de verificación.
### 1. Ingreso y Validación de Notas (Actas Finales)
**Funcionalidad:**
- Interfaz exclusiva para el docente asignado, habilitada solo durante las fechas del calendario académico.
- Cálculo automático de promedios según el sistema de evaluación del CILN (Examen Parcial, Examen Final, Participación).
- Cierre y bloqueo automático de actas validadas; toda modificación posterior requiere autorización del Administrador y queda en el log de auditoría.
**Campos Requeridos (Actas Finales):**
- Datos del Curso: Idioma, Nivel, Ciclo, Periodo Académico.
- Datos del Docente: ID, Nombres Completos.
- Calificaciones: ID Estudiante, Notas parciales, Promedio Final, Estado Final (Aprobado/Desaprobado/NSP).
### 2. Generación de Constancias y Certificados con Código QR
**Funcionalidad:**
- Emisión automatizada (masiva o individual) de Constancias de Matrícula, Constancias de Notas y Certificados de Culminación de Nivel.
- Documentos en PDF estandarizado con diseño institucional.
- Validación digital: Código QR único por documento que, al escanearse, redirige a un portal oficial del CILN/UNASAM que valida la autenticidad mostrando datos del alumno y nivel aprobado.
**Campos Requeridos (Certificación):**
- Datos del Documento: Código Único, Fecha de Emisión, Código QR.
- Datos de Acreditación: Nombres, Idioma, Nivel alcanzado, Total de horas lectivas, Nota final.
Nota: la solicitud, el seguimiento y la notificación de estos documentos se gestionan mediante los pipelines de trámites del Módulo 6.

## MÓDULO 5: TABLERO DE CONTROL GERENCIAL (BUSINESS INTELLIGENCE SINTÉTICO)
Objetivo: Centralizar la información de los módulos académicos y financieros en un único tablero de control gerencial con indicadores descriptivos en tiempo real. El módulo se limita a la consolidación y visualización descriptiva de datos; no contempla analítica predictiva, machine learning ni proyección por series de tiempo.
### 1. Indicadores Financieros (KPIs)
- Ingresos recaudados por periodo, idioma y nivel.
- Tasa de morosidad o pagos pendientes de validación.
- Rentabilidad básica por grupo (ingresos por matrículas frente a los costos por carga docente, según horas y tarifa).
### 2. Indicadores Académicos (KPIs)
- Número de matrículas y crecimiento mes a mes / año a año.
- Tasa de deserción por nivel y por idioma.
- Distribución de la demanda horaria (horarios más solicitados) para optimizar la asignación de aulas.
### 3. Visualización
- Tablero único para el personal directivo, con gráficos y tablas de resumen, filtros por periodo, idioma y nivel, y exportación (PDF/Excel).

## MÓDULO 6: PORTAL PÚBLICO Y TRAZABILIDAD DE TRÁMITES (PIPELINES)
Objetivo: Desplegar una interfaz pública en el dominio oficial (https://cid.unasam.edu.pe/) como punto de ingreso de estudiantes y docentes, y gestionar el seguimiento de los trámites del CILN mediante flujos guiados (pipelines), sin asistente conversacional.
### 1. Portal Público y Acceso
**Funcionalidad:**
- Página pública con información institucional del CILN (oferta, requisitos, fechas, costos según TUPA/TUSNE).
- Inicio de sesión diferenciado para estudiantes y docentes, como punto único de acceso a sus módulos.
- Recuperación de credenciales mediante envío de token al correo institucional/personal registrado.
### 2. Pipelines de Trámites (Flujos Guiados Deterministas)
**Funcionalidad:**
- Un flujo configurable por cada trámite (constancia, certificado de nivel, traslado o retiro), con etapas y estados definidos.
- Transiciones condicionadas por reglas: el flujo se bloquea hasta que el Módulo de Gestión de Pagos confirme la validación del pago de la tasa según el TUPA/TUSNE.
- Barra de progreso visible para el usuario (Ej. 0% Solicitud recibida → 33% Pago validado → 66% En firma de Dirección → 100% Listo para recojo/descarga).
- Notificación automática por correo electrónico ante cada cambio de estado del trámite.
- Panel autogestionable para que el administrador configure etapas, estados y condiciones de cada trámite sin programación.
**Campos Requeridos (Tracking de Trámite):**
- ID de Trámite (código único de seguimiento).
- Estados del Flujo: Pendiente de Pago, En Proceso, Observado, Finalizado.
- Log de Notificaciones: registro de correos enviados (Fecha, Hora, Estado de entrega).

## IV. CONDICIONES DE SEGURIDAD, PROPIEDAD INTELECTUAL Y CONFIDENCIALIDAD
### 1. Propiedad Intelectual y Código Fuente
- Todos los derechos patrimoniales y de propiedad intelectual del software, el diseño de la base de datos, el código fuente, los manuales y los scripts de implementación serán cedidos de forma total, exclusiva y a perpetuidad a la UNASAM.
- La UNASAM será la única titular con derecho a modificación, escalabilidad, redistribución o integración futura sin autorización ni pagos adicionales al proveedor. A la finalización del servicio, el proveedor entregará los repositorios completos y documentados.
### 2. Seguridad de la Información y Pistas de Auditoría
- El sistema garantizará la seguridad de la información conforme a la Ley N° 29733 y a los estándares OWASP señalados en el numeral II.
- Se implementarán pistas de auditoría (Log Triggers) inmutables para las operaciones críticas, registrando fecha, hora, IP, ID de usuario y detalle de la modificación.
### 3. Confidencialidad
- Toda la información académica, financiera y personal a la que acceda el proveedor es estrictamente confidencial; queda prohibido revelarla, copiarla o usarla para fines ajenos al contrato, bajo responsabilidad civil y penal.
### 4. Mantenimiento Preventivo y Soporte Técnico
- El proveedor otorgará garantía de funcionamiento y soporte técnico (resolución de bugs, incidencias y asistencia en el uso) por un periodo mínimo de doce (12) meses, contados desde la conformidad final del sistema.

Lugar y fecha: ……………………………………………..

…………………………………………..
NOMBRE, FIRMA Y SELLO – ÁREA USUARIA
