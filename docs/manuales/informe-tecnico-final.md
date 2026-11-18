<!--
titulo: Informe Técnico Final – Sistema de Gestión Académica y Administrativa del CILN
version: 1.0
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

# USO DE LA INFORMACIÓN

El presente documento es de uso interno y confidencial de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM). Está dirigido al Centro de Idiomas y Lenguas Nativas (CILN), como área usuaria, y a la Dirección de Sistemas y Tecnologías de la Información (DSTI), como responsable de la operación técnica del sistema.

Conforme al numeral 6.5 de los Términos de Referencia, toda información propia de la Entidad es confidencial; el contratista mantiene reserva absoluta sobre la información académica, financiera y personal a la que tuvo acceso durante la prestación, conforme a la Ley N.° 29733.

Conforme al numeral IV.1 del Anexo Técnico y al numeral 6.1 de los Términos de Referencia, todos los derechos patrimoniales y de propiedad intelectual del software, el diseño de la base de datos, el código fuente, los manuales, los scripts de implementación y la presente documentación se ceden de forma total, exclusiva y a perpetuidad a la UNASAM, que es la única titular con derecho a su modificación, escalabilidad, redistribución o integración futura, sin requerir autorización ni pagos adicionales al contratista.

# 1. INTRODUCCIÓN

## 1.1. Antecedentes

El Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM operaba un sistema web de gestión académica (`https://cid.unasam.edu.pe/`) desarrollado hace más de diez años sobre **PHP 5.6**, versión sin soporte oficial ni parches de seguridad desde 2018.

Mediante el **Oficio N.° 166-2026-UNASAM-DSTI/D** y el **Informe Técnico N.° 023-2026-FMAJ**, ambos del 22 de mayo de 2026, la DSTI informó al CILN sobre el estado situacional del sistema y concluyó que presenta limitaciones técnicas, funcionales y de seguridad que comprometen su continuidad: tecnología obsoleta, vulnerabilidades críticas (inyección SQL, ejecución remota de código, XSS, gestión insegura de sesiones, exposición de información sensible), malas prácticas de desarrollo (código monolítico y acoplado, sin arquitectura modular ni estándares OWASP) y una interfaz no adaptable a dispositivos móviles. En consecuencia, recomendó el rediseño y desarrollo integral de un sistema nuevo bajo tecnologías modernas y seguras.

En atención a dicha recomendación, el CILN aprobó los **Términos de Referencia (TdR)** del «Servicio de Elaboración e Implementación de Software de Sistema de Calidad» y su **Anexo Técnico**, suscritos en Huaraz el **7 de setiembre de 2026**. Mediante la **Orden de Servicio N.° 0003091**, del **20 de octubre de 2026** (Expediente SIAF N.° 0000005315), la UNASAM contrató el servicio con **ELECTRODATA INGENIERIA & SISTEMAS S.A.C.** (RUC 20571285852), por un monto de **S/ 39,500.00** y un plazo de prestación de **treinta (30) días calendario**, contados a partir del día siguiente de notificada la orden.

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
| Estudiantes y postulantes | Portal del estudiante | `/estudiante` |
| Docentes | Panel docente | `/docente` |
| Personal del CILN (dirección, coordinación, caja, administración) | Panel de administración y tablero gerencial | `/admin` |
| Otros sistemas de la UNASAM | Servicios REST | `/api/v1` |

Quedan fuera del alcance, conforme a los TdR y al Anexo Técnico: el procesamiento de la planilla y de las retenciones de ley de los docentes (competencia de la DGF), la integración automática con sistemas externos de pago, la analítica predictiva y el asistente conversacional. La migración de datos del sistema anterior se realiza solo **«de requerirse»**, en coordinación con la DSTI y limitada a la información que dicha dirección proporcione en un formato utilizable (numeral 6.1 de los TdR).

## 1.5. Base contractual y normativa

*Tabla 3. Base contractual y normativa del servicio*

| Documento | Aplicación en el servicio |
|---|---|
| Ley N.° 32069, Ley General de Contrataciones Públicas, y su Reglamento (D.S. N.° 009-2025-EF) | Marco de la contratación, penalidades (art. 120) y vicios ocultos |
| Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento | Tratamiento de datos personales de estudiantes, docentes y personal |
| Términos de Referencia del servicio (7 de setiembre de 2026) | Actividades, entregable, plazo, obligaciones, confidencialidad y garantía |
| Anexo Técnico de los TdR | Requisitos técnicos generales, seis módulos funcionales y condiciones de seguridad y propiedad intelectual |
| Orden de Servicio N.° 0003091 (20 de octubre de 2026) | Contratación de ELECTRODATA INGENIERIA & SISTEMAS S.A.C., monto y plazo |
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

# 2. ACTIVIDADES REALIZADAS

## 2.1. Metodología y plan de trabajo

Conforme al numeral 4.3 de los TdR, el servicio se ejecutó con un plan de trabajo coordinado con el área usuaria al inicio de la prestación, que definió la metodología, el cronograma y la estructura técnica propuesta. Se adoptó un desarrollo **iterativo e incremental por módulo**:

1. **Análisis.** Cada requisito del Anexo Técnico se transcribió y codificó (por ejemplo, `M2.1.b`), y cada supuesto o duda se registró como decisión para la validación del CILN (137 decisiones registradas).
2. **Diseño.** Se definieron la arquitectura, el modelo de datos por módulo y las decisiones técnicas en registros de arquitectura (ADR 0001 a 0009).
3. **Construcción guiada por pruebas.** Cada regla de negocio se implementó junto con su prueba automatizada sobre PostgreSQL, con datos sintéticos.
4. **Integración continua.** Cada cambio pasó por el análisis estático, el estilo, la auditoría de dependencias y la suite completa de pruebas antes de integrarse.
5. **Verificación funcional.** Cada flujo se comprobó en el navegador con datos de demostración, como lo usarían el estudiante, el docente y el personal.
6. **Despliegue y cierre.** Se publicó la versión con etiqueta (`v1.0.0`), se desplegó en el servidor designado por la DSTI y se elaboró la documentación técnica.

## 2.2. Cronograma de ejecución

La Orden de Servicio se emitió el 20 de octubre de 2026; el plazo de treinta días calendario corre desde el día siguiente de su notificación. El cronograma se reconstruye a partir del historial del repositorio del código fuente, que registra 466 cambios con su fecha.

*Tabla 5. Cronograma de ejecución del servicio*

| Fase | Fechas | Actividades principales |
|---|---|---|
| Inicio y análisis | 21/10 al 24/10/2026 | Levantamiento de requerimientos con el área usuaria, transcripción del Anexo Técnico y del Informe N.° 023-2026-FMAJ, plan de trabajo, arquitectura y registros de decisiones |
| Base técnica | 24/10/2026 | Proyecto base en Laravel 13 con arquitectura modular, integración continua sobre PostgreSQL 18, auditoría por triggers, autenticación y control de acceso |
| Módulos 1, 2 y 3 | 24/10 al 25/10/2026 | Oferta académica, personas, clasificación, matrícula, pagos y docencia, con pruebas de concurrencia |
| Módulos 4, 5 y 6 y API | 25/10/2026 | Actas y certificación, tablero gerencial, trámites, portal y servicios REST; panel de administración |
| Despliegue (primera versión) | 26/10 al 27/10/2026 | Scripts de instalación tradicional y con contenedores, ajustes de rendimiento del panel |
| Segunda iteración funcional | 31/10 al 03/11/2026 | Control de clases con asistencia y delegado, reportes, panel docente, tablero, portal público, correcciones del panel de administración |
| Seguridad y portal del estudiante | 08/11 al 10/11/2026 | Actualizaciones de seguridad de dependencias, cabeceras y política CSP, sesiones cifradas, PHP 8.5, consentimiento de datos personales, portal del estudiante en estilo formal, paleta con contraste WCAG AA |
| Cierre técnico | 16/11/2026 | Diccionario de datos generado desde PostgreSQL, portada pública institucional, actualización automática por versiones publicadas, correo institucional, guía de despliegue; publicación de la versión `v1.0.0` |
| Ajuste del sistema de evaluación y documentación final | 17/11/2026 | Cálculo de la nota final por las cuatro habilidades y los exámenes; informe técnico final |

## 2.3. Actividades del TdR

*Tabla 6. Ejecución de las actividades del numeral 4.2 de los TdR*

| Actividad (TdR 4.2) | Forma de ejecución | Evidencia |
|---|---|---|
| Levantamiento de requerimientos y análisis con el área usuaria del CILN | Transcripción y codificación de los 96 requisitos del Anexo Técnico; registro de supuestos y decisiones para validación del CILN | `docs/requisitos/`, `docs/decisiones-pendientes.md`, `docs/modulos.md` |
| Diseño y organización técnica del sistema y de los datos (documento técnico) | Fundamentación de PHP 8.5, Laravel, patrón MVC, modelo de datos y API REST | Capítulos 4 a 7 de este informe; `docs/arquitectura.md`, `docs/tecnologia.md`, ADR 0001 a 0009; Diccionario de Datos |
| Desarrollo, pruebas e implementación de los seis módulos | Desarrollo iterativo por módulo con pruebas automatizadas sobre PostgreSQL | Capítulos 8 y 10; matriz de cumplimiento (11.3); Anexos A y B |
| Aplicación de estándares de seguridad (OWASP Top 10) y de pistas de auditoría | Controles por cada riesgo OWASP y auditoría inmutable por triggers en 42 tablas | Capítulo 9; `docs/seguridad.md` |
| Despliegue y puesta en producción en el servidor designado por la DSTI | Instalación en el servidor de la DSTI con la guía y los scripts de implementación | Capítulo 3; `docs/despliegue.md`; versión `v1.0.0` |
| Capacitación al personal del CILN y entrega de manuales y del código fuente | Código fuente y repositorio entregados a la DSTI; capacitación y manuales de usuario según la sección 11.5 | Sección 11.5 |

# 3. INFRAESTRUCTURA TECNOLÓGICA

## 3.1. Servidor web y de aplicaciones

El sistema se ejecuta sobre un servidor Linux de la UNASAM con **PHP 8.5** (PHP-FPM) detrás de un servidor web **Nginx o Apache** con HTTPS. Requiere las extensiones de PHP `pdo_pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring` y `pcntl`. Junto al servidor web funcionan dos procesos de fondo: un **trabajador de colas** supervisado (envío de correos y reacciones a la validación de pagos) y el **programador de tareas** (cron cada minuto), que ejecuta los procesos automáticos.

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

Conforme al numeral 4.5 de los TdR, el sistema se encuentra **desplegado y operativo en el servidor designado por la DSTI**, en la infraestructura de la UNASAM y sin servicios de terceros en la nube. La DSTI administra el servidor, crea los roles de PostgreSQL, custodia las credenciales (base de datos, correo institucional) y decide la activación de la actualización automática.

*Tabla 8. Componentes del entorno de producción*

| Componente | Configuración |
|---|---|
| Aplicación | PHP 8.5 con Nginx o Apache y HTTPS; modo de depuración desactivado |
| Base de datos | PostgreSQL 18 con rol dueño (migraciones) y rol de aplicación |
| Procesos de fondo | Trabajador de colas supervisado y programador de tareas |
| Correo | Buzón institucional del CILN en Microsoft 365 |
| Almacenamiento | Disco del servidor, fuera de la carpeta pública (comprobantes, fotografías, respaldos) |
| Actualizaciones | Solo versiones publicadas con etiqueta `vX.Y.Z` en la rama principal, con reversión automática si falla antes de migrar |

# 4. FUNDAMENTACIÓN TECNOLÓGICA

Este capítulo constituye el documento técnico exigido por el numeral 4.2 de los TdR, que fundamenta la adopción de la tecnología backend, el framework, el patrón MVC, el modelado de la base de datos y la exposición de la API REST.

## 4.1. PHP 8.5 frente a PHP 5.6

El Informe N.° 023-2026-FMAJ (problema 1.1) identificó como causa principal de los riesgos del sistema anterior el uso de PHP 5.6, sin soporte ni parches de seguridad desde 2018. El numeral II.1 del Anexo Técnico exige PHP 8.5 o superior con soporte oficial activo.

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

La Figura 2 muestra la distribución en el servidor de la DSTI: el navegador del usuario se conecta por HTTPS al servidor web, que atiende la aplicación PHP; esta se conecta a PostgreSQL, al almacenamiento local y al servicio de correo institucional, mientras el trabajador de colas y el programador de tareas procesan las tareas de fondo.

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
| M4. Evaluación y certificación digital | Actas finales con cálculo automático, cierre y bloqueo, rectificación autorizada, constancias y certificados con código único y QR, verificación pública | Panel docente; panel: Certificación; `/verificar` |
| M5. Tablero de control gerencial | Indicadores financieros y académicos calculados en cada consulta, filtros y exportación a PDF y Excel | Panel: Tablero gerencial |
| M6. Portal público y trámites | Portada institucional con oferta, requisitos, costos y fechas; trámites con etapas configurables, barra de progreso y notificación por correo | Portada; portal del estudiante; panel: Trámites |

**Sistema de evaluación.** Conforme al numeral M4.1 del Anexo Técnico, los promedios se calculan «según el sistema de evaluación del CILN». La nota final es el promedio simple de dos promedios: el de las **cuatro habilidades** (comprensión auditiva, expresión oral, comprensión lectora y expresión escrita) y el de los **exámenes** (examen parcial y examen final). Los promedios intermedios no se redondean; **solo la nota final se redondea a un entero**, con 0,5 hacia arriba. La nota mínima aprobatoria se configura por cada sistema de evaluación (11 sobre 20 por defecto).

## 8.2. Roles y permisos

El acceso se controla por **permisos por acción**, que se agrupan en roles desde el propio panel (Seguridad → Roles y Usuarios del personal), sin modificar el código.

*Tabla 17. Perfiles de usuario y acceso*

| Perfil | Identificación | Acceso |
|---|---|---|
| Personal del CILN | Cuenta con permiso de acceso al panel y los permisos de su función | Panel de administración, con doble factor opcional u obligatorio por configuración |
| Docente | Cuenta vinculada a un legajo docente | Panel docente: sus grupos, asistencia, pre-actas y actas |
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

Una sola página de ingreso deriva a cada usuario a su portal según su perfil, con recuperación de la contraseña mediante un enlace enviado al correo registrado (M6.1.b y M6.1.c). El personal del CILN ingresa por una página propia, con doble factor.

![Figura 5. Página de acceso](figuras/acceso.png)

### 8.3.3. Portal del estudiante

El estudiante consulta su estado de matrícula y los requisitos pendientes, se matricula en un grupo con vacantes, registra sus pagos, solicita el examen de clasificación y sigue sus trámites con una barra de progreso (M1.3 y M6.2).

![Figura 6. Portal del estudiante](figuras/portal-estudiante.png)

### 8.3.4. Notas del estudiante

El estudiante revisa sus calificaciones por periodo: las notas de cada habilidad y de cada examen, los promedios y la nota final con su estado (aprobado, desaprobado o NSP).

![Figura 7. Notas del estudiante](figuras/notas-estudiante.png)

### 8.3.5. Panel docente

El docente ve sus grupos a cargo, con el horario, los estudiantes matriculados y el acceso al control de clases, a la asistencia y a las notas (M3.2.d y M4.1.a).

![Figura 8. Panel docente](figuras/panel-docente.png)

### 8.3.6. Registro de notas

El docente registra las notas de las cuatro habilidades y de los exámenes; el sistema calcula los promedios y la nota final de forma automática y la redondea a un entero. El registro solo está habilitado en las fechas del calendario académico (M4.1.a y M4.1.b).

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

## 9.2. Pista de auditoría inmutable

Conforme al numeral II.2 y a la sección IV.2 del Anexo Técnico, un trigger de PostgreSQL registra en la tabla `aud_registros` cada alta, cambio o baja de **42 tablas críticas** (notas parciales y finales, actas, rectificaciones, pagos, tarifas, matrículas, movimientos, documentos, roles y permisos, entre otras), con fecha y hora, dirección IP, usuario, operación, valores anteriores y nuevos e identificador de la solicitud.

La tabla rechaza toda modificación, borrado o vaciado. En producción, la aplicación se conecta con un rol que no es dueño de las tablas, por lo que no puede desactivar los triggers ni escribir directamente en la auditoría. El personal autorizado la consulta en Seguridad → Auditoría, con los datos sensibles ocultos.

## 9.3. Protección de datos personales (Ley N.° 29733)

- El autorregistro exige el consentimiento expreso para el tratamiento de los datos personales y guarda su fecha y hora.
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

El sistema cuenta con **238 archivos de prueba y 1 567 casos de prueba**, ejecutados siempre sobre PostgreSQL con datos sintéticos. Cubren las reglas de negocio de cada módulo, los flujos completos de las pantallas, la API, la seguridad (autorización, sesiones, cabeceras, auditoría), la concurrencia con conexiones reales (última vacante, doble validación de pagos) y la arquitectura (desacople entre módulos).

*Tabla 20. Pruebas automatizadas por tipo*

| Tipo de prueba | Archivos | Casos |
|---|---|---|
| Reglas de negocio por módulo (`tests/Modules`) | 155 | 1 176 |
| Flujos transversales, API, seguridad y portales (`tests/Feature`) | 59 | 330 |
| Concurrencia con conexiones reales (`tests/Concurrency`) | 15 | 33 |
| Unitarias (`tests/Unit`) | 6 | 20 |
| Arquitectura y desacople (`tests/Architecture`) | 3 | 8 |
| **Total** | **238** | **1 567** |

Los casos con conjuntos de datos se ejecutan una vez por cada dato, por lo que la suite completa suma **2 224 ejecuciones de prueba**, todas aprobadas en la ejecución del 17 de noviembre de 2026. El inventario por directorio está en el Anexo B.

## 10.2. Análisis estático e integración continua

Cada cambio se verifica con el comando `composer check`, el mismo que ejecuta la integración continua en GitHub Actions:

- **Pint** y **Rector**: estilo uniforme y código modernizado a PHP 8.5.
- **Larastan en nivel 8**, con un límite de complejidad cognitiva de 8 por método y 40 por clase, que impide funciones extensas o anidadas difíciles de mantener.
- **`composer audit`**: bloquea dependencias con vulnerabilidades conocidas.
- **Pest**: la suite completa sobre PostgreSQL 18.

La verificación completa resultó satisfactoria el 17 de noviembre de 2026. Durante el servicio se aplicaron de inmediato las actualizaciones de seguridad publicadas para el framework y sus componentes.

## 10.3. Pruebas con el sistema desplegado

Además de las pruebas automatizadas, cada flujo se verificó en el navegador con datos de demostración, desde el rol de estudiante, docente, cajero y administrador. Sobre la instalación en el servidor se comprobó el funcionamiento de la portada, el inicio de sesión, la cola de correos y el respaldo con el rol de aplicación, y que dicho rol no puede escribir en la pista de auditoría, desactivar triggers, vaciar tablas ni crear objetos en la base.

# 11. VERIFICACIÓN DEL CUMPLIMIENTO

## 11.1. Orden de Servicio

*Tabla 21. Cumplimiento de la Orden de Servicio N.° 0003091*

| Condición | Requisito | Evidencia | Estado |
|---|---|---|---|
| Objeto | Diseño, desarrollo, despliegue y puesta en producción del Sistema de Gestión Académica y Administrativa del CILN | Capítulos 4 a 8; sistema desplegado en el servidor de la DSTI | Cumple |
| Plazo | 30 días calendario desde la notificación (20/10/2026) | Cronograma (2.2): ejecución del 21/10 al 17/11/2026 | Cumple |
| Actividades | Las seis actividades del TdR 4.2, transcritas en la orden | Tabla de actividades (2.3) y sección 11.5 | Cumple, con la capacitación según 11.5 |
| Seis módulos | Registro, clasificación y matrícula; pagos; docencia; evaluación y certificación; tablero; portal y trámites | Matriz del Anexo Técnico (11.3): 78 de 78 requisitos funcionales | Cumple |
| Seguridad | Estándares OWASP Top 10 y pistas de auditoría | Capítulo 9 | Cumple |
| Pago | Una armada, previa conformidad del área usuaria sobre el único entregable | Presente informe y componentes de la sección 11.5 | Para conformidad |

## 11.2. Términos de Referencia

*Tabla 22. Cumplimiento de las cláusulas de los Términos de Referencia*

| Cláusula | Requisito | Evidencia | Estado |
|---|---|---|---|
| 3.2 | Software que automatice el registro, clasificación, matrícula, pagos, gestión docente y certificación, sobre arquitectura moderna y segura | Capítulos 4, 5 y 8; matriz (11.3) | Cumple |
| 4.1 | Servicio conforme al detalle del Anexo Técnico | Matriz (11.3): 91 requisitos cumplidos y 5 cumplidos parcialmente por causas externas | Cumple |
| 4.2 | Seis actividades mínimas | Tabla de actividades (2.3) | Cumple, con la capacitación según 11.5 |
| 4.3 | Plan de trabajo con metodología, cronograma y estructura técnica | Secciones 2.1 y 2.2 | Cumple |
| 4.5 | Sistema desplegado y operativo en el servidor de la DSTI, con código fuente, manuales y documentación técnica | Sección 3.4; relación de componentes (11.5) | Cumple, con los manuales según 11.5 |
| 4.6 | Plazo máximo de 30 días calendario | Cronograma (2.2) | Cumple |
| 6.1 | Código fuente, documentación, manuales, scripts y repositorios; garantía; capacitación; Ley N.° 29733 y auditoría inmutable; cesión de derechos | Secciones 9.2, 9.3 y 11.5 | Cumple, con lo pendiente según 11.5 |
| 6.3 | Único entregable: informe final, sistema desplegado y operativo, código fuente, repositorios, scripts, manuales y documentación | Presente informe y sección 11.5 | Cumple, con lo pendiente según 11.5 |
| 6.5 | Confidencialidad de la información académica, financiera y personal | Pruebas y demostraciones solo con datos sintéticos; sección «Uso de la información» | Cumple |
| 6.10 | Garantía técnica de funcionamiento y soporte por 12 meses | Compromiso del contratista desde la conformidad final | Cumple parcialmente (se inicia con la conformidad) |
| Anexo IV.4 | Garantía de funcionamiento y soporte técnico mínimo de 12 meses | Ídem | Cumple parcialmente (se inicia con la conformidad) |

## 11.3. Matriz de cumplimiento del Anexo Técnico

Se verificaron **96 requisitos** del Anexo Técnico contra el código fuente, las pantallas y las pruebas automatizadas del sistema. Cada requisito se identifica con un código (por ejemplo, `M2.1.b`); las rutas del código y las pruebas que lo comprueban están en el Anexo A, y los números precedidos de «#» remiten al registro de decisiones del proyecto (`docs/decisiones-pendientes.md`).

En la columna de ubicación, «Admin» es el panel de administración del personal (`/admin`), «Portal docente» es el panel `/docente` y «Portal» o «Portal del estudiante» son las páginas `/estudiante`.

*Tabla 23. Resumen del cumplimiento del Anexo Técnico*

| Sección del Anexo Técnico | Requisitos | Cumple | Cumple parcialmente | No cumple |
|---|---|---|---|---|
| II. Requisitos técnicos generales | 12 | 11 | 1 | 0 |
| M1. Registro, clasificación y matrícula | 22 | 22 | 0 | 0 |
| M2. Pagos | 12 | 12 | 0 | 0 |
| M3. Docencia | 15 | 15 | 0 | 0 |
| M4. Evaluación y certificación | 11 | 11 | 0 | 0 |
| M5. Tablero gerencial | 7 | 7 | 0 | 0 |
| M6. Portal público y trámites | 11 | 11 | 0 | 0 |
| IV. Seguridad, propiedad intelectual y confidencialidad | 6 | 2 | 4 | 0 |
| **Total** | **96** | **91** | **5** | **0** |

**Los seis módulos funcionales se cumplen en su totalidad**: los 78 requisitos funcionales tienen una pantalla o un servicio que los atiende y, al menos, una prueba automatizada que lo comprueba. Los cinco requisitos cumplidos parcialmente no dependen de funciones faltantes, sino de condiciones externas al desarrollo:

*Tabla 24. Requisitos cumplidos parcialmente y acción que corresponde*

| Requisito | Situación | Acción y responsable |
|---|---|---|
| II.2.a y IV.2.a OWASP Top 10 | Controles implementados y probados | La DSTI autoriza por escrito el escaneo OWASP ZAP sobre un entorno de pruebas; el reporte se adjunta como evidencia |
| IV.1.a Cesión de derechos a la UNASAM | Obligación contractual declarada en este informe | Se formaliza con el acta de conformidad y cesión |
| IV.1.b Repositorios documentados | Código fuente y repositorio entregados a la DSTI; documentación técnica y diccionario de datos entregados | Entrega de los manuales de usuario |
| IV.4.a Garantía de 12 meses | Se inicia con la conformidad final | Plan de soporte y garantía del contratista |

### 11.3.1. Requisitos técnicos generales (sección II del Anexo)

*Tabla 25. Matriz de cumplimiento: Requisitos técnicos generales (sección II del Anexo)*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| II.1.a | PHP 8.5 o superior, con soporte LTS y parches vigentes | Plataforma | Cumple | — |
| II.1.b | Framework consolidado bajo patrón MVC o multicapa | Plataforma | Cumple | Laravel 13; monolito modular (Controlador/Livewire/Filament → Form Request → Action → Modelo), ADR 0001 |
| II.1.c | Servicios expuestos por API REST y diseño desacoplado | API `/api/v1` | Cumple | 14 servicios REST, de solo lectura salvo la emisión/revocación de tokens (#86); módulos se comunican por `Contracts/` |
| II.1.d | Base de datos relacional normalizada con índices adecuados | Plataforma | Cumple | PostgreSQL 18 con claves foráneas reales, `CHECK` y tablas con prefijo por módulo |
| II.1.e | Compatible con entorno y estándares de la DSTI | Despliegue | Cumple | Desplegado y operativo en el servidor de la DSTI, con HTTPS, correo institucional y respaldos configurados por la DSTI |
| II.2.a | Cumplimiento del OWASP Top 10 | Transversal | Cumple parcialmente | Controles implementados y probados; el reporte OWASP ZAP contra staging (evidencia prevista en `docs/seguridad.md`) aún no existe y requiere autorización escrita de la DSTI |
| II.2.b | Validación de entradas; protección contra SQLi, XSS y CSRF | Transversal | Cumple | No hay una prueba automatizada dedicada a CSRF; la protección la da el middleware del framework |
| II.2.c | Gestión segura de sesiones y cifrado de datos sensibles | Admin → (perfil) Doble factor; transversal | Cumple | Doble factor opcional (#92); cierre del panel a los 30 min (#93) |
| II.2.d | Tratamiento de datos personales conforme a la Ley 29733 | Portal del estudiante → Crear cuenta; Sitio público → `/fichas/{código}`, `/verificar` | Cumple | Consentimiento registrado, documento enmascarado en verificación pública (#67), sin exportación de personas; datos de prueba sintéticos |
| II.2.e | Log Triggers inmutables: fecha, hora, IP, usuario y detalle | Admin → Seguridad → Auditoría | Cumple | Función `aud_registrar()` en 40+ tablas (notas, pagos, actas); `aud_registros` rechaza UPDATE/DELETE/TRUNCATE |
| II.3.a | Interfaz responsive, intuitiva, accesible y adaptable a móviles | Todas las pantallas | Cumple | Tailwind con puntos de quiebre y Filament; no hay prueba automatizada de vistas móviles (se verifica en navegador) |
| II.4.a | Escalabilidad, interoperabilidad, alta disponibilidad e integraciones futuras | Plataforma | Cumple | Arquitectura preparada (módulos desacoplados, API REST, colas); infraestructura de producción y respaldos programados configurados por la DSTI |

### 11.3.2. Módulo 1: Registro, examen de clasificación y matrícula

*Tabla 26. Matriz de cumplimiento: Módulo 1: Registro, examen de clasificación y matrícula*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M1.1.a | Registro de postulantes que desean ingresar a nivel superior al básico | Portal del estudiante → Crear cuenta (`/estudiante/registro`) | Cumple | — |
| M1.1.b | Gestión de solicitudes para rendir el examen de clasificación | Portal del estudiante → Clasificación; Admin → Matrícula → Solicitudes de clasificación | Cumple | Exige pago validado del concepto; vence a los 30 días (#11, #33, #36) |
| M1.1.c | Ingreso de resultados por coordinación y asignación automática del nivel | Admin → Matrícula → Solicitudes de clasificación (registrar resultado) | Cumple | Fija el ciclo habilitado (origen "Examen de clasificación"); la coordinación elige el ciclo, no hay tabla de rangos de puntaje (#34, #35) |
| M1.1.campos: postulante | Tipo/N.º documento, apellidos, nombres, nacimiento, correo, celular | Admin → Matrícula → Personas; Portal → Crear cuenta | Cumple | — |
| M1.1.campos: vínculo | Tipo de usuario (Estudiante, Docente/Administrativo UNASAM, Público) para tarifas | Admin → Matrícula → Personas; Admin → Catálogos → Conceptos y tarifas | Cumple | Autorregistro como Público General; solo el personal cambia el vínculo (#26) |
| M1.1.campos: examen | Idioma, fecha, puntaje, nivel asignado | Admin → Matrícula → Solicitudes de clasificación | Cumple | El nivel asignado se expresa como ciclo del idioma (con su nivel) |
| M1.1.campos: estado | Pendiente de Evaluación, Evaluado, Expirado | Admin → Matrícula → Solicitudes de clasificación | Cumple | Se agrega "Pago anulado" (#131); expiración diaria `clasificacion:expirar` |
| M1.2.a | Crear y ofertar cursos: cupos, horarios, idiomas, modalidades y acelerado | Admin → Oferta académica → Periodos / Idiomas y ciclos / Grupos / Aulas | Cumple | Modalidad Presencial/Virtual/Semipresencial y marca "acelerado" |
| M1.2.campos: ciclo | Año, mes/periodo, fecha de inicio y fin | Admin → Oferta académica → Periodos | Cumple | — |
| M1.2.campos: grupo | Código, idioma, nivel, ciclo (I a XII) | Admin → Oferta académica → Grupos; Idiomas y ciclos | Cumple | Distribución de ciclos por nivel configurable (#9) |
| M1.2.campos: capacidad | Cupo máximo, mínimo de apertura, vacantes en tiempo real | Admin → Oferta académica → Grupos; Portal → Matrícula | Cumple | `CHECK matriculados <= cupo_maximo`; cupo mínimo al iniciar clases (#127) |
| M1.2.campos: asignación | Docente, aula o enlace virtual, horario | Admin → Oferta académica → Grupos (Horarios); Admin → Docencia → Docentes (Carga académica) | Cumple | Choques de aula bloqueados por trigger (#22) |
| M1.3.a | Interfaz para que el estudiante se matricule de forma autónoma | Portal del estudiante → Matrícula | Cumple | — |
| M1.3.b | Bloqueos: ciclo previo/nivel, pago no validado, grupo sin vacantes | Portal del estudiante → Matrícula (requisitos) | Cumple | Prerrequisito = ciclo habilitado por idioma (#10, #15) |
| M1.3.c | Generación automática de la Ficha de Matrícula en PDF | Portal → Mis matrículas (Ficha); Admin → Matrícula → Matrículas | Cumple | Una ficha por estudiante y periodo, con QR (#96) |
| M1.3.campos: inscripción | ID estudiante, grupo, fecha y hora de matrícula | Admin → Matrícula → Matrículas | Cumple | — |
| M1.3.campos: pago | Estado de pago (Verificado/Pendiente) según Pagos | Portal → Matrícula; Admin → Matrícula → Matrículas (pago) | Cumple | La ficha muestra "Verificado"; el portal lista el pago pendiente como requisito |
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
| M2.1.b | Validación por administrador o cajero con recibo digital JPG/PNG/PDF | Admin → Pagos → Pagos (Validar / Rechazar) | Cumple | Tipo detectado por contenido, huella SHA-256, separación de funciones (#114, #117, #118) |
| M2.1.c | Desbloqueo automático de matrícula, constancias y exámenes al validar | Portal → Matrícula / Clasificación / Trámites | Cumple | Constancias y certificados se desbloquean vía trámites (#75) |
| M2.1.d | Auditoría de qué usuario validó cada pago y en qué fecha | Admin → Pagos → Pagos (ficha); Admin → Seguridad → Auditoría | Cumple | — |
| M2.1.campos: identificador | Número de recibo / código de operación | Admin → Pagos → Pagos | Cumple | Único por medio entre pagos no rechazados (#14, #116) |
| M2.1.campos: pagador | Tipo y número de documento del pagador | Admin → Pagos → Pagos (búsqueda por documento) | Cumple | El documento se toma de la ficha de la persona |
| M2.1.campos: financiero | Monto total, fecha de pago, medio de pago | Admin → Pagos → Pagos; Admin → Catálogos → Medios de pago | Cumple | Medios como catálogo (#119) |
| M2.1.campos: concepto | Código del concepto TUPA/TUSNE | Admin → Catálogos → Conceptos y tarifas; Fuentes TUPA/TUSNE | Cumple | Tarifas por concepto y vínculo (#12, #120) |
| M2.1.campos: evidencia | Recibo escaneado, estado, usuario y fecha de validación | Admin → Pagos → Pagos (ficha y recibo) | Cumple | Se agrega estado "Anulado" (#13) |
| M2.2.a | Reportes consolidados por periodo, idioma, nivel y concepto | Admin → Pagos → Reporte de recaudación | Cumple | Periodo = rango de fechas de pago más filtro de periodo académico (#37) |
| M2.2.b | Exportación PDF y/o hoja de cálculo (Excel/CSV) para DGF/Tesorería | Admin → Pagos → Reporte de recaudación (PDF, CSV) | Cumple | CSV UTF-8 con BOM; sin .xlsx (#38) |
| M2.2.campos | Periodo, concepto, monto total, N.º operaciones, detalle por estudiante | Admin → Pagos → Reporte de recaudación | Cumple | Montos sumados en céntimos enteros |

### 11.3.4. Módulo 3: Gestión docente y reportes de carga académica

*Tabla 28. Matriz de cumplimiento: Módulo 3: Gestión docente y reportes de carga académica*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| M3.1.a | Padrón de docentes con idiomas y disponibilidad horaria | Admin → Docencia → Docentes (Disponibilidad horaria) | Cumple | — |
| M3.1.b | Estado del docente: Activo, Inactivo, De Licencia | Admin → Docencia → Docentes | Cumple | Acceso de docentes inactivos al panel por decidir (#48) |
| M3.1.campos: personales | ID docente, nombres, DNI/CE, correo institucional y personal, celular | Admin → Docencia → Docentes | Cumple | Datos personales desde Personas (#39) |
| M3.1.campos: expediente | RUC, cuenta bancaria, banco, condición laboral | Admin → Docencia → Docentes; Admin → Catálogos → Condiciones laborales | Cumple | Cuenta bancaria cifrada y enmascarada (#122) |
| M3.1.campos: académico | Idiomas habilitados, nivel máximo autorizado | Admin → Docencia → Docentes | Cumple | Nivel máximo por idioma (#40) |
| M3.2.a | Vincular docente con grupos/ciclos aperturados | Admin → Docencia → Docentes → Carga académica | Cumple | — |
| M3.2.b | Cálculo automático del total de horas asignadas en el periodo | Admin → Docencia → Docentes → Carga académica; Admin → Docencia → Carga docente | Cumple | Horas = sesiones programadas del periodo (#42) |
| M3.2.c | Control de cruces de horarios del docente | Admin → Docencia → Docentes → Carga académica | Cumple | Regla en Action y en trigger de restricción (#41, #43) |
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
| M4.1.a | Interfaz exclusiva del docente asignado, solo en fechas del calendario | Portal docente → Mis grupos → Acta final | Cumple | Rango "actas desde/hasta" por periodo (#55) |
| M4.1.b | Cálculo automático de promedios (Parcial, Final, Participación) | Portal docente → Acta final; Admin → Catálogos → Sistemas de evaluación | Cumple | Según el sistema de evaluación del CILN: promedio de las cuatro habilidades, promedio de parcial y final y nota final igual al promedio de ambos, solo ella redondeada al entero (0,5 hacia arriba); nota mínima configurable por sistema (11 por defecto); el Anexo menciona «Participación», pero remite al sistema del CILN (#56, #121) |
| M4.1.c | Cierre y bloqueo; modificación con autorización del Administrador y en auditoría | Admin → Certificación → Actas finales (autorizar rectificación) | Cumple | Triggers `cer_actas_bloqueo`, `cer_notas_finales_bloqueo` (#59) |
| M4.1.campos: curso | Idioma, nivel, ciclo, periodo académico | Admin → Certificación → Actas finales | Cumple | — |
| M4.1.campos: docente | ID y nombres completos del docente | Admin → Certificación → Actas finales; PDF del acta | Cumple | Se muestra el código `DOC-00001` |
| M4.1.campos: calificaciones | ID estudiante, notas parciales, promedio, estado Aprobado/Desaprobado/NSP | Admin → Certificación → Actas finales | Cumple | — |
| M4.2.a | Emisión masiva o individual de constancias y certificados de nivel | Admin → Certificación → Constancias y certificados; Actas finales | Cumple | Alcance de cada documento (#63, #64, #65) |
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
| M6.1.a | Página pública: oferta, requisitos, fechas, costos TUPA/TUSNE | Sitio público → Portada (`/`) | Cumple | Requisitos como texto fijo hasta recibir el oficial (#70, #71, #72); dominio oficial depende del despliegue (#8) |
| M6.1.b | Inicio de sesión diferenciado para estudiantes y docentes | Sitio público → Ingresar (`/ingresar`) → Portal del estudiante o Portal docente | Cumple | Una sola entrada que deriva por perfil (#73, por confirmar) |
| M6.1.c | Recuperación de credenciales con token al correo registrado | Sitio público → Recuperar clave (`/recuperar-clave`, `/restablecer-clave/{token}`) | Cumple | — |
| M6.2.a | Flujo configurable por trámite (constancia, certificado, traslado, retiro) | Admin → Catálogos → Tipos de trámite; Portal → Trámites | Cumple | (#74, #76) |
| M6.2.b | Flujo bloqueado hasta que Pagos valide la tasa | Portal → Trámites; Admin → Trámites → Trámites | Cumple | Aplicación automática del pago (#75) |
| M6.2.c | Barra de progreso visible para el usuario | Portal del estudiante → Trámites | Cumple | Etapas con porcentaje configurables; estados fijos (#6) |
| M6.2.d | Notificación automática por correo en cada cambio de estado | Correo al estudiante; Admin → Trámites → Trámites (notificaciones) | Cumple | Por cola, 3 intentos (#79); requiere SMTP de la DSTI (#8) |
| M6.2.e | Panel para configurar etapas, estados y condiciones sin programar | Admin → Catálogos → Tipos de trámite | Cumple | Condiciones de etapa de un catálogo cerrado (solicitud, pago validado, acción del personal) y acciones finales predefinidas |
| M6.2.campos: id | ID de trámite (código único de seguimiento) | Portal → Trámites; Admin → Trámites → Trámites | Cumple | (#78) |
| M6.2.campos: estados | Pendiente de Pago, En Proceso, Observado, Finalizado | Admin → Trámites → Trámites | Cumple | Se agrega "Cerrado sin atender" (#134) |
| M6.2.campos: log | Log de notificaciones: fecha, hora, estado de entrega | Admin → Trámites → Trámites (ficha: notificaciones) | Cumple | Estados pendiente, enviado, fallido; inmutable por trigger |

### 11.3.8. Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)

*Tabla 32. Matriz de cumplimiento: Seguridad, propiedad intelectual y confidencialidad (sección IV del Anexo)*

| ID | Requisito | Ubicación en el sistema | Estado | Observación |
|---|---|---|---|---|
| IV.1.a | Cesión total de derechos patrimoniales a la UNASAM | Contractual | Cumple parcialmente | Declarada en este informe; se formaliza con el acta de conformidad y cesión |
| IV.1.b | Entrega de repositorios completos y documentados al finalizar | Repositorio | Cumple parcialmente | Código fuente y repositorio entregados a la DSTI con su documentación técnica; manuales de usuario pendientes de entrega |
| IV.2.a | Seguridad conforme a Ley 29733 y OWASP | Transversal | Cumple parcialmente | Mismo pendiente que II.2.a (reporte ZAP) |
| IV.2.b | Log Triggers inmutables: fecha, hora, IP, usuario y detalle | Admin → Seguridad → Auditoría | Cumple | — |
| IV.3.a | Confidencialidad de la información académica, financiera y personal | Proceso del proveedor | Cumple | Compromiso contractual; en lo técnico, pruebas y demo solo con datos sintéticos |
| IV.4.a | Garantía y soporte técnico mínimo de 12 meses | Contractual | Cumple parcialmente | Obligación que se inicia con la conformidad final; no verificable en el código |

## 11.4. Atención al Informe Técnico N.° 023-2026-FMAJ

*Tabla 33. Problemas del sistema anterior y solución del sistema nuevo*

| Problema (Informe N.° 023-2026-FMAJ) | Solución en el sistema nuevo |
|---|---|
| 1.1 Tecnología obsoleta (PHP 5.6 sin soporte desde 2018) | PHP 8.5, Laravel 13 y PostgreSQL 18, todos con soporte vigente; actualizaciones de seguridad aplicadas durante el servicio (sección 4.1) |
| 1.2 Vulnerabilidades (inyección SQL, ejecución remota, XSS, sesiones inseguras, exposición de datos) y malas prácticas | Controles OWASP Top 10 (9.1); monolito modular con fronteras verificadas por pruebas; separación entre lógica y presentación; manejo centralizado de errores |
| 1.3 Problemas de lógica funcional y limitaciones ante nuevos requerimientos | Reglas de negocio en Acciones únicas, validadas también en la base de datos y cubiertas por 1 567 casos de prueba; catálogos y flujos configurables sin programar |
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
| Sistema desplegado y operativo en el servidor de la DSTI | TdR 4.2, 4.5 y 6.3 | Entregado |
| Código fuente y repositorio completo con su historial | TdR 6.1; Anexo IV.1 | Entregado a la DSTI |
| Scripts de implementación (instalación tradicional y con contenedores, actualización automática con reversión, migraciones) | TdR 6.1 | Entregado, en el repositorio |
| Documentación técnica (presente informe, arquitectura, modelo de datos, seguridad, guía de despliegue, ADR, documentación OpenAPI de la API) | TdR 4.2, 4.5 y 6.3 | Entregado |
| Diccionario de Datos – Sistema de Gestión del CILN | TdR 4.2 | Entregado |
| Manuales de usuario (estudiante, docente y personal del CILN) | TdR 4.2 y 6.1 | Pendiente de entrega |
| Capacitación al personal directivo, administrativo y docente del CILN | TdR 4.2 y 6.1 | Pendiente, por programar con el CILN |
| Garantía técnica de funcionamiento y soporte por 12 meses | TdR 6.1 y 6.10; Anexo IV.4 | Se inicia con la conformidad final |

# 12. CONCLUSIONES

1. El Sistema de Gestión Académica y Administrativa del CILN **cumple los seis módulos funcionales** del Anexo Técnico: los 78 requisitos funcionales tienen una pantalla o un servicio que los atiende y pruebas automatizadas que lo comprueban.
2. De los 96 requisitos verificados del Anexo Técnico, **91 se cumplen y 5 se cumplen parcialmente** por depender de condiciones externas al desarrollo (autorización del escaneo de seguridad, formalización de la cesión de derechos, entrega de manuales y garantía, que se inicia con la conformidad). **Ningún requisito queda sin atender.**
3. El servicio se ejecutó dentro del plazo de la Orden de Servicio N.° 0003091 y atendió las actividades del numeral 4.2 de los TdR; el sistema se encuentra desplegado en el servidor de la DSTI y el código fuente está en poder de la DSTI.
4. El sistema supera las limitaciones señaladas por la DSTI en el Informe Técnico N.° 023-2026-FMAJ: usa tecnologías con soporte vigente, aplica los controles del OWASP Top 10, registra una pista de auditoría inmutable en la base de datos y protege los datos personales conforme a la Ley N.° 29733.
5. El cálculo de las notas sigue el sistema de evaluación del CILN: promedio de las cuatro habilidades y promedio de los exámenes, con la nota final redondeada a un entero y una nota mínima aprobatoria configurable.

# 13. RECOMENDACIONES

1. **Correo y respaldos.** Que la DSTI mantenga la configuración del correo institucional y de los respaldos programados del servidor de producción, y que realice una prueba de restauración de los respaldos al menos una vez por semestre.
2. **Seguridad.** Que la DSTI autorice por escrito el escaneo OWASP ZAP sobre un entorno de pruebas, active el doble factor obligatorio para el personal del panel (`PANEL_DOBLE_FACTOR_OBLIGATORIO=true`) y mantenga la conexión de la aplicación con un rol distinto del dueño del esquema.
3. **Garantía.** Que, con la conformidad final, se formalice el inicio de la garantía técnica de doce meses y el canal de reporte de incidencias entre el CILN, la DSTI y el contratista.
4. **Actualizaciones.** Que las nuevas versiones se instalen solo desde **etiquetas publicadas** (`vX.Y.Z`) de la rama principal, que pasan la integración continua, mediante la actualización automática o el procedimiento manual de la guía de despliegue, y que la DSTI mantenga las dependencias dentro de sus versiones con soporte.
6. **Validación institucional.** Que el CILN valide los supuestos registrados durante las pruebas de aceptación y registre en el panel los datos institucionales (tarifas TUPA/TUSNE, firmantes, denominación del año, sistemas de evaluación y roles del personal), y que se programen la capacitación y la entrega de los manuales de usuario.

# ANEXO A. EVIDENCIA TÉCNICA POR REQUISITO

Rutas del código fuente que implementan cada requisito y pruebas automatizadas que lo comprueban.


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
| II.2.b | Form Requests y Actions de cada módulo; Eloquent con bindings; middleware CSRF de Laravel | `tests/Feature/Portal/SeguridadDelPortalTest.php`, `tests/Modules/Tramites/PortadaPublicaTest.php` |
| II.2.c | `config/session.php` (cifrado, HttpOnly, SameSite), `app/Shared/Auth/Http/Middleware/`, `app/Modules/Docencia/Models/Docente.php` (cuenta bancaria cifrada) | `tests/Feature/Auth/SesionDelPanelTest.php`, `tests/Feature/Auth/DobleFactorDelPanelTest.php` |
| II.2.d | `app/Modules/Personas/Actions/RegistrarCuentaDeEstudiante.php` (`tratamiento_aceptado_en`), `app/Modules/Matricula/Http/Controllers/VerFichaPorCodigo.php` | `tests/Modules/Personas/Portal/RegistroTest.php`, `tests/Modules/Matricula/Portal/FichaPublicaTest.php` |
| II.2.e | `app/Shared/Audit/Database/Migrations/2026_10_24_125000_create_aud_registros_table.php`, `app/Shared/Audit/Http/Middleware/` | `tests/Feature/Audit/AuditTrailTest.php`, `tests/Feature/Auditoria/TablasAuditadasTest.php` |
| II.3.a | `resources/views/components/layouts/portal.blade.php`, `app/Providers/Filament/AparienciaDelPanel.php` | `tests/Unit/PaletaCilnTest.php` (contraste WCAG AA), `tests/Feature/Portal/MenuDelPortalTest.php` |
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
| M4.1.a | `app/Modules/Certificacion/Filament/Docente/Pages/ActaFinalDelGrupo.php`, `app/Modules/Certificacion/Actions/CerrarActa.php` | `tests/Modules/Certificacion/CerrarActaTest.php`, `tests/Modules/Certificacion/Panel/ActaFinalDelGrupoTest.php` |
| M4.1.b | `app/Modules/Certificacion/Support/PromedioFinal.php`, `app/Modules/Academico/Actions/GuardarEsquemaDeEvaluacion.php` | `tests/Modules/Certificacion/PromedioFinalTest.php`, `tests/Modules/Academico/EsquemasDeEvaluacionTest.php` |
| M4.1.c | `app/Modules/Certificacion/Actions/AutorizarRectificacion.php`, `app/Modules/Certificacion/Actions/RectificarNotasFinales.php` | `tests/Modules/Certificacion/RectificacionDeActaTest.php`, `tests/Concurrency/ActaConcurrenteTest.php` |
| M4.1.campos: curso | `app/Modules/Certificacion/Database/Migrations/2026_10_25_100000_create_cer_actas_tables.php` | `tests/Modules/Certificacion/Filament/ActasPanelTest.php` |
| M4.1.campos: docente | `cer_actas.docente_id/docente_nombre`, `app/Modules/Certificacion/Support/DocenteDelActa.php` | `tests/Modules/Certificacion/CerrarActaTest.php` |
| M4.1.campos: calificaciones | `cer_notas_finales` (notas, promedio, estado_final) | `tests/Modules/Certificacion/PromedioFinalTest.php`, `tests/Modules/Certificacion/CerrarActaTest.php` |
| M4.2.a | `app/Modules/Certificacion/Actions/EmitirCertificadosDelPeriodo.php`, `app/Modules/Certificacion/Actions/EmitirConstanciasDeNotasDelActa.php` | `tests/Modules/Certificacion/Documentos/EmisionDeDocumentosTest.php`, `tests/Modules/Certificacion/Documentos/EmisionMasivaTest.php` |
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
| IV.1.a | — | — |
| IV.1.b | `docs/` (arquitectura, modelo de datos, despliegue, seguridad, ADR 0001–0009) | — |
| IV.2.a | Ver II.2.a a II.2.d | Ver II.2 |
| IV.2.b | `app/Shared/Audit/Database/Migrations/2026_10_24_125000_create_aud_registros_table.php`, `app/Shared/Audit/Database/Migrations/2026_10_24_160000_add_solicitud_id_to_aud_registros.php` | `tests/Feature/Audit/AuditTrailTest.php`, `tests/Feature/Auditoria/CambiosDelRegistroTest.php` |
| IV.3.a | `database/seeders/DatabaseSeeder.php` (se niega a correr en producción) | `tests/Feature/DemoSeederTest.php` |
| IV.4.a | — | — |

# ANEXO B. INVENTARIO DE PRUEBAS AUTOMATIZADAS

Conteo de casos de prueba por directorio. Los casos con conjuntos de datos se ejecutan una vez por cada dato, por lo que el número de ejecuciones de la suite es mayor.

*Tabla 44. Inventario de pruebas por directorio*

| Directorio | Archivos | Casos de prueba |
|---|---|---|
| `tests/Modules/Academico` | 21 | 171 |
| `tests/Modules/Personas` | 7 | 68 |
| `tests/Modules/Matricula` | 31 | 251 |
| `tests/Modules/Pagos` | 23 | 179 |
| `tests/Modules/Docencia` | 29 | 226 |
| `tests/Modules/Certificacion` | 22 | 141 |
| `tests/Modules/Tablero` | 8 | 54 |
| `tests/Modules/Tramites` | 14 | 86 |
| `tests/Feature` (raíz) | 17 | 71 |
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
| `tests/Architecture` | 3 | 8 (3 `it` + 5 `arch`) |
| **Total** | **238** | **1 567** |
