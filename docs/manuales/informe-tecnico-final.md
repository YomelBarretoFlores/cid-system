<!--
titulo: Informe Técnico Final – Sistema de Gestión Académica y Administrativa del CILN
version: 1.1
fecha: noviembre de 2026
autor: ELECTRODATA INGENIERIA & SISTEMAS S.A.C.
contratista: ELECTRODATA INGENIERIA & SISTEMAS S.A.C. (RUC 20571285852)
entidad: Universidad Nacional Santiago Antúnez de Mayolo – Centro de Idiomas y Lenguas Nativas (CILN)
-->

# HISTORIAL DE VERSIONES

*Tabla 1. Historial de versiones del documento*

| Versión | Fecha | Descripción | Autor |
|---|---|---|---|
| 1.0 | Noviembre de 2026 | Informe técnico final del servicio, con la verificación del cumplimiento de la Orden de Servicio, los Términos de Referencia y su Anexo Técnico | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. |
| 1.1 | 19/11/2026 | Actualización a la versión v1.1.1 del sistema; incorporación de las evidencias de capacitación y de levantamiento de requerimientos, de las declaraciones del contratista y de las cartas de cesión de derechos y de garantía técnica | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. |

# USO DE LA INFORMACIÓN

El presente documento es de uso interno y confidencial de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM). Está dirigido al Centro de Idiomas y Lenguas Nativas (CILN), como área usuaria, y a la Dirección de Sistemas y Tecnologías de la Información (DSTI), como responsable de la operación técnica del sistema.

Conforme al numeral 6.5 de los Términos de Referencia, toda información propia de la Entidad es confidencial; el contratista mantiene reserva absoluta sobre la información académica, financiera y personal a la que tuvo acceso durante la prestación, conforme a la Ley N.° 29733.

Conforme al numeral IV.1 del Anexo Técnico y al numeral 6.1 de los Términos de Referencia, todos los derechos patrimoniales y de propiedad intelectual del software, el diseño de la base de datos, el código fuente, los manuales, los scripts de implementación y la presente documentación se ceden de forma total, exclusiva y a perpetuidad a la UNASAM, que es la única titular con derecho a su modificación, escalabilidad, redistribución o integración futura, sin requerir autorización ni pagos adicionales al contratista.

# 1. INTRODUCCIÓN

## 1.1. Antecedentes

El Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM operaba un sistema web de gestión académica (`https://cid.unasam.edu.pe/`) desarrollado hace más de diez años sobre **PHP 5.6**, versión sin soporte oficial ni parches de seguridad desde 2018.

Mediante el **Oficio N.° 166-2026-UNASAM-DSTI/D** y el **Informe Técnico N.° 023-2026-FMAJ**, ambos del 22 de mayo de 2026, la DSTI informó al CILN sobre el estado situacional del sistema y concluyó que presenta limitaciones técnicas, funcionales y de seguridad que comprometen su continuidad: tecnología obsoleta, vulnerabilidades críticas (inyección SQL, ejecución remota de código, XSS, gestión insegura de sesiones, exposición de información sensible), malas prácticas de desarrollo (código monolítico y acoplado, sin arquitectura modular ni estándares OWASP) y una interfaz no adaptable a dispositivos móviles. En consecuencia, recomendó el rediseño y desarrollo integral de un sistema nuevo bajo tecnologías modernas y seguras.

En atención a dicha recomendación, el CILN aprobó los **Términos de Referencia (TdR)** del «Servicio de Elaboración e Implementación de Software de Sistema de Calidad» y su **Anexo Técnico**, suscritos en Huaraz el **7 de setiembre de 2026**. Mediante la **Orden de Servicio N.° 0003091**, emitida el **20 de octubre de 2026** y notificada al contratista el **23 de octubre de 2026** (Expediente SIAF N.° 0000005315), la UNASAM contrató el servicio con **ELECTRODATA INGENIERIA & SISTEMAS S.A.C.** (RUC 20571285852), por un monto de **S/ 39,500.00** (incluido el IGV) y un plazo de prestación de **treinta (30) días calendario**, contados a partir del día siguiente de notificada la orden, es decir, del 24 de octubre al 22 de noviembre de 2026.

## 1.2. Objetivo general

Conforme al numeral 3.2 de los TdR, el objetivo general es desarrollar e implementar un software que automatice, estandarice y optimice el registro de estudiantes, la clasificación, la matrícula, la gestión y validación de pagos, la gestión docente y la certificación del CILN, sobre una arquitectura moderna y segura.

El presente informe documenta la solución construida, sus fundamentos técnicos y la verificación, requisito por requisito, del cumplimiento de la Orden de Servicio, los TdR y su Anexo Técnico, para la conformidad del área usuaria y la operación y el mantenimiento a cargo de la DSTI.

## 1.3. Objetivos específicos

Los objetivos específicos se corresponden con los seis módulos del Anexo Técnico:

1. **Módulo 1.** Automatizar el ingreso de postulantes y estudiantes regulares, desde el examen de clasificación hasta la matrícula autogestionada en un grupo y horario, con validación de prerrequisitos, pago y vacantes.
2. **Módulo 2.** Registrar, validar y reportar de forma ordenada y auditable los pagos por derechos académicos, con desbloqueo automático de los servicios condicionados y reportes de recaudación para la DGF y Tesorería.
3. **Módulo 3.** Administrar el padrón y la carga académica de los docentes, controlar los cruces de horario, registrar la asistencia y las notas parciales, y generar el expediente de horas por docente.
4. **Módulo 4.** Registrar de forma segura las calificaciones, automatizar las actas finales con cierre y bloqueo, y emitir constancias y certificados con código QR de verificación.
5. **Módulo 5.** Centralizar los indicadores financieros y académicos en un tablero de control gerencial con filtros y exportación.
6. **Módulo 6.** Ofrecer un portal público institucional con acceso diferenciado y gestionar los trámites del CILN mediante flujos configurables con seguimiento y notificación por correo.

Además, conforme a los objetivos específicos del numeral 3.2 de los TdR, el sistema debía soportar alta concurrencia en los periodos de matrícula, asegurar la trazabilidad de la recaudación y superar el desfase tecnológico y las vulnerabilidades señaladas por la DSTI.

## 1.4. Alcance

El servicio comprende el diseño, desarrollo, pruebas, despliegue y puesta en producción del Sistema de Gestión Académica y Administrativa del CILN, con los seis módulos funcionales del Anexo Técnico, los requisitos técnicos generales de su sección II y las condiciones de seguridad, propiedad intelectual y confidencialidad de su sección IV.

*Tabla 2. Usuarios y componentes del sistema*

| Usuario | Componente | Dirección |
|---|---|---|
| Público en general | Portal público y verificación de documentos | `/`, `/verificar` |
| Estudiantes y postulantes del CILN | Portal del estudiante | `/estudiante` |
| Docentes del CILN | Panel docente | `/docente` |
| Personal del CILN (dirección, coordinación, caja, administración) | Panel de administración y tablero gerencial | `/admin` |
| Sistemas de la UNASAM que autorice la DSTI (integraciones futuras) | Servicios REST | `/api/v1` |

Quedan fuera del alcance, conforme a los TdR y al Anexo Técnico: el procesamiento de la planilla y de las retenciones de ley de los docentes (competencia de la DGF), la integración automática con sistemas externos de pago, la analítica predictiva y el asistente conversacional. La migración de datos del sistema anterior se realiza solo **«de requerirse»**, en coordinación con la DSTI y limitada a la información que dicha dirección proporcione en un formato utilizable (numeral 6.1 de los TdR).

## 1.5. Base contractual y normativa

*Tabla 3. Base contractual y normativa del servicio*

| Documento | Aplicación en el servicio |
|---|---|
| Ley N.° 32069, Ley General de Contrataciones Públicas, y su Reglamento (D.S. N.° 009-2025-EF) | Marco de la contratación, penalidades (art. 120) y vicios ocultos |
| Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento | Tratamiento de datos personales de estudiantes, docentes y personal |
| Términos de Referencia del servicio (7 de setiembre de 2026) | Actividades, entregable, plazo, obligaciones, confidencialidad y garantía |
| Anexo Técnico de los TdR | Requisitos técnicos generales, seis módulos funcionales y condiciones de seguridad y propiedad intelectual |
| Orden de Servicio N.° 0003091 (emitida el 20/10/2026 y notificada el 23/10/2026) | Contratación de ELECTRODATA INGENIERIA & SISTEMAS S.A.C., monto y plazo |
| Oficio N.° 166-2026-UNASAM-DSTI/D e Informe Técnico N.° 023-2026-FMAJ | Diagnóstico del sistema anterior (problemas 1.1 a 1.5) y recomendaciones técnicas (3.1) |

Se aplicaron además, como referencias técnicas, el OWASP Top 10 (2021) para la seguridad de aplicaciones web y la NTP-ISO/IEC 12207:2016 para los procesos del ciclo de vida del software.

## 1.6. Glosario de términos

*Tabla 4. Glosario de términos*

| Término | Significado |
|---|---|
| CILN | Centro de Idiomas y Lenguas Nativas de la UNASAM |
| DSTI | Dirección de Sistemas y Tecnologías de la Información de la UNASAM |
| DGF | Dirección de Gestión Financiera de la UNASAM |
| TdR | Términos de Referencia del servicio |
| TUPA / TUSNE | Texto Único de Procedimientos Administrativos / Texto Único de Servicios No Exclusivos |
| MVC | Modelo-Vista-Controlador, patrón que separa los datos, la presentación y el control de las solicitudes |
| API REST | Interfaz de servicios web que permite a otros sistemas consultar datos del sistema |
| Acción | Clase que contiene una sola regla de negocio y que reutilizan todas las pantallas, la API y la consola |
| Trigger | Procedimiento de la base de datos que se ejecuta automáticamente ante un alta, un cambio o una baja |
| Pre-acta | Registro de notas parciales del docente durante el ciclo, previo al acta final |
| Sistema de evaluación | Configuración de las habilidades, los exámenes y la nota mínima aprobatoria con que se califica un ciclo |
| ULID | Identificador público no secuencial que impide recorrer registros por número |
| OWASP | Open Worldwide Application Security Project, referente internacional de seguridad web |
| CI | Integración continua: verificación automática de cada cambio del código fuente |
| LTS | Versión con soporte a largo plazo (Long Term Support), que recibe parches de seguridad |
| WCAG | Pautas de accesibilidad para el contenido web (Web Content Accessibility Guidelines); el nivel AA fija, entre otros, el contraste mínimo de colores |
| OWASP ZAP | Herramienta de código abierto para el escaneo de seguridad de aplicaciones web |
| COEP | Cabecera de seguridad Cross-Origin-Embedder-Policy, que restringe la carga de recursos de otros orígenes |
| NSP | «No se presentó»: estudiante que no rindió la evaluación |
| ARCO | Derechos de acceso, rectificación, cancelación y oposición sobre los datos personales (Ley N.° 29733) |

## 1.7. Convenciones del informe

Las rutas de archivos que se citan en el informe, como `app/…`, `tests/…`, `docs/…`, `routes/…` o `composer.json`, son relativas a la carpeta raíz del código fuente del sistema en su versión v1.1.1. El código fuente se entrega en la carpeta «Código fuente» del medio de almacenamiento (USB) que acompaña al presente informe; el repositorio completo, con su historial de cambios, se encuentra en poder de la DSTI.

Las rutas con el signo «→», como «Admin → Trámites → Trámites», indican la navegación dentro del sistema en producción (`https://ciln.unasam.edu.pe`): «Admin» es el panel de administración del personal (`/admin`), «Portal docente» es el panel `/docente` y «Portal» o «Portal del estudiante» son las páginas `/estudiante`.

Los registros de decisiones de arquitectura (ADR 0001 a 0009) y la documentación técnica citada, como `docs/seguridad.md` o `docs/despliegue.md`, se encuentran en la carpeta `docs/` del código fuente.

# 2. ACTIVIDADES REALIZADAS

## 2.1. Metodología y plan de trabajo

Conforme al numeral 4.3 de los TdR, el servicio se ejecutó con un plan de trabajo coordinado con el área usuaria al inicio de la prestación, que definió la metodología, el cronograma y la estructura técnica propuesta. Se adoptó un desarrollo **iterativo e incremental por módulo**:

1. **Análisis.** En reuniones con el área usuaria del 23 al 26 de octubre de 2026 (registro fotográfico en el Anexo C), cada requisito del Anexo Técnico se transcribió y codificó (por ejemplo, `M2.1.b`), y cada supuesto o duda se registró como una decisión del proyecto (137 decisiones registradas).
2. **Diseño.** Se definieron la arquitectura, el modelo de datos por módulo y las decisiones técnicas en registros de arquitectura (ADR 0001 a 0009).
3. **Construcción guiada por pruebas.** Cada regla de negocio se implementó junto con su prueba automatizada sobre PostgreSQL, con datos sintéticos.
4. **Integración continua.** Cada cambio pasó por el análisis estático, el estilo, la auditoría de dependencias y la suite completa de pruebas antes de integrarse.
5. **Verificación funcional.** Cada flujo se comprobó en el navegador con datos de demostración, como lo usarían el estudiante, el docente y el personal.
6. **Despliegue, capacitación y cierre.** Se publicaron las versiones con etiqueta (de `v1.0.0` a `v1.1.1`), se desplegaron en el servidor designado por la DSTI, se capacitó al personal directivo, docente y administrativo del CILN, se realizaron sesiones prácticas con docentes y estudiantes, y se elaboró la documentación técnica.

## 2.2. Cronograma de ejecución

La Orden de Servicio se emitió el 20 de octubre de 2026 y se notificó al contratista el 23 de octubre de 2026; el plazo de treinta días calendario corre desde el día siguiente de su notificación, es decir, del 24 de octubre al 22 de noviembre de 2026. La ejecución se organizó en las fases siguientes; la base técnica y la primera versión de los módulos se construyeron en paralelo con el análisis, de forma iterativa, y se ajustaron en las iteraciones posteriores hasta la versión entregada `v1.1.1`. Las fechas de construcción del cronograma provienen del historial del repositorio del código fuente, en poder de la DSTI, que registra 484 cambios con su fecha hasta la versión entregada `v1.1.1`.

*Tabla 5. Cronograma de ejecución del servicio*

| Fase | Fechas | Actividades principales |
|---|---|---|
| Inicio y análisis | 23/10 (tarde) al 26/10/2026 | Levantamiento de requerimientos en reuniones con el área usuaria (registro fotográfico en el Anexo C), transcripción del Anexo Técnico y del Informe Técnico N.° 023-2026-FMAJ, plan de trabajo, arquitectura y registros de decisiones |
| Base técnica (en paralelo con el análisis) | 24/10/2026 | Proyecto base en Laravel 13 con arquitectura modular, integración continua sobre PostgreSQL 18, auditoría por triggers, autenticación y control de acceso |
| Módulos 1, 2 y 3: primera versión | 24/10 al 25/10/2026 | Oferta académica, personas, clasificación, matrícula, pagos y docencia, con pruebas de concurrencia |
| Módulos 4, 5 y 6 y API: primera versión | 25/10/2026 | Actas y certificación, tablero gerencial, trámites, portal y servicios REST; panel de administración |
| Scripts de despliegue | 26/10 al 27/10/2026 | Scripts de instalación tradicional y con contenedores, ajustes de rendimiento del panel |
| Segunda iteración funcional | 31/10 al 03/11/2026 | Control de clases con asistencia y delegado, reportes, panel docente, tablero, portal público, correcciones del panel de administración |
| Seguridad y portal del estudiante | 08/11 al 10/11/2026 | Actualizaciones de seguridad de dependencias, cabeceras y política CSP, sesiones cifradas, PHP 8.5, consentimiento de datos personales, portal del estudiante en estilo formal, paleta con contraste WCAG AA |
| Versión v1.0.0 | 16/11/2026 | Diccionario de datos generado desde PostgreSQL, portada pública institucional, actualización automática por versiones publicadas, correo institucional, guía de despliegue; publicación de la versión `v1.0.0` |
| Capacitación | 16/11 al 18/11/2026 | Capacitación al personal directivo, docente y administrativo del CILN, en sus instalaciones, con exposición del sistema (16/11); capacitación práctica a docentes y estudiantes: ingreso al sistema en producción (`https://ciln.unasam.edu.pe`), portal público, matrícula y uso del sistema por los estudiantes en aula (17 y 18/11) (Anexo C) |
| Ajuste del sistema de evaluación y documentación final | 17/11/2026 | Cálculo de la nota final por las cuatro habilidades y los exámenes; informe técnico final |
| Correcciones finales y versión entregada | 18/11 al 19/11/2026 | Verificación del correo en producción con la cuenta institucional `sistemacln@unasam.edu.pe`, escaneo OWASP ZAP, registro de notas parciales limitado al calendario académico, política de privacidad y consentimiento registrado por el personal, emisión masiva de constancias de matrícula; publicación de las versiones `v1.0.1`, `v1.1.0` y `v1.1.1`, y actualización del presente informe |

## 2.3. Actividades del TdR

*Tabla 6. Ejecución de las actividades del numeral 4.2 de los TdR*

| Actividad (TdR 4.2) | Forma de ejecución | Evidencia |
|---|---|---|
| Levantamiento de requerimientos y análisis con el área usuaria del CILN | Reuniones con el área usuaria del 23/10 al 26/10/2026; transcripción y codificación de los 96 requisitos del Anexo Técnico; registro de decisiones del proyecto | `docs/requisitos/`, `docs/modulos.md`; registro fotográfico (Anexo C, figuras 14 a 17) |
| Diseño y organización técnica del sistema y de los datos (documento técnico) | Fundamentación de PHP 8.5, Laravel, patrón MVC, modelo de datos y API REST | Capítulos 4 a 7 de este informe; `docs/arquitectura.md`, `docs/tecnologia.md`, ADR 0001 a 0009; Diccionario de Datos; revisión de la arquitectura con el área usuaria (Anexo C, figuras 18 y 19) |
| Desarrollo, pruebas e implementación de los seis módulos | Desarrollo iterativo por módulo con pruebas automatizadas sobre PostgreSQL | Capítulos 8 y 10; matriz de cumplimiento (11.3); Anexos A y B |
| Aplicación de estándares de seguridad (OWASP Top 10) y de pistas de auditoría | Controles por cada riesgo OWASP y auditoría inmutable por triggers en 42 tablas | Capítulo 9; `docs/seguridad.md` |
| Despliegue y puesta en producción en el servidor designado por la DSTI | Instalación en el servidor de la DSTI con la guía y los scripts de implementación | Capítulo 3; `docs/despliegue.md`; versión entregada `v1.1.1` |
| Capacitación al personal del CILN y entrega de manuales y del código fuente | Capacitación al personal directivo, docente y administrativo del CILN (16/11/2026) y capacitación práctica a docentes y estudiantes en el sistema en producción (17 y 18/11/2026); código fuente en la carpeta «Código fuente» de la USB y repositorio completo entregado a la DSTI; manuales de usuario entregados | Sección 11.5; registro fotográfico (Anexo C, figuras 20 a 28) |

# 3. INFRAESTRUCTURA TECNOLÓGICA

## 3.1. Servidor web y de aplicaciones

El sistema se ejecuta sobre un servidor Linux de la UNASAM con **PHP 8.5** (PHP-FPM) detrás de un servidor web **Apache 2.4** con HTTPS. Requiere las extensiones de PHP `pdo_pgsql`, `pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring` y `pcntl`. Junto al servidor web funcionan dos procesos de fondo: un **trabajador de colas** supervisado (envío de correos y reacciones a la validación de pagos) y el **programador de tareas** (cron cada minuto), que ejecuta los procesos automáticos.

La DSTI dispone de dos rutas de instalación documentadas: la **instalación tradicional** sobre el sistema operativo y la **instalación con contenedores Docker**, ambas con el mismo código fuente.

## 3.2. Sistema de gestión de base de datos

La base de datos es **PostgreSQL 18**, en el mismo servidor o en uno dedicado. Se usan dos roles: uno **dueño del esquema**, que solo ejecuta las migraciones, y otro **de aplicación**, sin privilegios de dueño, con el que funciona el sistema. Así, la aplicación no puede desactivar los triggers, escribir en la pista de auditoría ni alterar la estructura de la base.

## 3.3. Lenguajes de programación

*Tabla 7. Lenguajes empleados*

| Lenguaje | Uso |
|---|---|
| PHP 8.5 | Lógica de negocio, controladores, pantallas Livewire y Filament, API y procesos de consola |
| SQL y PL/pgSQL (PostgreSQL 18) | Restricciones de integridad, triggers de auditoría y de reglas de negocio |
| Blade (HTML) | Plantillas de las vistas, los documentos PDF y los correos |
| CSS (Tailwind CSS 4) y JavaScript (Alpine.js) | Estilos de la interfaz y comportamiento del lado del navegador |

## 3.4. Entorno de producción

Conforme al numeral 4.5 de los TdR, el sistema se encuentra **desplegado y operativo en el servidor designado por la DSTI**, en la infraestructura de la UNASAM y sin servicios de terceros en la nube, y se publica en el dominio oficial `https://ciln.unasam.edu.pe`, que la DSTI designó para el sistema nuevo (el Anexo Técnico menciona `https://cid.unasam.edu.pe/`, dominio del sistema anterior). Ninguna dirección está fija en el código: se configura con `APP_URL`. La DSTI administra el servidor, crea los roles de PostgreSQL, custodia las credenciales (base de datos, correo institucional) y decide la activación de la actualización automática.

*Tabla 8. Componentes del entorno de producción*

| Componente | Configuración |
|---|---|
| Aplicación | PHP 8.5 con Apache 2.4 y HTTPS; modo de depuración desactivado |
| Base de datos | PostgreSQL 18 con rol dueño (migraciones) y rol de aplicación |
| Procesos de fondo | Trabajador de colas supervisado y programador de tareas |
| Correo | Cuenta institucional dedicada `sistemacln@unasam.edu.pe` (Microsoft 365), habilitada por la DSTI; envío verificado en producción el 18/11/2026 |
| Almacenamiento | Disco del servidor, fuera de la carpeta pública (comprobantes, fotografías, respaldos) |
| Actualizaciones | Solo versiones publicadas con etiqueta `vX.Y.Z` en la rama principal, con reversión automática si falla antes de migrar |

# 4. FUNDAMENTACIÓN TECNOLÓGICA

Este capítulo constituye el documento técnico exigido por el numeral 4.2 de los TdR, que fundamenta la adopción de la tecnología backend, el framework, el patrón MVC, el modelado de la base de datos y la exposición de la API REST.

## 4.1. PHP 8.5 frente a PHP 5.6

El Informe Técnico N.° 023-2026-FMAJ (problema 1.1) identificó como causa principal de los riesgos del sistema anterior el uso de PHP 5.6, sin soporte ni parches de seguridad desde 2018. El numeral II.1 del Anexo Técnico exige PHP 8.5 o superior con soporte oficial activo.

*Tabla 9. Comparación entre PHP 5.6 y PHP 8.5*

| Aspecto | PHP 5.6 (sistema anterior) | PHP 8.5 (sistema nuevo) |
|---|---|---|
| Soporte de seguridad | Finalizado en 2018 | Vigente, con parches oficiales |
| Tipado | Débil, sin tipos de retorno | Tipos estrictos en parámetros, retornos y propiedades; enumeraciones |
| Rendimiento | Intérprete sin compilación JIT | Motor optimizado con compilación JIT |
| Seguridad del lenguaje | Funciones inseguras aún disponibles | Funciones inseguras retiradas; errores como excepciones |
| Ecosistema | Sin compatibilidad con frameworks actuales | Compatible con Laravel 13 y las librerías vigentes |

El proyecto exige PHP 8.5 en su configuración (`composer.json`: `"php": "^8.5"`) y aplica las reglas de modernización del código de PHP 8.4 y 8.5.

## 4.2. Laravel 13, Livewire 4 y Filament 5

- **Laravel 13** es un framework PHP consolidado, citado expresamente como referencia en el Anexo Técnico (II.1) y en la recomendación 3.1 de la DSTI. Aporta, de forma probada y mantenida, la protección CSRF, el escapado de vistas, las consultas parametrizadas, la gestión de sesiones, las colas, el programador de tareas y las migraciones de base de datos.
- **Livewire 4** construye las pantallas interactivas de los portales público, del estudiante y docente en PHP, sin una aplicación JavaScript separada; el estado y las validaciones permanecen en el servidor.
- **Filament 5** proporciona el panel de administración, el panel docente y el tablero gerencial, con tablas, formularios, filtros, gráficos y exportaciones, sobre las mismas reglas de autorización del sistema.

Las tres piezas comparten el mismo lenguaje y las mismas reglas de negocio, lo que reduce el costo de mantenimiento para la DSTI.

## 4.3. Patrón MVC y monolito modular

El sistema aplica el patrón **MVC** con una capa adicional de **Acciones** para las reglas de negocio:

- **Modelo:** clases Eloquent, una por tabla, con sus relaciones.
- **Vista:** plantillas Blade y componentes Livewire y Filament.
- **Controlador:** controladores HTTP, componentes Livewire y recursos Filament, que solo reciben la solicitud, la validan (Form Request) y llaman a una Acción.
- **Acción:** clase final con una sola regla de negocio, que reutilizan la web, el panel, la API y la consola, de modo que una regla se aplica igual en todos los canales.

La organización es un **monolito modular** (ADR 0001): un solo despliegue dividido en ocho módulos de negocio con fronteras verificadas por pruebas de arquitectura. Responde a las malas prácticas señaladas por la DSTI (código monolítico y acoplado, sin separación entre lógica y presentación) sin la complejidad operativa de los microservicios, inadecuada para el tamaño del CILN.

## 4.4. PostgreSQL 18

Se eligió **PostgreSQL 18** (ADR 0002) por las siguientes razones:

- Es un gestor relacional de código abierto, sin costo de licencia, con soporte vigente.
- Ofrece restricciones avanzadas (`CHECK`, `UNIQUE`, `EXCLUDE`) que garantizan las reglas críticas en la propia base, como que las vacantes no superen el cupo o que dos tarifas del mismo concepto no se superpongan en el tiempo.
- Permite triggers en PL/pgSQL para la pista de auditoría inmutable que exigen el numeral II.2 y la sección IV.2 del Anexo Técnico, independientes del código de la aplicación.
- Guarda la descripción de cada tabla y columna en su catálogo, de donde se genera el Diccionario de Datos.
- Maneja tipos exactos para importes (`numeric`) y fechas con zona horaria.

## 4.5. API REST

El numeral II.1 del Anexo Técnico exige exponer servicios mediante API REST con un diseño desacoplado. El sistema publica la API versionada **`/api/v1`**, autenticada con **Laravel Sanctum** (tokens personales que vencen a los 30 días) y documentada en formato OpenAPI, generado desde el código en `/docs/api`.

*Tabla 10. Servicios de la API REST*

| Grupo | Servicios |
|---|---|
| Públicos | Oferta académica, tarifas vigentes, verificación de documentos y emisión de token |
| Sesión | Consulta y revocación del token actual |
| Estudiante | Perfil, matrículas, pagos, notas, documentos y su PDF, trámites |
| Docente | Grupos a cargo |

Son 14 servicios de solo lectura, salvo la emisión y revocación del token, pensados para la interoperabilidad con otros sistemas de la UNASAM y aplicaciones móviles futuras.

# 5. ARQUITECTURA DEL SISTEMA

## 5.1. Tipo de arquitectura

Es una arquitectura **web de tres capas** (presentación, lógica de negocio y datos) organizada como **monolito modular**: una sola aplicación desplegable, dividida en módulos de negocio independientes que se comunican por contratos explícitos. Las reglas de desacople se verifican con pruebas automatizadas:

- Un módulo solo usa la parte pública de otro (contratos, datos, enumeraciones y eventos); sus modelos y lógica internos no se comparten.
- Las precondiciones se consultan de forma síncrona (por ejemplo, la matrícula pregunta a Pagos si el pago está validado) y los efectos secundarios se comunican por eventos, despachados después de confirmar la transacción.
- Cada módulo es dueño de sus tablas, con prefijo propio, y las relaciones entre módulos son llaves foráneas reales de PostgreSQL.

## 5.2. Componentes principales

*Tabla 11. Módulos de negocio y núcleo compartido*

| Componente | Responsabilidad | Prefijo de tablas |
|---|---|---|
| Académico | Idiomas, ciclos, periodos, aulas, grupos, horarios y sistemas de evaluación | `aca_` |
| Personas | Ficha personal de estudiantes, postulantes y docentes | `per_` |
| Matrícula | Clasificación, habilitaciones, matrículas, fichas, traslados y retiros | `mat_` |
| Pagos | Conceptos, tarifas, medios de pago, pagos y su validación | `pag_` |
| Docencia | Padrón docente, carga académica, clases, asistencia, notas parciales y costos por hora | `doc_` |
| Certificación | Actas finales, rectificaciones, constancias y certificados | `cer_` |
| Tablero | Indicadores gerenciales (solo lectura) | — |
| Trámites | Tipos de trámite, etapas, trámites, movimientos y notificaciones | `tra_` |
| Núcleo compartido | Autenticación, roles y permisos, auditoría, archivos y notificaciones | `aud_` y tablas del framework |

## 5.3. Arquitectura lógica

La Figura 1 muestra las capas del sistema: los canales de presentación (portales, paneles y API) invocan las Acciones de los módulos de negocio, que se apoyan en el núcleo compartido y en la infraestructura de la UNASAM.

![Figura 1. Arquitectura lógica del sistema](figuras/arquitectura-logica.png)

## 5.4. Arquitectura física

La Figura 2 muestra la distribución en el servidor de la DSTI: el navegador del usuario se conecta por HTTPS, a través del dominio oficial `https://ciln.unasam.edu.pe`, al servidor web, que atiende la aplicación PHP; esta se conecta a PostgreSQL, al almacenamiento local y al servicio de correo institucional, mientras el trabajador de colas y el programador de tareas procesan las tareas de fondo.

![Figura 2. Arquitectura física del sistema](figuras/arquitectura-fisica.png)

# 6. MARCOS DE TRABAJO Y HERRAMIENTAS

## 6.1. Backend y frontend

*Tabla 12. Marcos de trabajo y librerías principales*

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | 8.5 |
| Framework backend | Laravel | 13 |
| Panel de administración, panel docente y tablero | Filament | 5 |
| Portales público y del estudiante | Blade y Livewire | Livewire 4 |
| Estilos e interfaz | Tailwind CSS, compilado con Vite | 4 |
| API REST | Laravel Sanctum; documentación OpenAPI con Scramble | Sanctum 4 |
| Roles y permisos | spatie/laravel-permission | 8 |
| Documentos PDF y códigos QR | spatie/laravel-pdf con DOMPDF; endroid/qr-code | 2 / 3 / 6 |
| Hojas de cálculo | maatwebsite/excel | 4 |
| Respaldos | spatie/laravel-backup | 10 |

## 6.2. Estructura del código fuente

*Tabla 13. Estructura del código fuente*

| Carpeta | Contenido |
|---|---|
| `app/Modules/<Módulo>` | Cada módulo con sus acciones, contratos, modelos, pantallas, rutas, migraciones y vistas |
| `app/Shared` | Autenticación, auditoría, archivos, notificaciones y utilidades comunes |
| `database/migrations` | Migraciones generales (cuentas, colas, caché, permisos, comentarios del diccionario de datos) |
| `resources/` | Estilos, componentes de interfaz y vistas compartidas |
| `tests/` | Pruebas automatizadas por módulo, transversales, de concurrencia y de arquitectura |
| `docs/` | Documentación técnica: requisitos, arquitectura, módulos, modelo de datos, seguridad, despliegue, decisiones (ADR) y manuales |

## 6.3. Herramientas de construcción, pruebas y control de versiones

*Tabla 14. Herramientas de desarrollo*

| Herramienta | Propósito |
|---|---|
| Composer y npm con Vite | Gestión de dependencias de PHP y JavaScript; compilación de estilos |
| Pest 5 | Pruebas automatizadas, ejecutadas siempre sobre PostgreSQL |
| Larastan (nivel 8) con límite de complejidad cognitiva | Análisis estático de tipos y de complejidad |
| Rector y Pint | Modernización y estilo uniforme del código |
| `composer audit` | Detección de dependencias con vulnerabilidades conocidas |
| Git y GitHub Actions | Control de versiones con historial completo e integración continua |
| Docker | Base de datos de desarrollo y ruta de instalación con contenedores |

# 7. MODELO DE DATOS

## 7.1. Modelo físico

La base de datos PostgreSQL 18 tiene **59 tablas y 548 columnas**, organizadas por módulo con prefijo propio. La Figura 3 resume las entidades principales y sus relaciones.

![Figura 3. Modelo físico de datos](figuras/modelo-datos.png)

*Tabla 15. Tablas y columnas por módulo*

| Módulo | Tablas | Columnas |
|---|---|---|
| Académico (oferta académica) | 8 | 78 |
| Personas | 1 | 17 |
| Matrícula | 5 | 54 |
| Pagos | 5 | 55 |
| Docencia | 11 | 94 |
| Certificación | 7 | 88 |
| Trámites | 5 | 55 |
| Auditoría | 1 | 10 |
| Seguridad y control de acceso | 9 | 52 |
| Infraestructura de la aplicación | 7 | 45 |
| **Total** | **59** | **548** |

## 7.2. Integridad y auditoría

Las reglas críticas no dependen solo del código de la aplicación; la base de datos las garantiza:

- Llaves foráneas reales entre todos los módulos, con índices en cada llave foránea.
- Restricciones `CHECK`, `UNIQUE` y `EXCLUDE`: las vacantes nunca son negativas ni superan el cupo, y dos tarifas del mismo concepto no se superponen en el tiempo.
- Triggers que impiden los cruces de horario de aulas y docentes, que un sistema de evaluación quede sin habilidades o sin exámenes, la modificación de pagos validados, de actas cerradas y de documentos emitidos, y el borrado de registros financieros y académicos, que solo se anulan con motivo.
- Importes en `numeric` (nunca en coma flotante) y fechas con zona horaria, mostradas en hora de Lima.
- La función `aud_registrar()` registra cada alta, cambio o baja de 42 tablas en la pista de auditoría (sección 9.2).

## 7.3. Diccionario de datos

El detalle de cada tabla (columnas, tipos, nulidad, valores por defecto, restricciones, índices, relaciones y triggers), con los diagramas entidad-relación por módulo, se entrega en el documento separado **«Diccionario de Datos – Sistema de Gestión del CILN»**. Se genera directamente del catálogo de PostgreSQL con el comando `php artisan docs:diccionario-de-datos`, por lo que coincide siempre con la base instalada; las descripciones de tablas y columnas están guardadas en la propia base y pueden consultarse con cualquier cliente de PostgreSQL.

# 8. FUNCIONALIDAD DEL SISTEMA

## 8.1. Módulos del sistema

*Tabla 16. Módulos del Anexo Técnico y funciones principales*

| Módulo del Anexo Técnico | Funciones principales | Ubicación |
|---|---|---|
| M1. Registro, examen de clasificación y matrícula | Autorregistro, solicitud y resultado del examen de clasificación, oferta de grupos con vacantes en tiempo real, matrícula autogestionada con bloqueos, ficha de matrícula en PDF con QR, traslados y retiros | Portal del estudiante; panel: Matrícula, Oferta académica |
| M2. Gestión y validación de pagos | Registro de pagos con comprobante digital, validación por caja, desbloqueo automático de servicios, anulación con motivo, reporte de recaudación en PDF y CSV | Portal del estudiante; panel: Pagos, Catálogos |
| M3. Gestión docente y carga académica | Legajo docente, disponibilidad, carga sin cruces de horario, asistencia con confirmación del delegado, pre-actas y expediente de horas | Panel docente; panel: Docencia |
| M4. Evaluación y certificación digital | Actas finales con cálculo automático, cierre y bloqueo, rectificación autorizada, constancias y certificados, individuales o masivos, con código único y QR, verificación pública | Panel docente; panel: Certificación; `/verificar` |
| M5. Tablero de control gerencial | Indicadores financieros y académicos calculados en cada consulta, filtros y exportación a PDF y Excel | Panel: Tablero gerencial |
| M6. Portal público y trámites | Portada institucional con oferta, requisitos, costos y fechas; trámites con etapas configurables, barra de progreso y notificación por correo | Portada; portal del estudiante; panel: Trámites |

**Sistema de evaluación.** Conforme al numeral M4.1 del Anexo Técnico, los promedios se calculan «según el sistema de evaluación del CILN». La nota final es el promedio simple de dos promedios: el de las **cuatro habilidades** (comprensión auditiva, expresión oral, comprensión lectora y expresión escrita) y el de los **exámenes** (examen parcial y examen final). Los promedios intermedios no se redondean; **solo la nota final se redondea a un entero**, con 0,5 hacia arriba. Así lo indicó el área usuaria. Cada sistema de evaluación configura sus componentes, la nota máxima y la nota mínima aprobatoria (11 sobre 20 por defecto).

## 8.2. Roles y permisos

El acceso se controla por **permisos por acción**, que se agrupan en roles desde el propio panel (Seguridad → Roles y Usuarios del personal), sin modificar el código.

*Tabla 17. Perfiles de usuario y acceso*

| Perfil | Identificación | Acceso |
|---|---|---|
| Personal del CILN | Cuenta con permiso de acceso al panel y los permisos de su función | Panel de administración, con doble factor opcional u obligatorio por configuración |
| Docente del CILN | Cuenta vinculada a un legajo docente | Panel docente: sus grupos, asistencia, pre-actas y actas |
| Estudiante | Cuenta con correo verificado y ficha personal | Portal del estudiante: matrícula, pagos, clasificación, trámites, notas y documentos |
| Público | Sin cuenta | Portada, verificación de documentos y creación de cuenta |

*Tabla 18. Permisos disponibles para los roles del personal*

| Área | Permisos |
|---|---|
| Pagos | Ver, registrar, validar, anular, configurar y reportar |
| Académico, personas, matrícula y clasificación | Ver y gestionar; evaluar la clasificación |
| Docencia | Ver, gestionar, costos y reportes |
| Certificación | Actas y documentos |
| Trámites | Gestionar y configurar |
| Transversales | Tablero, auditoría y gestión de usuarios |

## 8.3. Interfaces principales

### 8.3.1. Portada pública

La portada institucional presenta la oferta académica vigente, los requisitos, las fechas, los costos según el TUPA/TUSNE y la ruta de aprendizaje por idioma. Es el punto de ingreso de estudiantes y docentes (requisito M6.1.a).

![Figura 4. Portada pública del CILN](figuras/portada-publica.png)

### 8.3.2. Acceso al sistema

Una sola página de ingreso (`/ingresar`) lleva a estudiantes y docentes a su portal según su perfil (portal del estudiante o panel docente), con recuperación de la contraseña mediante un enlace enviado al correo registrado (M6.1.b y M6.1.c), cuyo envío se verificó en producción el 18 de noviembre de 2026. El personal del CILN ingresa al panel administrativo por su propia página de ingreso (`/admin/login`); el doble factor del panel está disponible y puede hacerse obligatorio por configuración (`PANEL_DOBLE_FACTOR_OBLIGATORIO`).

![Figura 5. Página de acceso](figuras/acceso.png)

### 8.3.3. Portal del estudiante

El estudiante consulta su estado de matrícula y los requisitos pendientes, se matricula en un grupo con vacantes, registra sus pagos, solicita el examen de clasificación y sigue sus trámites con una barra de progreso (M1.3 y M6.2).

![Figura 6. Portal del estudiante](figuras/portal-estudiante.png)

### 8.3.4. Notas del estudiante

El estudiante revisa sus calificaciones por periodo: las notas de cada habilidad y de cada examen, los promedios y la nota final con su estado (aprobado, desaprobado o NSP).

![Figura 7. Notas del estudiante](figuras/notas-estudiante.png)

### 8.3.5. Panel docente

El docente ve sus grupos a cargo en **Mis grupos**, con el horario y los estudiantes matriculados, y en cada grupo registra la **Asistencia**, las notas en la **Pre-acta** y el **Acta final** (M3.2.d y M4.1.a).

![Figura 8. Panel docente](figuras/panel-docente.png)

### 8.3.6. Registro de notas

El docente registra las notas de las cuatro habilidades y de los exámenes; el sistema calcula los promedios y la nota final de forma automática y la redondea a un entero. Las notas parciales se aceptan solo desde la fecha de inicio del periodo hasta su última fecha de actas (hora de Lima) y nunca después de existir el acta del grupo; fuera de ese plazo la pre-acta queda de solo lectura, con un aviso (M4.1.a y M4.1.b).

![Figura 9. Registro de notas del docente](figuras/registro-de-notas.png)

### 8.3.7. Panel de administración

El personal del CILN accede a los módulos según sus permisos, con el menú agrupado por módulo del TdR, catálogos y seguridad. Desde aquí se configuran la oferta, las tarifas, los sistemas de evaluación y los tipos de trámite sin programar.

![Figura 10. Panel de administración](figuras/panel-administracion.png)

### 8.3.8. Caja y pagos

El cajero revisa el comprobante digital de cada pago y lo valida o rechaza con motivo; al validarlo, el sistema desbloquea automáticamente la matrícula, la clasificación o el trámite asociado (M2.1).

![Figura 11. Caja y validación de pagos](figuras/caja-pagos.png)

### 8.3.9. Acta final

El acta final consolida las notas del grupo con el promedio y el estado de cada estudiante; al cerrarse queda bloqueada, y toda rectificación posterior requiere la autorización del administrador y queda en la auditoría (M4.1.c). Se emite en PDF con diseño institucional.

![Figura 12. Acta final](figuras/acta-final.png)

### 8.3.10. Tablero gerencial

El tablero muestra los ingresos, los pagos pendientes de validación, la rentabilidad por grupo, las matrículas y su crecimiento, la deserción y la demanda horaria, con filtros por periodo, idioma y nivel y exportación a PDF y Excel (M5).

![Figura 13. Tablero gerencial](figuras/tablero.png)

# 9. SEGURIDAD DE LA INFORMACIÓN

## 9.1. OWASP Top 10

*Tabla 19. Controles frente al OWASP Top 10 (2021)*

| Riesgo OWASP (2021) | Control implementado |
|---|---|
| A01 Control de acceso roto | Permiso por acción y política de autorización en cada recurso; el panel niega toda acción no concedida explícitamente; identificadores públicos no secuenciales (ULID); cada estudiante y docente ve solo sus propios datos |
| A02 Fallas criptográficas | Contraseñas con hash; sesión, cuenta bancaria del docente y secretos del doble factor cifrados; HTTPS con `Strict-Transport-Security` |
| A03 Inyección | Consultas parametrizadas; escapado automático de las vistas; política de seguridad de contenido (CSP) |
| A04 Diseño inseguro | Una Acción por regla de negocio, validada también en la base de datos; bloqueo de filas en operaciones concurrentes (última vacante, doble validación) |
| A05 Configuración insegura | Cabeceras de seguridad en toda respuesta; depuración desactivada en producción; páginas de error sin trazas técnicas |
| A06 Componentes vulnerables | Versiones con soporte vigente; `composer audit` en la integración continua; actualización inmediata ante avisos de seguridad |
| A07 Fallas de autenticación | Límite de intentos en el ingreso, el registro, la recuperación de contraseña y la verificación pública; doble factor para el personal; cierre del panel tras 30 minutos sin actividad |
| A08 Integridad de datos y software | Huella SHA-256 de cada comprobante; verificación del tipo real de los archivos subidos; documentos verificables por código único y QR |
| A09 Registro y monitoreo | Pista de auditoría inmutable; registro de rechazos de negocio con datos personales enmascarados; identificador de solicitud en cada registro |
| A10 Falsificación de solicitudes del lado del servidor | El sistema no realiza peticiones a direcciones ingresadas por el usuario |

Las sesiones usan cookies `HttpOnly`, `Secure` y `SameSite`, con el contenido cifrado y regeneración al iniciar sesión; la protección CSRF verifica el token de cada formulario. La API usa tokens personales que vencen a los 30 días.

**Escaneo de seguridad.** El 19 de noviembre de 2026 se ejecutó el escaneo pasivo OWASP ZAP 2.17.0 (*baseline*), sin ataques ni modificación de datos, sobre la versión entregada **v1.1.1** del sistema en un entorno equivalente al de producción (Apache 2.4 con HTTPS, PHP-FPM 8.5 y la misma configuración `public/.htaccess`). Resultado: **ningún hallazgo de riesgo alto y ninguna regla fallida** (58 reglas aprobadas). Los tres avisos de riesgo medio corresponden a la política de contenido (`unsafe-inline` y `unsafe-eval`), que Livewire, Alpine y Filament requieren para funcionar; es un riesgo aceptado, mitigado por el escape de salida de Blade, la validación de entradas y una política `default-src 'self'` con `object-src 'none'` y `frame-ancestors 'self'`. De los cinco avisos de riesgo bajo, tres son falsos positivos (la cookie XSRF-TOKEN es legible por diseño para la protección CSRF, marcas de tiempo dentro de las librerías y la página de redirección estándar de la verificación de documentos) y dos son avisos sin impacto: la cabecera opcional COEP, que no se activa porque impediría cargar el visor de la documentación de la API, y la cabecera `X-Powered-By` del entorno de prueba, que en el servidor se suprime con `expose_php = Off` en la configuración de PHP. El escaneo inicial sobre el servidor de producción (v1.0.0), autorizado por la DSTI, señaló cookies sin la marca `Secure` y cabeceras ausentes en los archivos estáticos; desde la versión v1.0.1 están corregidos y se añadieron las cabeceras `Cross-Origin-Opener-Policy` y `Cross-Origin-Resource-Policy`. El reporte se entrega como evidencia (*Reporte OWASP ZAP - Sistema CILN v1.1.1*); se recomienda repetirlo periódicamente sobre `https://ciln.unasam.edu.pe`.

## 9.2. Pista de auditoría inmutable

Conforme al numeral II.2 y a la sección IV.2 del Anexo Técnico, un trigger de PostgreSQL registra en la tabla `aud_registros` cada alta, cambio o baja de **42 tablas críticas** (notas parciales y finales, actas, rectificaciones, pagos, tarifas, matrículas, movimientos, documentos, roles y permisos, entre otras), con fecha y hora, dirección IP, usuario, operación, valores anteriores y nuevos e identificador de la solicitud.

La tabla rechaza toda modificación, borrado o vaciado. En producción, la aplicación se conecta con un rol que no es dueño de las tablas, por lo que no puede desactivar los triggers ni escribir directamente en la auditoría. El personal autorizado la consulta en Seguridad → Auditoría, con los datos sensibles ocultos.

## 9.3. Protección de datos personales (Ley N.° 29733)

- La política de privacidad se publica en `/privacidad` (titular del banco de datos, finalidad, datos tratados, base legal, conservación, transferencias, medidas de seguridad y derechos ARCO) y se enlaza desde el registro, el inicio de sesión y el pie de la portada.
- El autorregistro exige el consentimiento expreso para el tratamiento de los datos personales y guarda su fecha y hora.
- Para las personas que registra el personal, el consentimiento se registra con su fecha y hora cuando la persona firmó su autorización (casilla del personal en la ficha); la edición posterior de la ficha nunca lo borra.
- La verificación pública de documentos y fichas muestra solo los datos mínimos, con el documento de identidad enmascarado, y no se indexa en buscadores.
- Las fotografías se guardan fuera de la carpeta pública y sin metadatos (ubicación GPS, equipo).
- La cuenta bancaria del docente se guarda cifrada y se muestra enmascarada.
- El tablero gerencial solo muestra cifras agregadas, sin nombres ni documentos.
- Las pruebas y los entornos de demostración usan exclusivamente datos sintéticos; los datos de demostración se niegan a cargarse en producción.

## 9.4. Respaldos y recuperación

El sistema incluye respaldos programados de la base de datos y de los archivos (`spatie/laravel-backup`), con limpieza diaria a la 01:00 y respaldo a la 01:30, y cifrado AES-256 opcional del archivo de respaldo. Se entregan **desactivados por defecto** (`CILN_RESPALDOS_PROGRAMADOS=false`) porque el disco de destino, la retención y la prueba de restauración corresponden a la DSTI, que los configuró en el servidor de producción siguiendo el procedimiento de la guía de despliegue: definir el destino, activar los respaldos, ejecutar un respaldo manual y restaurarlo en un entorno de pruebas.

La recuperación ante fallos de actualización está automatizada: si una actualización falla antes de migrar la base, el sistema vuelve a la versión anterior y sale del modo de mantenimiento.

# 10. ASEGURAMIENTO DE LA CALIDAD

## 10.1. Pruebas automatizadas

El sistema cuenta con **241 archivos de prueba y 1 588 casos de prueba**, ejecutados siempre sobre PostgreSQL con datos sintéticos. Cubren las reglas de negocio de cada módulo, los flujos completos de las pantallas, la API, la seguridad (autorización, sesiones, cabeceras, auditoría), la concurrencia con conexiones reales (última vacante, doble validación de pagos) y la arquitectura (desacople entre módulos).

*Tabla 20. Pruebas automatizadas por tipo*

| Tipo de prueba | Archivos | Casos |
|---|---|---|
| Reglas de negocio por módulo (`tests/Modules`) | 157 | 1 193 |
| Flujos transversales, API, seguridad y portales (`tests/Feature`) | 60 | 334 |
| Concurrencia con conexiones reales (`tests/Concurrency`) | 15 | 33 |
| Unitarias (`tests/Unit`) | 6 | 20 |
| Arquitectura y desacople (`tests/Architecture`) | 3 | 8 |
| **Total** | **241** | **1 588** |

Los casos con conjuntos de datos se ejecutan una vez por cada dato, por lo que la suite completa suma **2 250 ejecuciones de prueba**, todas aprobadas en la integración continua de la versión entregada `v1.1.1`, el 19 de noviembre de 2026. El inventario por directorio está en el Anexo B.

## 10.2. Análisis estático e integración continua

Cada cambio se verifica con el comando `composer check`, el mismo que ejecuta la integración continua en GitHub Actions:

- **Pint** y **Rector**: estilo uniforme y código modernizado a PHP 8.5.
- **Larastan en nivel 8**, con un límite de complejidad cognitiva de 8 por método y 40 por clase, que impide funciones extensas o anidadas difíciles de mantener.
- **`composer audit`**: bloquea dependencias con vulnerabilidades conocidas.
- **Pest**: la suite completa sobre PostgreSQL 18.

La verificación completa de la versión entregada `v1.1.1` resultó satisfactoria el 19 de noviembre de 2026. Durante el servicio se aplicaron de inmediato las actualizaciones de seguridad publicadas para el framework y sus componentes.

## 10.3. Pruebas con el sistema desplegado

Además de las pruebas automatizadas, cada flujo se verificó en el navegador con datos de demostración, desde el rol de estudiante, docente, cajero y administrador. Sobre la instalación en el servidor se comprobó el funcionamiento de la portada, el inicio de sesión, la cola de correos y el respaldo con el rol de aplicación, y que dicho rol no puede escribir en la pista de auditoría, desactivar triggers, vaciar tablas ni crear objetos en la base. El 18 de noviembre de 2026 se verificó en producción la recepción de los correos de recuperación y de cambio de contraseña enviados desde la cuenta institucional `sistemacln@unasam.edu.pe`, habilitada por la DSTI.

# 11. VERIFICACIÓN DEL CUMPLIMIENTO

## 11.1. Orden de Servicio

*Tabla 21. Cumplimiento de la Orden de Servicio N.° 0003091*

| Condición | Requisito | Evidencia | Estado |
|---|---|---|---|
| Objeto | Diseño, desarrollo, despliegue y puesta en producción del Sistema de Gestión Académica y Administrativa del CILN | Capítulos 4 a 8; sistema desplegado en el servidor de la DSTI | Cumple |
| Plazo | 30 días calendario contados desde el día siguiente de la notificación de la orden (emitida el 20/10/2026 y notificada el 23/10/2026) | Cronograma (2.2): ejecución iniciada el 23/10/2026, tras la notificación, y concluida el 19/11/2026, antes del vencimiento del plazo (22/11/2026) | Cumple |
| Actividades | Las seis actividades del TdR 4.2, transcritas en la orden | Tabla de actividades (2.3), sección 11.5 y Anexo C | Cumple |
| Seis módulos | Registro, clasificación y matrícula; pagos; docencia; evaluación y certificación; tablero; portal y trámites | Matriz del Anexo Técnico (11.3): de 78 requisitos funcionales, 77 cumplidos y 1 cumplido parcialmente por diseño (M6.2.e) | Cumple (1 requisito parcial por diseño, M6.2.e) |
| Seguridad | Estándares OWASP Top 10 y pistas de auditoría | Capítulo 9 | Cumple |
| Pago | Una armada, previa conformidad del área usuaria sobre el único entregable | Presente informe y componentes de la sección 11.5 | Para conformidad (Informe de Conformidad de Servicio, TdR 6.7) |

## 11.2. Términos de Referencia

*Tabla 22. Cumplimiento de las cláusulas de los Términos de Referencia*

| Cláusula | Requisito | Evidencia | Estado |
|---|---|---|---|
| 3.2 | Software que automatice el registro, clasificación, matrícula, pagos, gestión docente y certificación, sobre arquitectura moderna y segura | Capítulos 4, 5 y 8; matriz (11.3) | Cumple |
| 4.1 | Servicio conforme al detalle del Anexo Técnico | Matriz (11.3): 95 requisitos cumplidos y 1 cumplido parcialmente por diseño (M6.2.e) | Cumple (1 requisito parcial por diseño, M6.2.e) |
| 4.2 | Seis actividades mínimas | Tabla de actividades (2.3); Anexo C | Cumple |
| 4.3 | Plan de trabajo con metodología, cronograma y estructura técnica | Secciones 2.1 y 2.2 | Cumple |
| 4.5 | Sistema desplegado y operativo en el servidor de la DSTI, con código fuente, manuales y documentación técnica | Sección 3.4; relación de componentes (11.5) | Cumple |
| 4.6 | Plazo máximo de 30 días calendario | Cronograma (2.2): ejecución iniciada el 23/10/2026, tras la notificación, y concluida el 19/11/2026, antes del vencimiento del plazo (22/11/2026) | Cumple |
| 6.1 | Código fuente, documentación, manuales, scripts y repositorios; garantía; capacitación; Ley N.° 29733 y auditoría inmutable; cesión de derechos | Secciones 9.2, 9.3, 11.5 y 11.6; Anexos C y D | Cumple |
| 6.2 | Obligaciones de la Entidad: acceso al servidor de la DSTI y contraparte técnica | La DSTI brindó el acceso al servidor de producción y la contraparte técnica (sección 11.6) | Cumplido por la Entidad |
| 6.3 | Único entregable: informe final, sistema desplegado y operativo, código fuente, repositorios, scripts, manuales y documentación | Presente informe y sección 11.5 | Cumple |
| 6.5 | Confidencialidad de la información académica, financiera y personal | Pruebas y demostraciones solo con datos sintéticos; sección «Uso de la información» | Cumple |
| 6.8 | Penalidad por mora (artículo 120 del Reglamento de la Ley N.° 32069) | El entregable se presenta antes del vencimiento del plazo (22/11/2026); no hay días de atraso | No corresponde |
| 6.9 | Responsabilidad por vicios ocultos por un (1) año | Compromiso formalizado en la carta de garantía técnica (sección 11.6; Anexo D) | Cumple (compromiso formalizado) |
| 6.10 | Garantía técnica de funcionamiento y soporte por 12 meses | Carta de garantía técnica por 12 meses desde la conformidad final (sección 11.6; Anexo D) | Cumple (compromiso formalizado; rige desde la conformidad) |
| 6.11 | Cláusula anticorrupción y antisoborno | Declaración del contratista (sección 11.6) | Cumple |
| Anexo IV.4 | Garantía de funcionamiento y soporte técnico mínimo de 12 meses | Ídem | Cumple (compromiso formalizado; rige desde la conformidad) |

## 11.3. Matriz de cumplimiento del Anexo Técnico

Se verificaron **96 requisitos** del Anexo Técnico contra el código fuente, las pantallas y las pruebas automatizadas del sistema. Cada requisito se identifica con un código (por ejemplo, `M2.1.b`); las rutas del código y las pruebas que lo comprueban están en el Anexo A, y los números precedidos de «#» remiten al registro de decisiones del proyecto (`docs/decisiones-pendientes.md`).

En la columna de ubicación, «Admin» es el panel de administración del personal (`/admin`), «Portal docente» es el panel `/docente` y «Portal» o «Portal del estudiante» son las páginas `/estudiante`. Las rutas del código fuente y las rutas de navegación del sistema se explican en la sección 1.7.

*Tabla 23. Resumen del cumplimiento del Anexo Técnico*

| Sección del Anexo Técnico | Requisitos | Cumple | Cumple parcialmente | No cumple |
|---|---|---|---|---|
| II. Requisitos técnicos generales | 12 | 12 | 0 | 0 |
| M1. Registro, clasificación y matrícula | 22 | 22 | 0 | 0 |
| M2. Pagos | 12 | 12 | 0 | 0 |
| M3. Docencia | 15 | 15 | 0 | 0 |
| M4. Evaluación y certificación | 11 | 11 | 0 | 0 |
| M5. Tablero gerencial | 7 | 7 | 0 | 0 |
| M6. Portal público y trámites | 11 | 10 | 1 | 0 |
| IV. Seguridad, propiedad intelectual y confidencialidad | 6 | 6 | 0 | 0 |
| **Total** | **96** | **95** | **1** | **0** |

**Los seis módulos funcionales están atendidos**: cada uno de los 78 requisitos funcionales tiene una pantalla o un servicio que lo atiende y, al menos, una prueba automatizada que lo comprueba; 77 se cumplen y uno (M6.2.e) se cumple parcialmente por una decisión de diseño. La cesión de derechos (IV.1.a) y la garantía técnica (IV.4.a) se cumplen con las cartas suscritas por el contratista (Anexo D). El único requisito cumplido parcialmente no depende de una función faltante: mantiene fijos, por diseño, los estados de los trámites:

*Tabla 24. Requisito cumplido parcialmente y acción que corresponde*

| Requisito | Situación | Acción y responsable |
|---|---|---|
| M6.2.e Panel para configurar etapas, estados y condiciones de cada trámite | Etapas (nombre, porcentaje y condición), tasa, requisitos y acción final configurables desde el panel; los estados del flujo y las tres condiciones de etapa son fijos por diseño | Se mantienen fijos para conservar coherentes las reglas de pago y de auditoría; un estado adicional que requiera el CILN se atiende como mejora, fuera del alcance de este servicio, previa evaluación del CILN y la DSTI |

### 11.3.1. Requisitos técnicos generales (sección II del Anexo)

*Tabla 25. Matriz de cumplimiento: Requisitos técnicos generales (sección II del Anexo)*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| II.1.a | PHP 8.5 o superior, con soporte LTS y parches vigentes | Plataforma | Cumple | — |
| II.1.b | Framework consolidado bajo patrón MVC o multicapa | Plataforma | Cumple | Laravel 13; monolito modular (Controlador/Livewire/Filament → Form Request → Acción → Modelo), ADR 0001 |
| II.1.c | Servicios expuestos por API REST y diseño desacoplado | API `/api/v1` | Cumple | 14 servicios REST, de solo lectura salvo la emisión/revocación de tokens (#86); los módulos se comunican mediante interfaces de contrato de la carpeta `Contracts/` |
| II.1.d | Base de datos relacional normalizada con índices adecuados | Plataforma | Cumple | PostgreSQL 18 con claves foráneas reales, `CHECK` y tablas con prefijo por módulo |
| II.1.e | Compatible con entorno y estándares de la DSTI | Despliegue | Cumple | Desplegado y operativo en el servidor de la DSTI, con HTTPS, correo institucional y respaldos configurados por la DSTI |
| II.2.a | Cumplimiento del OWASP Top 10 | Transversal | Cumple | Controles implementados y probados; escaneo OWASP ZAP de la v1.1.1 sin hallazgos de riesgo alto ni reglas fallidas (sección 9.1) |
| II.2.b | Validación de entradas; protección contra SQLi, XSS y CSRF | Transversal | Cumple | Validación con Form Requests; consultas parametrizadas; escapado automático de las vistas; token CSRF verificado por el framework en cada formulario |
| II.2.c | Gestión segura de sesiones y cifrado de datos sensibles | Admin → (perfil) Doble factor; transversal | Cumple | Doble factor opcional (#92); cierre del panel a los 30 min (#93) |
| II.2.d | Tratamiento de datos personales conforme a la Ley N.° 29733 | Portal del estudiante → Crear cuenta; Sitio público → Política de privacidad (`/privacidad`), `/fichas/{código}`, `/verificar`; Admin → Matrícula → Personas | Cumple | Política de privacidad pública (titular, finalidad, datos, base legal, conservación, transferencias, medidas de seguridad y derechos ARCO), enlazada desde el registro, el ingreso y el pie de la portada; consentimiento con fecha y hora en el autorregistro y, para las personas registradas por el personal, al firmar su autorización (casilla del personal), que la edición nunca borra; documento enmascarado en verificación pública (#67), sin exportación de personas; datos de prueba sintéticos |
| II.2.e | Log Triggers inmutables: fecha, hora, IP, usuario y detalle | Admin → Seguridad → Auditoría | Cumple | Función `aud_registrar()` en 42 tablas (notas, pagos, actas); `aud_registros` rechaza UPDATE/DELETE/TRUNCATE |
| II.3.a | Interfaz responsive, intuitiva, accesible y adaptable a móviles | Todas las pantallas | Cumple | Tailwind CSS con puntos de quiebre y Filament; verificación en navegador; contraste de colores conforme a WCAG AA |
| II.4.a | Escalabilidad, interoperabilidad, alta disponibilidad e integraciones futuras | Plataforma | Cumple | Arquitectura preparada (módulos desacoplados, API REST, colas); infraestructura de producción y respaldos programados configurados por la DSTI |

### 11.3.2. Módulo 1: Registro, examen de clasificación y matrícula

*Tabla 26. Matriz de cumplimiento: Módulo 1: Registro, examen de clasificación y matrícula*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M1.1.a | Registro de postulantes que desean ingresar a nivel superior al básico | Portal del estudiante → Crear cuenta (`/estudiante/registro`) | Cumple | — |
| M1.1.b | Gestión de solicitudes para rendir el examen de clasificación | Portal del estudiante → Clasificación; Admin → Matrícula → Solicitudes de clasificación | Cumple | Exige pago validado del concepto; vence a los 30 días (#11, #33, #36) |
| M1.1.c | Ingreso de resultados por coordinación y asignación automática del nivel | Admin → Matrícula → Solicitudes de clasificación (registrar resultado) | Cumple | La coordinación registra el puntaje y el nivel resultante; el sistema asigna automáticamente el ciclo habilitado en el perfil del estudiante (origen "Examen de clasificación") (#34, #35) |
| M1.1.campos: postulante | Tipo/N.° documento, apellidos, nombres, nacimiento, correo, celular | Admin → Matrícula → Personas; Portal → Crear cuenta | Cumple | — |
| M1.1.campos: vínculo | Tipo de usuario (Estudiante, Docente/Administrativo UNASAM, Público) para tarifas | Admin → Matrícula → Personas; Admin → Catálogos → Conceptos y tarifas | Cumple | Autorregistro como Público General; solo el personal cambia el vínculo (#26) |
| M1.1.campos: examen | Idioma, fecha, puntaje, nivel asignado | Admin → Matrícula → Solicitudes de clasificación | Cumple | El nivel asignado se expresa como ciclo del idioma (con su nivel) |
| M1.1.campos: estado | Pendiente de Evaluación, Evaluado, Expirado | Admin → Matrícula → Solicitudes de clasificación | Cumple | Se agrega "Pago anulado" (#131); expiración diaria `clasificacion:expirar` |
| M1.2.a | Crear y ofertar cursos: cupos, horarios, idiomas, modalidades y acelerado | Admin → Oferta académica → Periodos / Idiomas y ciclos / Grupos / Aulas | Cumple | Modalidad Presencial/Virtual/Semipresencial combinada con el atributo «acelerado» del grupo (`es_acelerado`), registrado aparte para que un grupo sea, por ejemplo, presencial y acelerado |
| M1.2.campos: ciclo | Año, mes/periodo, fecha de inicio y fin | Admin → Oferta académica → Periodos | Cumple | — |
| M1.2.campos: grupo | Código, idioma, nivel, ciclo (I a XII) | Admin → Oferta académica → Grupos; Idiomas y ciclos | Cumple | Distribución de ciclos por nivel configurable (#9) |
| M1.2.campos: capacidad | Cupo máximo, mínimo de apertura, vacantes en tiempo real | Admin → Oferta académica → Grupos; Portal → Matrícula | Cumple | `CHECK matriculados <= cupo_maximo`; cupo mínimo al iniciar clases (#127) |
| M1.2.campos: asignación | Docente, aula o enlace virtual, horario | Admin → Oferta académica → Grupos (Horarios); Admin → Docencia → Docentes (Carga académica) | Cumple | Choques de aula bloqueados por trigger (#22) |
| M1.3.a | Interfaz para que el estudiante se matricule de forma autónoma | Portal del estudiante → Matrícula | Cumple | — |
| M1.3.b | Bloqueos: ciclo previo/nivel, pago no validado, grupo sin vacantes | Portal del estudiante → Matrícula (requisitos) | Cumple | Prerrequisito = ciclo habilitado por idioma (#10, #15) |
| M1.3.c | Generación automática de la Ficha de Matrícula en PDF | Portal → Mis matrículas (Ficha); Admin → Matrícula → Matrículas | Cumple | Una ficha por estudiante y periodo, con QR (#96) |
| M1.3.campos: inscripción | ID estudiante, grupo, fecha y hora de matrícula | Admin → Matrícula → Matrículas | Cumple | — |
| M1.3.campos: pago | Estado de pago (Verificado/Pendiente) según Pagos | Portal → Matrícula; Admin → Matrícula → Matrículas (pago) | Cumple | El estado se toma del pago validado en Pagos y la ficha lo muestra como «Verificado»; mientras no se valide, el portal lista el pago pendiente como requisito y no permite matricularse |
| M1.3.campos: condición | Condición Regular o Repitente | Admin → Matrícula → Matrículas; Ficha | Cumple | Criterio de repitente (#16) |
| M1.3.campos: documentos | Ficha de Matrícula (PDF), Constancia de Inscripción | Portal → Mis matrículas | Cumple | Constancia gratuita e inmediata, sin QR (#29, #30) |
| M1.4.a | Cambiar alumno de grupo/horario por motivos excepcionales | Admin → Matrícula → Matrículas (Trasladar) | Cumple | Mismo periodo y ciclo (#32) |
| M1.4.b | Registro de retiros oficiales del ciclo | Admin → Matrícula → Matrículas (Retirar) | Cumple | Retirados no figuran en el acta final (#58) |
| M1.4.campos | Grupo origen/destino, fecha, motivo, usuario que autoriza | Admin → Matrícula → Matrículas (movimientos) | Cumple | Movimientos inmutables por trigger |

### 11.3.3. Módulo 2: Gestión y validación de pagos

*Tabla 27. Matriz de cumplimiento: Módulo 2: Gestión y validación de pagos*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M2.1.a | Registro de pagos (matrícula, pensión, clasificación, certificados) | Admin → Pagos → Pagos (Registrar pago); Portal → Registro de pagos | Cumple | — |
| M2.1.b | Validación por administrador o cajero con recibo digital JPG/PNG/PDF | Admin → Pagos → Pagos (Validar / Rechazar) | Cumple | Tipo detectado por contenido, huella SHA-256, separación de funciones (#117, #118); el cajero confirma además la boleta original (#114) |
| M2.1.c | Desbloqueo automático de matrícula, constancias y exámenes al validar | Portal → Matrícula / Clasificación / Trámites | Cumple | Constancias y certificados se desbloquean vía trámites (#75) |
| M2.1.d | Auditoría de qué usuario validó cada pago y en qué fecha | Admin → Pagos → Pagos (ficha); Admin → Seguridad → Auditoría | Cumple | — |
| M2.1.campos: identificador | Número de recibo / código de operación | Admin → Pagos → Pagos | Cumple | Único por medio entre pagos no rechazados (#14, #116) |
| M2.1.campos: pagador | Tipo y número de documento del pagador | Admin → Pagos → Pagos (búsqueda por documento) | Cumple | El documento se toma de la ficha de la persona |
| M2.1.campos: financiero | Monto total, fecha de pago, medio de pago | Admin → Pagos → Pagos; Admin → Catálogos → Medios de pago | Cumple | Medios como catálogo (#119) |
| M2.1.campos: concepto | Código del concepto TUPA/TUSNE | Admin → Catálogos → Conceptos y tarifas; Fuentes TUPA/TUSNE | Cumple | Tarifas por concepto y vínculo (#12, #120) |
| M2.1.campos: evidencia | Recibo escaneado, estado, usuario y fecha de validación | Admin → Pagos → Pagos (ficha y recibo) | Cumple | Se agrega estado "Anulado" (#13) |
| M2.2.a | Reportes consolidados por periodo, idioma, nivel y concepto | Admin → Pagos → Reporte de recaudación | Cumple | Periodo = rango de fechas de pago más filtro de periodo académico (#37) |
| M2.2.b | Exportación PDF y/o hoja de cálculo (Excel/CSV) para DGF/Tesorería | Admin → Pagos → Reporte de recaudación (PDF, CSV) | Cumple | Exportación en PDF y CSV (UTF-8, se abre directamente en Excel) (#38) |
| M2.2.campos | Periodo, concepto, monto total, N.° operaciones, detalle por estudiante | Admin → Pagos → Reporte de recaudación | Cumple | Montos sumados en céntimos enteros |

### 11.3.4. Módulo 3: Gestión docente y reportes de carga académica

*Tabla 28. Matriz de cumplimiento: Módulo 3: Gestión docente y reportes de carga académica*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M3.1.a | Padrón de docentes con idiomas y disponibilidad horaria | Admin → Docencia → Docentes (Disponibilidad horaria) | Cumple | — |
| M3.1.b | Estado del docente: Activo, Inactivo, De Licencia | Admin → Docencia → Docentes | Cumple | Un docente con grupos a cargo no pasa a Inactivo ni a De licencia hasta que se reasignen sus grupos (#48) |
| M3.1.campos: personales | ID docente, nombres, DNI/CE, correo institucional y personal, celular | Admin → Docencia → Docentes | Cumple | Datos personales desde Personas (#39) |
| M3.1.campos: expediente | RUC, cuenta bancaria, banco, condición laboral | Admin → Docencia → Docentes; Admin → Catálogos → Condiciones laborales | Cumple | Condición laboral como catálogo editable, con Nombrado, Contratado y Tercero cargados de inicio; cuenta bancaria cifrada y enmascarada (#122) |
| M3.1.campos: académico | Idiomas habilitados, nivel máximo autorizado | Admin → Docencia → Docentes | Cumple | Nivel máximo por idioma (#40) |
| M3.2.a | Vincular docente con grupos/ciclos aperturados | Admin → Docencia → Docentes → Carga académica | Cumple | — |
| M3.2.b | Cálculo automático del total de horas asignadas en el periodo | Admin → Docencia → Docentes → Carga académica; Admin → Docencia → Carga docente | Cumple | Horas = sesiones programadas del periodo (#42) |
| M3.2.c | Control de cruces de horarios del docente | Admin → Docencia → Docentes → Carga académica | Cumple | Regla en la acción y en el trigger de restricción (#41, #43) |
| M3.2.d | Llamado de asistencia y registro de pre-actas según cronograma | Portal docente → Mis grupos → Asistencia / Pre-acta | Cumple | Dos llamados y confirmación del delegado (ADR 0009; #45, #46, #98) |
| M3.2.campos: grupo | Código, nivel, idioma del grupo | Admin → Docencia → Docentes → Carga académica | Cumple | — |
| M3.2.campos: carga | Semanas, fechas de inicio y fin, días de dictado, horas totales | Admin → Docencia → Docentes → Carga académica | Cumple | — |
| M3.3.a | Consolidar horas dictadas × costo por hora: monto bruto referencial | Admin → Docencia → Expediente de horas; Costos por hora | Cumple | Costo por grado académico (#49 resuelto; #50, #51) |
| M3.3.b | Reporte por docente en PDF y/o Excel/CSV para DGF/Tesorería | Admin → Docencia → Expediente de horas (PDF, CSV) | Cumple | — |
| M3.3.c | No calcula retenciones ni procesa el pago | Admin → Docencia → Expediente de horas | Cumple | Sin cálculo de retenciones en el código |
| M3.3.campos | Mes y año; nombres, idioma/nivel, horas, costo/hora, monto bruto | Admin → Docencia → Expediente de horas | Cumple | — |

### 11.3.5. Módulo 4: Evaluación y certificación digital

*Tabla 29. Matriz de cumplimiento: Módulo 4: Evaluación y certificación digital*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M4.1.a | Interfaz exclusiva del docente asignado, solo en fechas del calendario | Portal docente → Mis grupos → Pre-acta / Acta final | Cumple | Solo el docente asignado al grupo. Las notas parciales se aceptan desde la fecha de inicio del periodo hasta su última fecha de actas (hora de Lima) y nunca después de existir el acta del grupo; fuera de ese plazo la pre-acta queda de solo lectura con un aviso. El acta final, en el rango «actas desde/hasta» del periodo (#55) |
| M4.1.b | Cálculo automático de promedios (Parcial, Final, Participación) | Portal docente → Acta final; Admin → Catálogos → Sistemas de evaluación | Cumple | Según el sistema de evaluación del CILN, como lo indicó el área usuaria: promedio simple de las cuatro habilidades y promedio simple de los exámenes parcial y final; nota final igual al promedio simple de ambos, redondeada al entero (0,5 hacia arriba) solo al final; cada sistema de evaluación configura sus componentes, la nota máxima y la nota mínima aprobatoria (11 por defecto). El Anexo menciona «Participación», pero remite al sistema del CILN (#56, #121) |
| M4.1.c | Cierre y bloqueo; modificación con autorización del Administrador y en auditoría | Admin → Certificación → Actas finales (autorizar rectificación) | Cumple | Triggers `cer_actas_bloqueo`, `cer_notas_finales_bloqueo` (#59) |
| M4.1.campos: curso | Idioma, nivel, ciclo, periodo académico | Admin → Certificación → Actas finales | Cumple | — |
| M4.1.campos: docente | ID y nombres completos del docente | Admin → Certificación → Actas finales; PDF del acta | Cumple | Se muestra el código `DOC-00001` |
| M4.1.campos: calificaciones | ID estudiante, notas parciales, promedio, estado Aprobado/Desaprobado/NSP | Admin → Certificación → Actas finales | Cumple | — |
| M4.2.a | Emisión masiva o individual de constancias y certificados de nivel | Admin → Certificación → Constancias y certificados; Actas finales | Cumple | Emisión individual de cada documento y emisión masiva: constancias de matrícula por grupo («Constancias de matrícula del grupo»), constancias de notas por acta y certificados por periodo (#63, #64, #65) |
| M4.2.b | PDF estandarizado con diseño institucional | Admin → Certificación → Constancias y certificados (PDF) | Cumple | Firmantes y numeración anual; firma digital no exigida (#69) |
| M4.2.c | QR único que lleva al portal oficial de verificación | Sitio público → Verificar un documento (`/verificar/{código}`) | Cumple | Con límite de 30 consultas/min; datos mínimos (#62, #67) |
| M4.2.campos: documento | Código único, fecha de emisión, código QR | Admin → Certificación → Constancias y certificados | Cumple | — |
| M4.2.campos: acreditación | Nombres, idioma, nivel, horas lectivas, nota final | Admin → Certificación → Constancias y certificados | Cumple | — |

### 11.3.6. Módulo 5: Tablero de control gerencial

*Tabla 30. Matriz de cumplimiento: Módulo 5: Tablero de control gerencial*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M5.1.a | Ingresos recaudados por periodo, idioma y nivel | Admin → Tablero gerencial | Cumple | Atribución de pagos (#80) |
| M5.1.b | Tasa de morosidad o pagos pendientes de validación | Admin → Tablero gerencial | Cumple | Indicador de pendientes de validación (#4) |
| M5.1.c | Rentabilidad básica por grupo (ingresos vs. costo docente) | Admin → Tablero gerencial | Cumple | (#81) |
| M5.2.a | Número de matrículas y crecimiento mes a mes / año a año | Admin → Tablero gerencial | Cumple | (#82) |
| M5.2.b | Tasa de deserción por nivel y por idioma | Admin → Tablero gerencial | Cumple | (#83) |
| M5.2.c | Distribución de la demanda horaria | Admin → Tablero gerencial | Cumple | (#84) |
| M5.3.a | Tablero único con gráficos, tablas, filtros y exportación PDF/Excel | Admin → Tablero gerencial (Exportar PDF, Excel) | Cumple | Filtros periodo/idioma/nivel; Excel .xlsx (maatwebsite/excel); tiempo real sin caché (#85) |

### 11.3.7. Módulo 6: Portal público y trazabilidad de trámites

*Tabla 31. Matriz de cumplimiento: Módulo 6: Portal público y trazabilidad de trámites*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M6.1.a | Página pública: oferta, requisitos, fechas, costos TUPA/TUSNE | Sitio público → Portada (`/`) | Cumple | Oferta del periodo con matrícula abierta o del siguiente planificado, costos con la tarifa vigente por vínculo, requisitos de matrícula en cuatro pasos y fechas de matrícula, inicio y fin de clases (#70, #71, #72). El Anexo menciona `https://cid.unasam.edu.pe/`, dominio del sistema anterior; la DSTI designó `https://ciln.unasam.edu.pe/` para el sistema nuevo, donde está desplegado; ninguna dirección está fija en el código (`APP_URL`) |
| M6.1.b | Inicio de sesión diferenciado para estudiantes y docentes | Sitio público → Ingresar (`/ingresar`) → Portal del estudiante o Portal docente | Cumple | Un solo punto de acceso que lleva a cada usuario a su propio portal según su rol: portal del estudiante, panel docente o panel del personal (#73) |
| M6.1.c | Recuperación de credenciales con token al correo registrado | Sitio público → Recuperar clave (`/recuperar-clave`, `/restablecer-clave/{token}`) | Cumple | Correo de recuperación recibido en producción el 18/11/2026, enviado desde la cuenta institucional `sistemacln@unasam.edu.pe` |
| M6.2.a | Flujo configurable por trámite (constancia, certificado, traslado, retiro) | Admin → Catálogos → Tipos de trámite; Portal → Trámites | Cumple | (#74, #76) |
| M6.2.b | Flujo bloqueado hasta que Pagos valide la tasa | Portal → Trámites; Admin → Trámites → Trámites | Cumple | Aplicación automática del pago (#75) |
| M6.2.c | Barra de progreso visible para el usuario | Portal del estudiante → Trámites | Cumple | Etapas con porcentaje configurables; estados fijos (#6) |
| M6.2.d | Notificación automática por correo en cada cambio de estado | Correo al estudiante; Admin → Trámites → Trámites (notificaciones) | Cumple | Por cola, 3 intentos (#79); envío desde la cuenta institucional `sistemacln@unasam.edu.pe`, verificado en producción el 18/11/2026 |
| M6.2.e | Panel para configurar etapas, estados y condiciones sin programar | Admin → Catálogos → Tipos de trámite | Cumple parcialmente | Configurables desde el panel: etapas (nombre, porcentaje y condición), tasa, requisitos y acción final. Los estados del flujo (Pendiente de pago, En proceso, Observado, Finalizado y Cerrado sin atender) y las tres condiciones de etapa (solicitud, pago validado, acción del personal) son fijos por diseño, para mantener coherentes las reglas de pago y de auditoría |
| M6.2.campos: id | ID de trámite (código único de seguimiento) | Portal → Trámites; Admin → Trámites → Trámites | Cumple | (#78) |
| M6.2.campos: estados | Pendiente de Pago, En Proceso, Observado, Finalizado | Admin → Trámites → Trámites | Cumple | Se agrega "Cerrado sin atender" (#134) |
| M6.2.campos: log | Log de notificaciones: fecha, hora, estado de entrega | Admin → Trámites → Trámites (ficha: notificaciones) | Cumple | Registra fecha, hora, destinatario, asunto, estado de entrega (pendiente, enviado, fallido) e intentos; «enviado» significa aceptado por el servidor de correo; no se puede borrar ni vaciar (trigger) |

### 11.3.8. Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)

*Tabla 32. Matriz de cumplimiento: Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| IV.1.a | Cesión total de derechos patrimoniales a la UNASAM | Contractual | Cumple | Cesión formalizada en la carta de cesión de derechos patrimoniales suscrita por el representante legal del contratista, conforme al numeral 6.1 de los TdR (Anexo D) |
| IV.1.b | Entrega de repositorios completos y documentados al finalizar | Repositorio | Cumple | Código fuente con su documentación técnica en la carpeta «Código fuente» de la USB y repositorio completo entregado a la DSTI; manuales de usuario (estudiante, docente y personal del CILN) entregados |
| IV.2.a | Seguridad conforme a la Ley N.° 29733 y OWASP | Transversal | Cumple | Ley N.° 29733 (sección 9.3: política de privacidad en `/privacidad` y consentimiento registrado con fecha y hora) y escaneo OWASP ZAP de la v1.1.1 (sección 9.1) |
| IV.2.b | Log Triggers inmutables: fecha, hora, IP, usuario y detalle | Admin → Seguridad → Auditoría | Cumple | — |
| IV.3.a | Confidencialidad de la información académica, financiera y personal | Proceso del proveedor | Cumple | Compromiso contractual; en lo técnico, pruebas y demostraciones solo con datos sintéticos |
| IV.4.a | Garantía y soporte técnico mínimo de 12 meses | Contractual | Cumple | Compromiso formalizado en la carta de garantía técnica de funcionamiento y soporte (Anexo D); los 12 meses rigen desde la conformidad final |

## 11.4. Atención al Informe Técnico N.° 023-2026-FMAJ

*Tabla 33. Problemas del sistema anterior y solución del sistema nuevo*

| Problema (Informe Técnico N.° 023-2026-FMAJ) | Solución en el sistema nuevo |
|---|---|
| 1.1 Tecnología obsoleta (PHP 5.6 sin soporte desde 2018) | PHP 8.5, Laravel 13 y PostgreSQL 18, todos con soporte vigente; actualizaciones de seguridad aplicadas durante el servicio (secciones 4.1 y 10.2) |
| 1.2 Vulnerabilidades (inyección SQL, ejecución remota, XSS, sesiones inseguras, exposición de datos) y malas prácticas | Controles OWASP Top 10 (9.1); monolito modular con fronteras verificadas por pruebas; separación entre lógica y presentación; manejo centralizado de errores |
| 1.3 Problemas de lógica funcional y limitaciones ante nuevos requerimientos | Reglas de negocio en Acciones únicas, validadas también en la base de datos y cubiertas por 1 588 casos de prueba; catálogos y flujos configurables sin programar |
| 1.4 Diseño no responsive e interfaz poco intuitiva | Interfaz adaptable a móviles, sistema de diseño único y contraste de colores conforme a WCAG AA |
| 1.5 Impacto institucional (pérdida de integridad, interrupción, exposición de datos) | Auditoría inmutable, integridad garantizada por la base de datos, respaldos programados, documentación técnica y despliegue en la infraestructura de la UNASAM |

*Tabla 34. Recomendaciones de la DSTI y su aplicación*

| Recomendación 3.1 | Aplicación en el sistema |
|---|---|
| Tecnologías: PHP 8.x, framework moderno, MVC, API REST, base de datos optimizada, servicios desacoplados | PHP 8.5, Laravel 13, MVC con capa de Acciones, API `/api/v1`, PostgreSQL 18 normalizado con índices, módulos desacoplados por contratos (capítulos 4 y 5) |
| Seguridad: OWASP Top 10, validación de entradas, sesiones seguras, CSRF/XSS/SQLi, cifrado, auditoría y trazabilidad | Capítulo 9; requisitos II.2.a a II.2.e |
| UX/UI: responsive, intuitiva, accesible, dinámica, adaptable a móviles | Portales con Livewire y Tailwind, paneles con Filament, contraste WCAG AA (requisito II.3.a) |
| Arquitectura escalable: integraciones, crecimiento, interoperabilidad, alta disponibilidad | API REST, colas, módulos independientes y despliegue con contenedores (requisito II.4.a) |

## 11.5. Relación de componentes entregados

*Tabla 35. Componentes del entregable*

| Componente | Base contractual | Estado |
|---|---|---|
| Sistema desplegado y operativo en el servidor de la DSTI, en el dominio oficial `https://ciln.unasam.edu.pe` (versión v1.1.1) | TdR 4.2, 4.5 y 6.3 | Entregado |
| Código fuente de la versión v1.1.1 y repositorio completo con su historial | TdR 6.1; Anexo IV.1 | Entregado: código fuente en la carpeta «Código fuente» de la USB; repositorio completo, a la DSTI |
| Scripts de implementación (instalación tradicional y con contenedores, actualización automática con reversión, migraciones) | TdR 6.1 | Entregado, en la carpeta «Código fuente» (`deploy/` y `docker/`) |
| Documentación técnica (presente informe, arquitectura, modelo de datos, seguridad, guía de despliegue, ADR, documentación OpenAPI de la API) | TdR 4.2, 4.5 y 6.3 | Entregado |
| Diccionario de Datos – Sistema de Gestión del CILN | TdR 4.2 | Entregado |
| Reporte del escaneo de seguridad OWASP ZAP (versión v1.1.1) | Anexo II.2.a y IV.2 | Entregado |
| Manuales de usuario (estudiante, docente y personal del CILN) | TdR 4.2 y 6.1 | Entregado |
| Capacitación al personal directivo, docente y administrativo del CILN (16/11/2026) y capacitación práctica a docentes y estudiantes (17 y 18/11/2026) | TdR 4.2 y 6.1 | Realizada (Anexo C) |
| Carta de cesión de derechos patrimoniales | TdR 6.1; Anexo IV.1 | Entregada (Anexo D) |
| Garantía técnica de funcionamiento y soporte por 12 meses | TdR 6.1 y 6.10; Anexo IV.4 | Carta de garantía entregada (Anexo D); rige desde la conformidad final |

## 11.6. Declaraciones del contratista

ELECTRODATA INGENIERIA & SISTEMAS S.A.C. declara, conforme a los Términos de Referencia y a su Anexo Técnico:

- **Cesión de derechos.** Cede a la UNASAM, en forma total, exclusiva y a perpetuidad, los derechos patrimoniales del software, el diseño de la base de datos, el código fuente, los manuales y los scripts de implementación, mediante la carta de cesión de derechos patrimoniales (TdR 6.1; Anexo IV.1; Anexo D).
- **Garantía técnica.** Se compromete a brindar la garantía técnica de funcionamiento y el soporte (resolución de errores, atención de incidencias y asistencia en el uso al personal del CILN y de la DSTI) por doce (12) meses contados desde la conformidad final del sistema (TdR 6.1 y 6.10; Anexo IV.4), según la carta de garantía técnica (Anexo D).
- **Vicios ocultos.** Asume la responsabilidad por los vicios ocultos del servicio por un (1) año, conforme al numeral 6.9 de los TdR; este compromiso consta en la carta de garantía técnica (Anexo D).
- **Ejecución directa.** Prestó el servicio directamente, sin subcontratar ninguna de sus prestaciones (TdR 6.1).
- **Confidencialidad.** Mantiene reserva absoluta sobre la información académica, financiera y personal de la Entidad a la que tuvo acceso (TdR 6.5; Anexo IV.3), según la sección «Uso de la información».
- **Anticorrupción y antisoborno.** No ofreció, otorgó ni ofrecerá, directa o indirectamente, pagos, dádivas ni otros beneficios indebidos a funcionarios o servidores de la Entidad en relación con el servicio (TdR 6.11).
- **Obligaciones de la Entidad.** La UNASAM, a través de la DSTI, brindó el acceso al servidor de producción y la contraparte técnica requeridos para el despliegue (TdR 6.2).

# 12. CONCLUSIONES

1. El Sistema de Gestión Académica y Administrativa del CILN **atiende los seis módulos funcionales** del Anexo Técnico: cada uno de los 78 requisitos funcionales tiene una pantalla o un servicio que lo atiende y pruebas automatizadas que lo comprueban; 77 se cumplen y uno (M6.2.e) se cumple parcialmente porque los estados de los trámites y las condiciones de etapa son fijos por diseño.
2. De los 96 requisitos verificados del Anexo Técnico, **95 se cumplen y 1 se cumple parcialmente**: la cesión de derechos y la garantía técnica quedan formalizadas mediante cartas suscritas por el contratista (Anexo D), y el único requisito parcial (M6.2.e) responde a la decisión de diseño de mantener fijos los estados de los trámites. **Ningún requisito queda sin atender.**
3. El servicio se ejecutó dentro del plazo de la Orden de Servicio N.° 0003091, emitida el 20 de octubre de 2026 y notificada el 23 de octubre de 2026, cuyo plazo corre del 24 de octubre al 22 de noviembre de 2026, y atendió las seis actividades del numeral 4.2 de los TdR, incluidos el levantamiento de requerimientos con el área usuaria y la capacitación al personal directivo, docente y administrativo del CILN y la capacitación práctica de docentes y estudiantes (Anexo C); el sistema se encuentra desplegado en `https://ciln.unasam.edu.pe`, envía sus correos desde la cuenta institucional `sistemacln@unasam.edu.pe`, y el código fuente se entrega en la USB que acompaña al presente informe, con el repositorio completo en poder de la DSTI; los manuales de usuario del estudiante, del docente y del personal del CILN fueron entregados.
4. El sistema supera las limitaciones señaladas por la DSTI en el Informe Técnico N.° 023-2026-FMAJ: usa tecnologías con soporte vigente, aplica los controles del OWASP Top 10, registra una pista de auditoría inmutable en la base de datos y protege los datos personales conforme a la Ley N.° 29733.
5. El cálculo de las notas sigue el sistema de evaluación del CILN, como lo indicó el área usuaria: promedio de las cuatro habilidades y promedio de los exámenes, con la nota final redondeada a un entero y una nota mínima aprobatoria configurable.

# 13. RECOMENDACIONES

1. **Correo y respaldos.** Que la DSTI mantenga habilitada la cuenta `sistemacln@unasam.edu.pe` y la configuración de los respaldos programados del servidor de producción, que realice una prueba de restauración de los respaldos al menos una vez por semestre y que, si cambia la política de autenticación del servicio de correo institucional, aplique la configuración alternativa descrita en la guía de despliegue.
2. **Seguridad.** Que la DSTI repita el escaneo OWASP ZAP sobre el servidor de producción `https://ciln.unasam.edu.pe` de forma periódica y después de instalar cada nueva versión, mantenga activo el módulo `mod_headers` de Apache, active el doble factor obligatorio para el personal del panel (`PANEL_DOBLE_FACTOR_OBLIGATORIO=true`) y mantenga la conexión de la aplicación con un rol distinto del dueño del esquema.
3. **Garantía.** Que, con la conformidad final, se registre la fecha de inicio de la garantía técnica de doce meses; el canal de reporte de incidencias consta en la carta de garantía (Anexo D).
4. **Actualizaciones.** Que las nuevas versiones se instalen solo desde **etiquetas publicadas** (`vX.Y.Z`) de la rama principal, que pasan la integración continua, mediante la actualización automática o el procedimiento manual de la guía de despliegue, y que la DSTI mantenga las dependencias dentro de sus versiones con soporte.
5. **Datos institucionales.** Que el CILN mantenga actualizados en el panel los datos institucionales que cambian con el tiempo (tarifas TUPA/TUSNE, firmantes, denominación del año, sistemas de evaluación y roles del personal), y que el personal capacitado replique la capacitación, con los manuales de usuario, al personal y a los docentes que se incorporen.
6. **Trámites.** Que, si el CILN requiere estados de trámite adicionales a los previstos, los solicite como mejora, para evaluarla con la DSTI sin afectar las reglas de pago y de auditoría.

# ANEXO A. EVIDENCIA TÉCNICA POR REQUISITO

Rutas del código fuente que implementan cada requisito y pruebas automatizadas que lo comprueban. Las rutas son relativas a la carpeta raíz del código fuente de la versión v1.1.1 (sección 1.7).


**Requisitos técnicos generales (sección II del Anexo)**

*Tabla 36. Evidencia técnica: Requisitos técnicos generales (sección II del Anexo)*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| II.1.a | `composer.json` (`"php": "^8.5"`), `Dockerfile` | `tests/Architecture/ModulesTest.php` (suite completa corre sobre PHP 8.5) |
| II.1.b | `bootstrap/providers.php`, `app/Shared/Support/ModuleServiceProvider.php` | `tests/Architecture/ModulesTest.php`, `tests/Architecture/TransaccionesTest.php` |
| II.1.c | `routes/api.php`, `app/Modules/*/routes/api.php` | `tests/Feature/Api/ServiciosPublicosTest.php`, `tests/Feature/Api/DatosDelEstudianteTest.php` |
| II.1.d | `app/Modules/*/Database/Migrations/`, `app/Modules/Matricula/Database/Migrations/2026_10_24_161000_index_matricula_foreign_keys.php` | `tests/Feature/Database/IndicesTest.php`, `tests/Modules/Academico/SchemaConstraintsTest.php` |
| II.1.e | `docs/despliegue.md`, `Dockerfile`, `docker/` | `tests/Feature/HealthTest.php`, `tests/Feature/ProxiesDeConfianzaTest.php` |
| II.2.a | `docs/seguridad.md`, `app/Shared/Support/Http/Middleware/` (cabeceras de seguridad) | `tests/Feature/CabecerasDeSeguridadTest.php`, `tests/Feature/Auth/PanelAuthorizationTest.php` |
| II.2.b | Form Requests y Actions de cada módulo; consultas parametrizadas de Eloquent; middleware CSRF de Laravel | `tests/Feature/Portal/SeguridadDelPortalTest.php`, `tests/Modules/Tramites/PortadaPublicaTest.php` |
| II.2.c | `config/session.php` (cifrado, HttpOnly, SameSite), `app/Shared/Auth/Http/Middleware/`, `app/Modules/Docencia/Models/Docente.php` (cuenta bancaria cifrada) | `tests/Feature/Auth/SesionDelPanelTest.php`, `tests/Feature/Auth/DobleFactorDelPanelTest.php` |
| II.2.d | `app/Modules/Personas/Actions/RegistrarCuentaDeEstudiante.php` (`tratamiento_aceptado_en`), `app/Modules/Personas/Models/Persona.php` (`registrarConsentimiento`), `app/Modules/Personas/resources/views/privacidad.blade.php`, `app/Modules/Matricula/Http/Controllers/VerFichaPorCodigo.php` | `tests/Modules/Personas/Portal/RegistroTest.php`, `tests/Modules/Personas/Portal/PoliticaDePrivacidadTest.php`, `tests/Modules/Personas/Filament/PersonasPanelTest.php`, `tests/Modules/Matricula/Portal/FichaPublicaTest.php` |
| II.2.e | `app/Shared/Audit/Database/Migrations/2026_10_24_125000_create_aud_registros_table.php`, `app/Shared/Audit/Http/Middleware/` | `tests/Feature/Audit/AuditTrailTest.php`, `tests/Feature/Auditoria/TablasAuditadasTest.php` |
| II.3.a | `resources/views/components/layouts/portal.blade.php`, `app/Providers/Filament/AparienciaDelPanel.php` | `tests/Unit/PaletaCilnTest.php` (paleta del panel igual a los tokens de diseño), `tests/Feature/Portal/MenuDelPortalTest.php` |
| II.4.a | `app/Modules/*/Contracts/`, `routes/api.php`, colas (`database/migrations/0001_01_01_000002_create_jobs_table.php`) | `tests/Architecture/ModulesTest.php`, `tests/Feature/Api/SesionesDeApiTest.php` |

**Módulo 1: Registro, examen de clasificación y matrícula**

*Tabla 37. Evidencia técnica: Módulo 1: Registro, examen de clasificación y matrícula*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M1.1.a | `app/Modules/Personas/Actions/RegistrarCuentaDeEstudiante.php`, `app/Modules/Personas/Livewire/Registro.php` | `tests/Modules/Personas/Portal/RegistroTest.php`, `tests/Feature/Portal/RegistroYCredencialesTest.php` |
| M1.1.b | `app/Modules/Matricula/Actions/SolicitarExamenDeClasificacion.php`, `app/Modules/Matricula/Filament/Resources/SolicitudesClasificacion/SolicitudClasificacionResource.php` | `tests/Modules/Matricula/ExamenDeClasificacionTest.php`, `tests/Modules/Matricula/Filament/SolicitudesClasificacionPanelTest.php` |
| M1.1.c | `app/Modules/Matricula/Actions/RegistrarResultadoDeClasificacion.php`, `app/Modules/Matricula/Actions/CorregirResultadoDeClasificacion.php` | `tests/Modules/Matricula/ClasificacionTest.php`, `tests/Modules/Matricula/CorreccionDeClasificacionTest.php` |
| M1.1.campos: postulante | `app/Modules/Personas/Database/Migrations/2026_10_24_110000_create_per_personas_table.php`, `app/Modules/Personas/Filament/Resources/Personas/PersonaResource.php` | `tests/Modules/Personas/PersonaTest.php`, `tests/Modules/Personas/Portal/RegistroTest.php` |
| M1.1.campos: vínculo | `per_personas.tipo_vinculo`, `app/Modules/Pagos/Actions/RegistrarTarifa.php` | `tests/Modules/Pagos/TarifaTest.php`, `tests/Modules/Personas/PersonaTest.php` |
| M1.1.campos: examen | `app/Modules/Matricula/Database/Migrations/2026_10_24_120000_create_mat_clasificacion_and_habilitaciones_tables.php` | `tests/Modules/Matricula/ClasificacionTest.php` |
| M1.1.campos: estado | `app/Modules/Matricula/Enums/EstadoSolicitudClasificacion.php`, `app/Modules/Matricula/Actions/ExpirarSolicitudesDeClasificacion.php` | `tests/Modules/Matricula/ExamenDeClasificacionTest.php`, `tests/Concurrency/ClasificacionConcurrenteTest.php` |
| M1.2.a | `app/Modules/Academico/Actions/GuardarGrupo.php`, `app/Modules/Academico/Filament/Resources/Grupos/GrupoResource.php` | `tests/Modules/Academico/GruposTest.php`, `tests/Modules/Academico/Filament/GruposPanelTest.php` |
| M1.2.campos: ciclo | `app/Modules/Academico/Database/Migrations/2026_10_24_100100_create_aca_periodos_table.php`, `app/Modules/Academico/Actions/CambiarEstadoPeriodo.php` | `tests/Modules/Academico/Filament/PeriodosTest.php`, `tests/Modules/Academico/CambiarEstadoPeriodoTest.php` |
| M1.2.campos: grupo | `app/Modules/Academico/Database/Migrations/2026_10_24_100000_create_aca_idiomas_and_ciclos_tables.php`, `app/Modules/Academico/Actions/GuardarCiclo.php` | `tests/Modules/Academico/CiclosTest.php`, `tests/Modules/Academico/GruposTest.php` |
| M1.2.campos: capacidad | `app/Modules/Academico/Actions/ReservarVacante.php`, `app/Modules/Academico/Actions/CambiarEstadoGrupo.php` | `tests/Modules/Academico/OfertaDeMatriculaTest.php`, `tests/Concurrency/MatriculaConcurrenteTest.php` |
| M1.2.campos: asignación | `app/Modules/Academico/Actions/GuardarHorario.php`, `app/Modules/Docencia/Actions/AsignarDocente.php` | `tests/Modules/Academico/HorariosDelGrupoTest.php`, `tests/Modules/Academico/GuardaDeCrucesEnLaBaseTest.php` |
| M1.3.a | `app/Modules/Matricula/Livewire/Matricularse.php`, `app/Modules/Matricula/Actions/MatricularEstudiante.php` | `tests/Modules/Matricula/Portal/MatricularseTest.php`, `tests/Modules/Matricula/MatricularEstudianteTest.php` |
| M1.3.b | `app/Modules/Matricula/Actions/MatricularEstudiante.php`, `app/Modules/Pagos/Actions/VerificarPagoParaServicio.php` | `tests/Modules/Matricula/MatricularEstudianteTest.php`, `tests/Modules/Matricula/Portal/RequisitosDeMatriculaTest.php` |
| M1.3.c | `app/Modules/Matricula/Actions/GenerarFichaDelPeriodo.php`, `app/Modules/Matricula/Http/Controllers/DescargarFichaDeMatricula.php` | `tests/Modules/Matricula/Portal/DocumentosDeMatriculaTest.php`, `tests/Modules/Matricula/Portal/FichaPublicaTest.php` |
| M1.3.campos: inscripción | `app/Modules/Matricula/Database/Migrations/2026_10_24_120100_create_mat_matriculas_and_movimientos_tables.php` | `tests/Modules/Matricula/MatriculaTest.php` |
| M1.3.campos: pago | `mat_matriculas.pago_id`, `app/Modules/Pagos/Contracts/` (`VerificacionDePago`) | `tests/Modules/Matricula/PagoVinculadoTest.php`, `tests/Modules/Matricula/PagoDeClasificacionYMatriculaTest.php` |
| M1.3.campos: condición | `app/Modules/Matricula/Enums/CondicionMatricula.php`, `app/Modules/Matricula/Actions/MatricularEstudiante.php` | `tests/Modules/Matricula/MatriculaTest.php` |
| M1.3.campos: documentos | `app/Modules/Matricula/Http/Controllers/DescargarConstanciaDeInscripcion.php`, `app/Modules/Matricula/Actions/GenerarFichaDeMatricula.php` | `tests/Modules/Matricula/Portal/DocumentosDeMatriculaTest.php` |
| M1.4.a | `app/Modules/Matricula/Actions/TrasladarMatricula.php` | `tests/Modules/Matricula/TrasladosYRetirosTest.php`, `tests/Concurrency/TrasladoConcurrenteTest.php` |
| M1.4.b | `app/Modules/Matricula/Actions/RetirarMatricula.php` | `tests/Modules/Matricula/TrasladosYRetirosTest.php`, `tests/Modules/Matricula/VerificacionDeTrasladosTest.php` |
| M1.4.campos | `mat_movimientos`; `app/Modules/Matricula/Database/Migrations/2026_10_24_140100_audit_enrollments_and_freeze_movements.php` | `tests/Modules/Matricula/TrasladosYRetirosTest.php`, `tests/Modules/Matricula/PermisosYAuditoriaTest.php` |

**Módulo 2: Gestión y validación de pagos**

*Tabla 38. Evidencia técnica: Módulo 2: Gestión y validación de pagos*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M2.1.a | `app/Modules/Pagos/Actions/RegistrarPago.php`, `app/Modules/Pagos/Livewire/MisPagos.php` | `tests/Modules/Pagos/RegistrarPagoTest.php`, `tests/Modules/Pagos/Filament/RegistroDePagoTest.php` |
| M2.1.b | `app/Modules/Pagos/Actions/ValidarPago.php`, `app/Modules/Pagos/Actions/RechazarPago.php` | `tests/Modules/Pagos/ValidacionPagoTest.php`, `tests/Modules/Pagos/ValidacionConBoletaOriginalTest.php` |
| M2.1.c | `app/Modules/Pagos/Actions/VerificarPagoParaServicio.php`, `app/Modules/Tramites/Listeners/AlValidarPago.php` | `tests/Modules/Matricula/PagoDeClasificacionYMatriculaTest.php`, `tests/Modules/Tramites/PagoDeTramitesTest.php` |
| M2.1.d | `pag_pagos.validado_por/validado_en`, trigger `pag_pagos_auditoria` | `tests/Modules/Pagos/CicloDeVidaPagoTest.php`, `tests/Feature/Audit/AuditTrailTest.php` |
| M2.1.campos: identificador | `app/Modules/Pagos/Database/Migrations/2026_10_24_130100_create_pag_pagos_table.php` | `tests/Modules/Pagos/PagoTest.php` |
| M2.1.campos: pagador | `pag_pagos.persona_id` → `per_personas` | `tests/Modules/Pagos/Filament/PagoResourceTest.php` |
| M2.1.campos: financiero | `pag_pagos`, `app/Modules/Pagos/Filament/Resources/MediosDePago/MedioDePagoResource.php` | `tests/Modules/Pagos/Filament/MediosDePagoPanelTest.php`, `tests/Modules/Pagos/RegistrarPagoTest.php` |
| M2.1.campos: concepto | `app/Modules/Pagos/Filament/Resources/Conceptos/ConceptoResource.php`, `app/Modules/Pagos/Actions/ActualizarConcepto.php` | `tests/Modules/Pagos/ConfiguracionDePagosTest.php`, `tests/Modules/Pagos/TipoDeConceptoConPagosTest.php` |
| M2.1.campos: evidencia | `pag_pagos.recibo_path/estado/validado_por/validado_en`, trigger `pag_pagos_ciclo_de_vida` | `tests/Modules/Pagos/CicloDeVidaPagoTest.php`, `tests/Modules/Pagos/Portal/ReciboDelPortalTest.php` |
| M2.2.a | `app/Modules/Pagos/Actions/GenerarReporteDeRecaudacion.php`, `app/Modules/Pagos/Filament/Pages/ReporteDeRecaudacion.php` | `tests/Modules/Pagos/ReporteDeRecaudacionTest.php`, `tests/Modules/Pagos/Filament/ReporteDeRecaudacionPanelTest.php` |
| M2.2.b | `app/Modules/Pagos/Actions/ExportarRecaudacionPdf.php`, `app/Modules/Pagos/Actions/ExportarRecaudacionCsv.php` | `tests/Modules/Pagos/ReporteDeRecaudacionTest.php`, `tests/Modules/Pagos/VerificacionDeRecaudacionTest.php` |
| M2.2.campos | `app/Modules/Pagos/Actions/GenerarReporteDeRecaudacion.php` | `tests/Modules/Pagos/ReporteDeRecaudacionTest.php` |

**Módulo 3: Gestión docente y reportes de carga académica**

*Tabla 39. Evidencia técnica: Módulo 3: Gestión docente y reportes de carga académica*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M3.1.a | `app/Modules/Docencia/Actions/GuardarDocente.php`, `app/Modules/Docencia/Actions/GuardarDisponibilidad.php` | `tests/Modules/Docencia/PadronDocenteTest.php`, `tests/Modules/Docencia/DisponibilidadDocenteTest.php` |
| M3.1.b | `app/Modules/Docencia/Actions/CambiarEstadoDocente.php` | `tests/Modules/Docencia/PadronDocenteTest.php`, `tests/Modules/Docencia/Filament/DocentesPanelTest.php` |
| M3.1.campos: personales | `app/Modules/Docencia/Database/Migrations/2026_10_24_150000_create_doc_docentes_tables.php` (código `DOC-00001`) | `tests/Modules/Docencia/PadronDocenteTest.php` |
| M3.1.campos: expediente | `doc_docentes`, `app/Modules/Docencia/Filament/Resources/CondicionesLaborales/CondicionLaboralResource.php` | `tests/Modules/Docencia/PadronDocenteTest.php`, `tests/Modules/Docencia/CatalogosDocentesTest.php` |
| M3.1.campos: académico | `doc_docente_idiomas.nivel_maximo` | `tests/Modules/Docencia/PadronDocenteTest.php` |
| M3.2.a | `app/Modules/Docencia/Actions/AsignarDocente.php`, `app/Modules/Docencia/Filament/Resources/Docentes/RelationManagers/CargasRelationManager.php` | `tests/Modules/Docencia/CargaAcademicaTest.php`, `tests/Modules/Docencia/Filament/CargaAcademicaPanelTest.php` |
| M3.2.b | `app/Modules/Docencia/Actions/ResumirCargaDelDocente.php`, `app/Modules/Docencia/Data/CargaHoraria.php` | `tests/Modules/Docencia/CargaAcademicaTest.php`, `tests/Modules/Tablero/Reportes/ReportesGeneralesTest.php` |
| M3.2.c | `app/Modules/Docencia/Support/ReglasDeCargaDocente.php`, `app/Modules/Docencia/Database/Migrations/2026_11_01_110100_guard_teacher_clashes_across_overlapping_periods.php` | `tests/Concurrency/CargaDocenteConcurrenteTest.php`, `tests/Modules/Academico/CrucesEntrePeriodosTest.php` |
| M3.2.d | `app/Modules/Docencia/Actions/RegistrarAsistencia.php`, `app/Modules/Docencia/Actions/RegistrarNotasParciales.php` | `tests/Modules/Docencia/AsistenciaTest.php`, `tests/Modules/Docencia/Panel/PreActaDelGrupoTest.php` |
| M3.2.campos: grupo | `app/Modules/Docencia/Actions/ResumirCargaDelDocente.php` | `tests/Modules/Docencia/Filament/CargaAcademicaPanelTest.php` |
| M3.2.campos: carga | `app/Modules/Docencia/Data/CargaHoraria.php` | `tests/Modules/Docencia/CargaAcademicaTest.php` |
| M3.3.a | `app/Modules/Docencia/Actions/ConsolidarHorasDictadas.php`, `app/Modules/Docencia/Actions/RegistrarCostoPorHora.php` | `tests/Modules/Docencia/ExpedienteDeHorasTest.php`, `tests/Modules/Docencia/CostosPorHoraTest.php` |
| M3.3.b | `app/Modules/Docencia/Actions/ExportarExpedienteDeHorasPdf.php`, `app/Modules/Docencia/Actions/ExportarExpedientesDeHorasCsv.php` | `tests/Modules/Docencia/Filament/ExpedienteDeHorasPanelTest.php` |
| M3.3.c | `app/Modules/Docencia/Actions/ConsolidarHorasDictadas.php` | `tests/Modules/Docencia/ExpedienteDeHorasTest.php` |
| M3.3.campos | `app/Modules/Docencia/Filament/Pages/ExpedienteDeHorasDocentes.php` | `tests/Modules/Docencia/ExpedienteDeHorasTest.php` |

**Módulo 4: Evaluación y certificación digital**

*Tabla 40. Evidencia técnica: Módulo 4: Evaluación y certificación digital*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M4.1.a | `app/Modules/Docencia/Support/VentanaDeNotasParciales.php`, `app/Modules/Docencia/Actions/RegistrarNotasParciales.php`, `app/Modules/Certificacion/Filament/Docente/Pages/ActaFinalDelGrupo.php`, `app/Modules/Certificacion/Actions/CerrarActa.php` | `tests/Modules/Docencia/NotasParcialesTest.php`, `tests/Modules/Docencia/Panel/PreActaDelGrupoTest.php`, `tests/Modules/Certificacion/CerrarActaTest.php`, `tests/Modules/Certificacion/Panel/ActaFinalDelGrupoTest.php` |
| M4.1.b | `app/Modules/Certificacion/Support/PromedioFinal.php`, `app/Modules/Academico/Actions/GuardarEsquemaDeEvaluacion.php` | `tests/Modules/Certificacion/PromedioFinalTest.php`, `tests/Modules/Academico/EsquemasDeEvaluacionTest.php` |
| M4.1.c | `app/Modules/Certificacion/Actions/AutorizarRectificacion.php`, `app/Modules/Certificacion/Actions/RectificarNotasFinales.php` | `tests/Modules/Certificacion/RectificacionDeActaTest.php`, `tests/Concurrency/ActaConcurrenteTest.php` |
| M4.1.campos: curso | `app/Modules/Certificacion/Database/Migrations/2026_10_25_100000_create_cer_actas_tables.php` | `tests/Modules/Certificacion/Filament/ActasPanelTest.php` |
| M4.1.campos: docente | `cer_actas.docente_id/docente_nombre`, `app/Modules/Certificacion/Support/DocenteDelActa.php` | `tests/Modules/Certificacion/CerrarActaTest.php` |
| M4.1.campos: calificaciones | `cer_notas_finales` (notas, promedio, estado_final) | `tests/Modules/Certificacion/PromedioFinalTest.php`, `tests/Modules/Certificacion/CerrarActaTest.php` |
| M4.2.a | `app/Modules/Certificacion/Actions/EmitirCertificadosDelPeriodo.php`, `app/Modules/Certificacion/Actions/EmitirConstanciasDeNotasDelActa.php`, `app/Modules/Certificacion/Actions/EmitirConstanciasDeMatriculaDelGrupo.php` | `tests/Modules/Certificacion/Documentos/EmisionDeDocumentosTest.php`, `tests/Modules/Certificacion/Documentos/EmisionMasivaTest.php`, `tests/Modules/Certificacion/Documentos/EmisionMasivaDeConstanciasDeMatriculaTest.php` |
| M4.2.b | `app/Modules/Certificacion/Actions/GenerarDocumentoPdf.php`, `app/Modules/Certificacion/resources/views/pdf/` | `tests/Modules/Certificacion/Documentos/DocumentosFormalesTest.php`, `tests/Modules/Certificacion/Documentos/FirmantesYNumeracionTest.php` |
| M4.2.c | `app/Shared/Ui/CodigoQr.php`, `app/Modules/Certificacion/Http/Controllers/VerificarDocumento.php` | `tests/Modules/Certificacion/Documentos/DocumentosPdfYVerificacionTest.php`, `tests/Modules/Matricula/Portal/VerificacionDeDocumentosTest.php` |
| M4.2.campos: documento | `app/Modules/Certificacion/Database/Migrations/2026_10_25_120000_create_cer_documentos_table.php` | `tests/Modules/Certificacion/Documentos/DocumentosPdfYVerificacionTest.php` |
| M4.2.campos: acreditación | `cer_documentos` (estudiante_nombre, idioma, nivel, horas_lectivas, nota_final) | `tests/Modules/Certificacion/Documentos/EmisionDeDocumentosTest.php` |

**Módulo 5: Tablero de control gerencial**

*Tabla 41. Evidencia técnica: Módulo 5: Tablero de control gerencial*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M5.1.a | `app/Modules/Tablero/Actions/CalcularRecaudacion.php` | `tests/Modules/Tablero/IndicadoresFinancierosTest.php` |
| M5.1.b | `app/Modules/Tablero/Actions/CalcularPagosPorValidar.php` | `tests/Modules/Tablero/IndicadoresFinancierosTest.php` |
| M5.1.c | `app/Modules/Tablero/Actions/CalcularRentabilidadPorGrupo.php` | `tests/Modules/Tablero/RentabilidadPorGrupoTest.php` |
| M5.2.a | `app/Modules/Tablero/Actions/CalcularMatriculas.php` | `tests/Modules/Tablero/IndicadoresAcademicosTest.php`, `tests/Modules/Tablero/NumerosDePeriodoTest.php` |
| M5.2.b | `app/Modules/Tablero/Actions/CalcularDesercion.php` | `tests/Modules/Tablero/IndicadoresAcademicosTest.php` |
| M5.2.c | `app/Modules/Tablero/Actions/CalcularDemandaHoraria.php` | `tests/Modules/Tablero/IndicadoresAcademicosTest.php` |
| M5.3.a | `app/Modules/Tablero/Filament/Pages/TableroGerencial.php`, `app/Modules/Tablero/Actions/ExportarTableroExcel.php` | `tests/Modules/Tablero/Filament/TableroGerencialTest.php`, `tests/Modules/Tablero/PresentacionDelTableroTest.php` |

**Módulo 6: Portal público y trazabilidad de trámites**

*Tabla 42. Evidencia técnica: Módulo 6: Portal público y trazabilidad de trámites*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| M6.1.a | `app/Modules/Tramites/Livewire/PortadaPublica.php`, `app/Modules/Tramites/Support/ContenidoDePortada.php` | `tests/Modules/Tramites/PortadaPublicaTest.php`, `tests/Modules/Tramites/PortadaEnCacheTest.php` |
| M6.1.b | `app/Shared/Auth/Livewire/IniciarSesion.php`, `app/Shared/Auth/Support/DestinoTrasIngreso.php` | `tests/Feature/Portal/IngresoUnificadoTest.php`, `tests/Feature/Portal/IniciarSesionTest.php` |
| M6.1.c | `app/Shared/Auth/Livewire/SolicitarRestablecimiento.php`, `app/Shared/Auth/Notifications/CorreoDeRestablecimiento.php` | `tests/Feature/Portal/RestablecerClaveTest.php`, `tests/Feature/Portal/CorreosEnColaTest.php` |
| M6.2.a | `app/Modules/Tramites/Actions/GuardarTipoDeTramite.php`, `app/Modules/Tramites/Enums/AccionFinal.php` | `tests/Modules/Tramites/Filament/TiposDeTramitePanelTest.php`, `tests/Modules/Tramites/SolicitudDeTramitesTest.php` |
| M6.2.b | `app/Modules/Tramites/Actions/AplicarPagoValidado.php`, `app/Modules/Tramites/Listeners/AlValidarPago.php` | `tests/Modules/Tramites/PagoDeTramitesTest.php`, `tests/Concurrency/PagoDeTramiteConcurrenteTest.php` |
| M6.2.c | `app/Modules/Tramites/Livewire/MisTramites.php`, `resources/views/components/portal/etapas.blade.php` | `tests/Modules/Tramites/Portal/MisTramitesTest.php`, `tests/Modules/Tramites/ReglasDeEtapasTest.php` |
| M6.2.d | `app/Modules/Tramites/Listeners/NotificarCambioDeTramite.php`, `app/Modules/Tramites/Support/CorreoDeCambioDeTramite.php` | `tests/Modules/Tramites/NotificacionesDeTramitesTest.php` |
| M6.2.e | `app/Modules/Tramites/Filament/Resources/TiposTramite/TipoTramiteResource.php`, `app/Modules/Tramites/Support/ReglasDeEtapas.php` | `tests/Modules/Tramites/Filament/TiposDeTramitePanelTest.php`, `tests/Modules/Tramites/EsquemaDeTramitesTest.php` |
| M6.2.campos: id | `tra_tramites.codigo` (`TR-AAAA-NNNNNN`) | `tests/Modules/Tramites/SolicitudDeTramitesTest.php` |
| M6.2.campos: estados | `app/Modules/Tramites/Enums/EstadoTramite.php`, `app/Modules/Tramites/Actions/CerrarTramiteSinAtender.php` | `tests/Modules/Tramites/AtencionDeTramitesTest.php`, `tests/Modules/Tramites/CierreSinAtenderTest.php` |
| M6.2.campos: log | `tra_notificaciones`, `app/Modules/Tramites/resources/views/filament/notificaciones-del-tramite.blade.php` | `tests/Modules/Tramites/NotificacionesDeTramitesTest.php`, `tests/Modules/Tramites/Filament/BandejaDeTramitesTest.php` |

**Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)**

*Tabla 43. Evidencia técnica: Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)*

| ID | Código fuente | Pruebas automatizadas |
|---|---|---|
| IV.1.a | Carta de cesión de derechos patrimoniales (Anexo D) | — |
| IV.1.b | `docs/` (arquitectura, modelo de datos, despliegue, seguridad, ADR 0001–0009); manuales de usuario entregados en Word y PDF | — |
| IV.2.a | Ver II.2.a a II.2.d | Ver II.2 |
| IV.2.b | `app/Shared/Audit/Database/Migrations/2026_10_24_125000_create_aud_registros_table.php`, `app/Shared/Audit/Database/Migrations/2026_10_24_160000_add_solicitud_id_to_aud_registros.php` | `tests/Feature/Audit/AuditTrailTest.php`, `tests/Feature/Auditoria/CambiosDelRegistroTest.php` |
| IV.3.a | `database/seeders/DatabaseSeeder.php` (se niega a correr en producción) | `tests/Feature/DemoSeederTest.php` |
| IV.4.a | Carta de garantía técnica (Anexo D) | — |

# ANEXO B. INVENTARIO DE PRUEBAS AUTOMATIZADAS

Conteo de casos de prueba por directorio. Los casos con conjuntos de datos se ejecutan una vez por cada dato, por lo que el número de ejecuciones de la suite es mayor.

*Tabla 44. Inventario de pruebas por directorio*

| Directorio | Archivos | Casos de prueba |
|---|---|---|
| `tests/Modules/Academico` | 21 | 172 |
| `tests/Modules/Personas` | 8 | 73 |
| `tests/Modules/Matricula` | 31 | 251 |
| `tests/Modules/Pagos` | 23 | 179 |
| `tests/Modules/Docencia` | 29 | 232 |
| `tests/Modules/Certificacion` | 23 | 146 |
| `tests/Modules/Tablero` | 8 | 54 |
| `tests/Modules/Tramites` | 14 | 86 |
| `tests/Feature` (raíz) | 18 | 75 |
| `tests/Feature/Api` | 7 | 51 |
| `tests/Feature/Audit` | 1 | 8 |
| `tests/Feature/Auditoria` | 4 | 12 |
| `tests/Feature/Auth` | 8 | 60 |
| `tests/Feature/Database` | 5 | 15 |
| `tests/Feature/Observabilidad` | 2 | 8 |
| `tests/Feature/Portal` | 14 | 102 |
| `tests/Feature/Support` | 1 | 3 |
| `tests/Concurrency` | 15 | 33 |
| `tests/Unit` | 6 | 20 |
| `tests/Architecture` | 3 | 8 (3 casos y 5 reglas de arquitectura) |
| **Total** | **241** | **1 588** |

# ANEXO C. EVIDENCIAS FOTOGRÁFICAS

Registro fotográfico de las reuniones con el área usuaria y de las capacitaciones realizadas durante el servicio (fotografías incluidas en la versión Word/PDF del informe). Las pantallas que aparecen en las fotografías no muestran datos personales de los estudiantes.

**Levantamiento de requerimientos con el área usuaria (23/10–26/10/2026)**

*Figura 14. Levantamiento de requerimientos con el área usuaria: revisión de la documentación del CILN (23/10–26/10/2026)*

*Figura 15. Levantamiento de requerimientos con el área usuaria: registro de los requisitos (23/10–26/10/2026)*

*Figura 16. Levantamiento de requerimientos con el área usuaria: revisión de los requisitos en pantalla (23/10–26/10/2026)*

*Figura 17. Levantamiento de requerimientos con el área usuaria: sesión de trabajo (23/10–26/10/2026)*

**Diseño y organización técnica del sistema**

*Figura 18. Diseño y organización técnica del sistema: revisión de la arquitectura con el área usuaria (23/10–26/10/2026)*

*Figura 19. Diseño y organización técnica del sistema: revisión del modelo del sistema con el área usuaria (23/10–26/10/2026)*

**Capacitación al personal directivo, docente y administrativo del CILN (16/11/2026)**

*Figura 20. Capacitación al personal directivo, docente y administrativo del CILN (16/11/2026)*

*Figura 21. Capacitación al personal directivo, docente y administrativo del CILN: exposición del sistema (16/11/2026)*

**Capacitación práctica a docentes y estudiantes (17 y 18/11/2026)**

*Figura 22. Capacitación práctica a docentes: ingreso al sistema en producción, ciln.unasam.edu.pe (17 y 18/11/2026)*

*Figura 23. Capacitación práctica a docentes: acceso al sistema (17 y 18/11/2026)*

*Figura 24. Capacitación práctica a docentes: portal público del CILN (17 y 18/11/2026)*

*Figura 25. Capacitación práctica a docentes y estudiantes: portal público del CILN (17 y 18/11/2026)*

*Figura 26. Capacitación práctica a docentes y estudiantes: matrícula en el portal (17 y 18/11/2026)*

*Figura 27. Capacitación práctica a estudiantes: uso del sistema en aula (17 y 18/11/2026)*

*Figura 28. Capacitación práctica a estudiantes: acompañamiento en el uso del sistema en aula (17 y 18/11/2026)*

# ANEXO D. CARTAS DE CESIÓN DE DERECHOS Y DE GARANTÍA TÉCNICA

Cartas suscritas por el representante legal de ELECTRODATA INGENIERIA & SISTEMAS S.A.C., que se entregan junto con el presente informe como documentos independientes.

*Tabla 45. Cartas del contratista*

| Documento | Base contractual | Contenido |
|---|---|---|
| Carta de cesión de derechos patrimoniales | TdR 6.1; Anexo Técnico IV.1 | Cesión total, exclusiva y a perpetuidad a la UNASAM de los derechos patrimoniales del software (versión v1.1.1 y anteriores, con su código fuente), el diseño de la base de datos, los scripts, los manuales, la documentación técnica y los repositorios, sin contraprestación adicional; surte efectos desde su suscripción |
| Carta de garantía técnica de funcionamiento y soporte | TdR 6.1, 6.9 y 6.10; Anexo Técnico IV.4 | Garantía de funcionamiento y soporte por doce (12) meses desde la conformidad final (resolución de incidencias y errores, asistencia en el uso al personal del CILN y de la DSTI, y correcciones como nuevas versiones), canal de comunicación de incidencias y responsabilidad por vicios ocultos por un (1) año |
