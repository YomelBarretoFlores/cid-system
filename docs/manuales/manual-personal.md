<!--
titulo: Manual de Usuario – Personal del CILN
sistema: Sistema de Gestión Académica y Administrativa del CILN
version: 1.7
fecha: noviembre de 2026
autor: ELECTRODATA INGENIERIA & SISTEMAS S.A.C.
-->

# HISTORIAL DE VERSIONES

| Versión | Fecha | Descripción | Elaborado por | Dirigido a |
|---|---|---|---|---|
| 1.0 | 18/11/2026 | Versión inicial | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.1 | 19/11/2026 | Actualización para la versión v1.1.1 del sistema | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.2 | 26/11/2026 | Actualización para la versión v1.2.0 del sistema: programas, grupos acelerados, forma de aprobación, constancia de notas por nivel, datos institucionales, tarifas por nivel y programa, FUT configurable, nuevos trámites y matrícula extemporánea | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.3 | 26/11/2026 | Actualización para la versión v1.3.0 del sistema: menú «Administración» con «Reportes» y «Configuración», catálogos de tipos de vínculo, tipos de documento, modalidades, bancos y grados del FUT, programa por defecto, parámetros del sistema, textos editables, cuentas del portal creadas en la oficina y habilitación de la cuenta del estudiante con su primer pago validado | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.4 | 27/11/2026 | Cuentas de docentes con el DNI como usuario y contraseña entregada en la oficina; dar de baja y reactivar cuentas del portal | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.5 | 27/11/2026 | Matricular en ventanilla, con un pago validado o con un pago anterior al sistema, y el interruptor que permite estos pagos | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.6 | 27/11/2026 | Web pública administrable: foto de portada, títulos, textos, botones, secciones visibles, pie de página y enlaces; vista previa y atajos «Editar» | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.7 | 27/11/2026 | Sistemas de evaluación configurables: columnas que ingresa el docente y calculadas (promedio simple o ponderado, redondeo), nota del ciclo y del segundo ciclo en el acelerado, por programa y régimen | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.8 | 27/11/2026 | Tareas en una sola pantalla: ficha de la persona con sus matrículas, pagos, documentos y trámites; nuevo docente con su persona y su cuenta; matrícula en ventanilla con registro de la persona; grupo con horario y docente; búsqueda de docentes por nombre o documento | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |

# 1. INTRODUCCIÓN

## 1.1. Objetivo

El presente manual describe el uso del panel administrativo del Sistema de Gestión Académica y Administrativa del Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM. Está dirigido al personal del CILN que administra la oferta académica, registra y valida pagos, gestiona matrículas y clasificaciones, atiende trámites, emite actas, constancias y certificados, consulta reportes y administra las cuentas del personal.

Cada procedimiento indica su finalidad, los pasos a seguir con los nombres exactos de los botones y campos de la pantalla (en **negrita**), el resultado esperado y una figura de referencia. Las figuras se obtuvieron con datos de demostración generados para las pruebas: los nombres, documentos de identidad, correos, teléfonos, montos y fechas que aparecen en ellas son ficticios.

## 1.2. Perfiles y permisos

El acceso a cada opción del menú depende de los permisos asignados a la cuenta. Los permisos se agrupan en roles (por ejemplo, «Caja» o «Coordinación académica») desde el menú **Seguridad > Roles**, y cada cuenta puede recibir uno o más roles y, si hace falta, permisos adicionales. Una opción del menú que la cuenta no puede usar simplemente no se muestra.

La Tabla 1 resume los perfiles habituales del CILN y los permisos que cada uno necesita. Los nombres de los permisos son los que aparecen en el formulario de usuarios y roles.

*Tabla 1. Perfiles del personal y permisos que requieren*

| Perfil | Tareas principales | Permisos necesarios |
|---|---|---|
| Administración | Configura catálogos, cuentas y roles; consulta la auditoría; anula pagos. | Gestionar usuarios, roles y permisos · Consultar la auditoría · Configurar conceptos y tarifas · Anular pagos · Configurar tipos de trámite (y los demás permisos que deba supervisar) |
| Caja | Registra personas y pagos; valida o rechaza pagos; consulta matrículas. | Ver personas · Gestionar personas · Ver pagos · Registrar pagos · Validar pagos · Ver matrículas (opcional: Ver reportes de pagos) |
| Coordinación académica | Prepara periodos, idiomas, ciclos, aulas y grupos; registra a los docentes y gestiona sus cargas, las matrículas y las clasificaciones. | Ver personas · Gestionar personas · Ver la oferta académica · Gestionar la oferta académica · Ver docentes y carga académica · Gestionar docentes y carga académica · Gestionar costos por hora · Ver reportes de docencia · Ver matrículas · Gestionar matrículas, traslados y retiros · Ver solicitudes de examen de clasificación · Registrar resultados de clasificación |
| Certificación | Revisa actas finales, autoriza rectificaciones y emite constancias y certificados. | Gestionar actas · Emitir y anular constancias y certificados |
| Trámites | Atiende las solicitudes de trámite de los estudiantes. | Atender trámites |
| Dirección (tablero) | Consulta indicadores y reportes gerenciales. | Ver el tablero gerencial (opcional, según los reportes que consulte: Ver reportes de pagos, Ver reportes de docencia, Ver matrículas; ver la Tabla 16) |

El permiso «Ingresar al panel» se concede automáticamente a toda cuenta del personal, por lo que no figura en los formularios.

## 1.3. Requisitos

*Tabla 2. Requisitos para usar el panel*

| Requisito | Detalle |
|---|---|
| Equipo | Computadora con conexión a la red de la UNASAM o a Internet. |
| Navegador | Versión actual de Google Chrome, Mozilla Firefox, Microsoft Edge o Safari. |
| Dirección del panel | **https://ciln.unasam.edu.pe/admin/login** |
| Cuenta | Cuenta del personal creada por el administrador del sistema, es decir, el personal del CILN con los permisos del menú **Seguridad** (usuarios y roles); la persona define su contraseña con el enlace que recibe en su correo (sección 11.1). |
| Doble factor | Si el CILN lo exige, una aplicación autenticadora en el teléfono (por ejemplo, Google Authenticator o Microsoft Authenticator). |
| Documentos | Para validar pagos: la boleta o voucher original entregado por la persona. |

# 2. ACCESO AL PANEL

## 2.1. Ingresar al panel

Finalidad: iniciar una sesión de trabajo en el panel administrativo.

1. Abra el navegador e ingrese a **https://ciln.unasam.edu.pe/admin/login** (enlace **Personal CILN** de la parte superior de la página pública).
2. Escriba su **Correo electrónico** y su **Contraseña**.
3. Pulse **Entrar**.
4. Si su cuenta tiene el doble factor configurado, escriba el código de seis dígitos que muestra su aplicación autenticadora y confirme.

Resultado esperado: se muestra la pantalla de inicio que corresponde a sus permisos. Quien tiene el permiso del tablero ingresa al **Tablero gerencial**; una cuenta de caja ingresa, por ejemplo, a **Personas**.

![Figura 1. Pantalla de ingreso al panel](figuras/p01-acceso-al-panel.png)

Si se ingresa varias veces una contraseña incorrecta, el sistema bloquea temporalmente los intentos y el mensaje indica cuántos segundos esperar antes de volver a intentarlo.

## 2.2. Doble factor de autenticación

Cuando el CILN activa el doble factor como obligatorio, el sistema solicita configurarlo en el primer ingreso y no permite continuar hasta completarlo. También puede configurarse de forma voluntaria desde el perfil:

1. Pulse su nombre en la parte inferior del menú y elija el perfil.
2. En **Autenticación de dos factores (2FA)**, junto a **Aplicación de autenticación**, pulse **Configurar**.
3. Escanee el código QR con la aplicación autenticadora y escriba el código que esta genera.
4. Guarde en un lugar seguro los códigos de recuperación que muestra el sistema.

Resultado esperado: el estado de la aplicación de autenticación cambia a habilitada y, desde el siguiente ingreso, el sistema pedirá el código.

Si pierde el teléfono, use un código de recuperación o pida al administrador que ejecute **Reiniciar doble factor** sobre su cuenta (sección 11.1).

## 2.3. Menú por grupos

El menú lateral muestra primero el **Tablero gerencial** y organiza las demás opciones en grupos:

*Tabla 3. Grupos del menú del panel*

| Grupo | Opciones |
|---|---|
| Matrícula | Personas, Solicitudes de clasificación, Matrículas, Ciclos habilitados |
| Oferta académica | Periodos, Idiomas y ciclos, Grupos, Aulas, Programas |
| Pagos | Pagos |
| Docencia | Docentes, Control de clases, Expediente de horas |
| Certificación | Actas pendientes, Actas finales, Constancias y certificados |
| Trámites | Trámites |
| Administración | **Reportes** (todos los reportes, en pestañas; sección 10.2) y **Configuración** (todos los catálogos, parámetros y textos editables; capítulo 3) |
| Seguridad | Usuarios del personal, Cuentas de docentes, Roles, Auditoría |

Cada grupo puede contraerse con la flecha de su encabezado, y el menú completo se contrae con **Contraer el menú**. Las cifras resaltadas junto a una opción indican tareas pendientes, por ejemplo pagos por validar o solicitudes de clasificación por evaluar. Al pasar de una pantalla a otra, el menú lateral y el submenú de **Configuración** conservan la posición en que estaban.

![Figura 2. Menú del panel y tablero de inicio](figuras/p02-menu-del-panel.png)

## 2.4. Perfil

Finalidad: actualizar la contraseña propia y el doble factor.

1. Pulse su nombre en la parte inferior del menú y elija el perfil.
2. Escriba la **Nueva contraseña** y repítala en **Confirmar nueva contraseña**.
3. Escriba su **Contraseña actual**, que el sistema pide para confirmar el cambio.
4. Pulse **Guardar cambios**.

Resultado esperado: la contraseña queda actualizada. El nombre y el correo de la cuenta solo los modifica el administrador.

![Figura 3. Perfil del usuario](figuras/p03-perfil.png)

## 2.5. Búsqueda

El campo **Buscar** de la parte superior del menú (atajo ⌘+K o Ctrl+K) permite ubicar registros por su nombre o código sin recorrer el menú. Las personas y los docentes se encuentran por apellidos y nombres o por número de documento; los docentes, también por su código (DOC-…). Además, cada listado tiene su propio campo **Buscar** y un botón de filtros que muestra entre paréntesis cuántos filtros están activos.

## 2.6. Cerrar sesión

1. Pulse su nombre en la parte inferior del menú.
2. Elija **Salir**.

Cierre siempre la sesión al terminar, en especial en equipos compartidos. Por seguridad, el sistema también cierra la sesión del panel tras 30 minutos sin actividad (valor configurable por la DSTI).

# 3. CONFIGURACIÓN INICIAL

Todos los catálogos, parámetros y textos editables están en **Administración > Configuración**. Al entrar, un submenú a la izquierda los agrupa por tema: **Institución**, **Académico**, **Personas**, **Pagos**, **Docencia** y **Trámites**. Cada opción solo aparece a quien tiene su permiso. En este manual, una ruta como **Configuración > Pagos > Conceptos y tarifas** indica la opción **Conceptos y tarifas** del grupo **Pagos** del submenú. Antes de abrir el primer periodo, el administrador revisa estos catálogos; la Tabla 4 resume su contenido.

*Tabla 4. Opciones de Configuración*

| Grupo del submenú | Opción | Contenido | Permiso |
|---|---|---|---|
| Institución | Firmantes | Autoridades que firman actas, constancias y certificados, con su periodo en el cargo. | Emitir y anular constancias y certificados (para marcar o editar un firmante de actas, también Gestionar actas) |
| Institución | Denominación del año | Nombre oficial del año que se imprime en los documentos. | Emitir y anular constancias y certificados para consultarla; para crearla o editarla, también Gestionar actas |
| Institución | Datos institucionales | Nombre de la universidad y del centro, encabezado, pie de página, ciudad y formato del número de los documentos oficiales. | Emitir y anular constancias y certificados y Gestionar actas |
| Institución | Textos de documentos | Frases de las constancias, de la acreditación y del certificado de nivel (sección 3.8). | Emitir y anular constancias y certificados |
| Institución | Política de privacidad | Texto de la política de privacidad y frase de consentimiento del registro (sección 3.8). | Gestionar personas |
| Institución | Contenido de la portada | Todo el contenido de la web pública: foto de portada, títulos y textos de cada sección, botones, secciones visibles, pie de página, contacto y enlaces (sección 3.8). | Configurar tipos de trámite |
| Institución | Parámetros del sistema | Vigencia y puntaje del examen de clasificación, ventanas de los llamados de asistencia, límites de archivos y aviso de pocas vacantes (sección 3.7). | Gestionar usuarios, roles y permisos |
| Académico | Sistemas de evaluación | Columnas que ingresa el docente, columnas calculadas, nota del ciclo y nota mínima aprobatoria, por idioma, nivel, programa y régimen. | Gestionar la oferta académica |
| Académico | Modalidades | Modalidades de los grupos (presencial, virtual, semipresencial u otras) y su descripción en la web pública (sección 3.6). | Gestionar la oferta académica |
| Personas | Tipos de vínculo | Vínculo de la persona con la UNASAM, que decide su tarifa (sección 3.6). | Gestionar personas |
| Personas | Tipos de documento | Documentos de identidad aceptados y la regla de formato de su número (sección 3.6). | Gestionar personas |
| Pagos | Conceptos y tarifas | Conceptos TUPA/TUSNE (matrícula, pensión, constancias, examen de clasificación, etc.) y sus tarifas por vínculo con la UNASAM, nivel, programa y vigencia. | Configurar conceptos y tarifas |
| Pagos | Medios de pago | Bancos, agentes y canales con los que se paga (por ejemplo, Banco de la Nación, Tesorería UNASAM, Págalo.pe). La web pública los lista como lugares de pago. | Configurar conceptos y tarifas |
| Pagos | Fuentes TUPA/TUSNE | Documento normativo del que proviene cada concepto. | Configurar conceptos y tarifas |
| Pagos | Configuración de pagos | **Recargo por matrícula extemporánea (%)**: porcentaje entero de 0 a 100 (20 por defecto) que se suma a la tarifa de matrícula pagada en el plazo extemporáneo. 0 lo desactiva. **Permitir pagos anteriores al sistema**: activado por defecto; permite usar **Matricular en ventanilla** con un pago hecho fuera del sistema (sección 7.2). Desactívelo cuando el CILN ya no lo necesite. Cada cambio queda en la auditoría. | Configurar conceptos y tarifas |
| Docencia | Costos por hora | Costo por hora del docente según su grado académico (sección 5.4). | Gestionar costos por hora |
| Docencia | Condiciones laborales | Condición del docente (nombrado, contratado, etc.). | Gestionar docentes y carga académica |
| Docencia | Grados académicos | Grado del docente; define su costo por hora. | Gestionar docentes y carga académica |
| Docencia | Bancos | Bancos de la cuenta del docente para el expediente de horas (sección 3.6). | Gestionar docentes y carga académica |
| Trámites | Tipos de trámite | Trámites que el estudiante puede iniciar, con requisitos, tasa, etapas y acción al finalizar. | Configurar tipos de trámite |
| Trámites | Grados del FUT | Grados que el estudiante elige en la mención del FUT y el grado que imprime la acreditación (sección 3.6). | Configurar tipos de trámite |

Siguen fijos en el sistema, porque deciden reglas: los niveles Básico, Intermedio y Avanzado, los estados de cada proceso, los límites de seguridad, la fórmula del promedio y los tipos de concepto.

## 3.1. Conceptos y tarifas

Finalidad: registrar los conceptos que se cobran y su tarifa vigente.

1. Ingrese a **Configuración > Pagos > Conceptos y tarifas**.
2. Pulse **Nuevo concepto** y complete **Código TUPA/TUSNE** (no cambia después de crear el concepto), **Nombre**, **Fuente** y **Servicio que habilita**. Deje marcado **Disponible para nuevos pagos**.
3. Guarde y, en la sección **Tarifas** del concepto, pulse **Nueva tarifa**.
4. Elija el **Vínculo con la UNASAM**, el **Monto** y la fecha **Vigente desde**; indique **Vigente hasta** solo si la tarifa tiene fecha de cierre.
5. Si el monto depende del nivel o del programa (por ejemplo, el certificado de estudios cuesta distinto en Básico, Intermedio y Avanzado, o en Posgrado), elija el **Nivel** y/o el **Programa**. Déjelos vacíos para una tarifa que vale para todos.

Resultado esperado: el concepto aparece en el listado y su tarifa se aplica a los pagos con fecha dentro de la vigencia. Si ya había una tarifa abierta para el mismo vínculo, nivel y programa, el sistema la cierra el día anterior al inicio de la nueva, de modo que los pagos anteriores conservan el monto que aplicaron.

Al registrar un pago se aplica la tarifa más específica vigente: programa y nivel; luego solo nivel; luego solo programa; por último la tarifa general. Dos tarifas del mismo concepto, vínculo, nivel y programa no pueden superponer sus vigencias. Una tarifa ya aplicada a pagos no cambia de nivel ni de programa.

En la sección **Tarifas**, cada fila tiene además las acciones **Cerrar**, que registra el **Último día de vigencia** de la tarifa, y **Borrar**, que solo elimina una tarifa que ningún pago usa.

![Figura 4. Listado de conceptos TUPA/TUSNE](figuras/p04-conceptos.png)

![Figura 5. Tarifas de un concepto](figuras/p05-concepto-tarifas.png)

## 3.2. Medios de pago y fuentes TUPA/TUSNE

1. Ingrese a **Configuración > Pagos > Medios de pago** o **Configuración > Pagos > Fuentes TUPA/TUSNE**.
2. Pulse **Nuevo medio de pago** o **Nueva fuente**, escriba el **Nombre**, defina el **Orden en las listas** y deje marcado **Disponible en los formularios**. El **Código** se genera al crear el registro y no cambia.
3. Guarde.

Resultado esperado: el registro aparece en los formularios de pagos y conceptos. Un registro desmarcado como disponible deja de ofrecerse, pero se conserva en los pagos ya registrados.

## 3.3. Tipos de trámite

1. Ingrese a **Configuración > Trámites > Tipos de trámite** y cree o edite un tipo.
2. Complete **Código**, **Nombre** y **Requisitos** (el estudiante los ve antes de iniciar el trámite).
3. En **Tasa**, elija el tipo de pago que debe presentarse (por ejemplo, Matrícula, Pensión o Constancia) o deje **Sin tasa**. Con tasa, el trámite espera a que Pagos valide un pago de ese tipo.
   - Opcionalmente, en **Concepto de pago**, elija el concepto exacto que debe pagarse (por ejemplo, «Constancia de notas»). La **Tasa** toma el tipo de ese concepto y el trámite solo acepta un pago de ese concepto; si la tarifa del pago es por nivel, además debe ser del mismo nivel que el trámite. Sin concepto, se acepta cualquier pago del tipo elegido. Los trámites ya solicitados conservan el concepto con el que se iniciaron.
4. Defina al menos dos **Etapas** con **Agregar etapa**. Cada etapa tiene su nombre, su **Avance** y **Se alcanza**: **Al recibir la solicitud**, **Cuando Pagos valida la tasa** o **Cuando el personal la avanza**. La primera es la solicitud recibida (0 %); con tasa, la segunda se alcanza cuando Pagos valida la tasa; la última llega al 100 % por acción del personal.
5. Elija qué ocurre **Al finalizar** (por ejemplo, emitir la constancia de notas o trasladar la matrícula).
6. En **Formato único de trámite (FUT)**, configure lo que el estudiante llena (su nombre y DNI salen de su cuenta):

| Opción | Uso |
|---|---|
| **Campos del FUT** y **Obligatorios** | Al crear un tipo, elegir **Al finalizar** marca los campos que esa acción necesita. Marque los campos que se piden: Matrícula, Idioma, Nivel, Programa, Mención (maestría o doctorado y su especialidad), Modalidad (3 a 100 caracteres), «Y expongo que» (10 a 1000 caracteres), Celular (9 dígitos) y Motivo (5 a 500 caracteres). Los no marcados como obligatorios son opcionales. La acción final exige sus propios campos como obligatorios (por ejemplo, el certificado de nivel exige Idioma y Nivel); si se quitan, el sistema no guarda el tipo. |
| **Meses sin estudiar** | Solo para la reclasificación: meses desde el fin de la última matrícula en el idioma (1 a 24; vacío: 2). |
| **Requiere haber finalizado** | Otro tipo de trámite que el estudiante debe tener finalizado para el mismo idioma y nivel. Por defecto, el certificado de nivel requiere la constancia de notas; puede quitarse. |
| **Programas que pueden solicitarlo** | Si elige programas, solo los estudiantes de esos programas (según su última matrícula en cada idioma) ven y solicitan el trámite. Vacío: todos. |
| **Adjuntos** | Hasta el máximo de adjuntos de **Parámetros del sistema** (5 por defecto) con su nombre y si son obligatorios (PDF, JPG o PNG del tamaño máximo fijado en **Parámetros del sistema**, 5 MB por defecto). Por defecto ningún trámite pide adjuntos: el certificado ya no requiere la constancia autenticada en Secretaría General, porque el sistema valida las actas cerradas y los documentos llevan código QR. |

7. Guarde.

Resultado esperado: si está marcado **Disponible para nuevas solicitudes**, el tipo aparece en el portal del estudiante. Los trámites ya iniciados conservan sus etapas, sus datos y sus adjuntos; los cambios solo afectan a las nuevas solicitudes.

## 3.4. Sistemas de evaluación

Finalidad: definir qué notas registra el docente y cómo se calcula la nota de cada ciclo.

Un sistema de evaluación es una lista de **columnas**, en el orden en que se muestran:

- **La ingresa el docente**: una nota que el docente registra en la pre-acta (por ejemplo, Comprensión auditiva o Examen parcial).
- **Calculada**: una nota que el sistema obtiene de columnas anteriores. Su **Cálculo** puede ser **Promedio simple** o **Promedio ponderado** (cada columna usada lleva un **Peso**, de 1 a 100). Su **Redondeo** puede ser **Sin redondear (dos decimales)** o **Al entero (0.5 hacia arriba)**.

Una columna se marca como **nota del ciclo**: con ella se decide si el estudiante aprueba (alcanza la **Nota mínima aprobatoria**) y es la nota que pasa al acta, al récord y a la constancia. En un sistema del régimen **Acelerado**, donde un grupo cubre dos ciclos, puede marcarse además la **nota del segundo ciclo**; si no se marca, el segundo ciclo recibe la misma nota que el primero.

Ejemplo, el sistema actual del CILN:

| Columna | Tipo | Cálculo |
|---|---|---|
| Comprensión auditiva, Expresión oral, Comprensión lectora, Expresión escrita | La ingresa el docente | — |
| Examen parcial, Examen final | La ingresa el docente | — |
| Promedio de habilidades | Calculada | Promedio simple de las cuatro habilidades, sin redondear |
| Promedio de exámenes | Calculada | Promedio simple de los dos exámenes, sin redondear |
| Nota final (nota del ciclo) | Calculada | Promedio simple de los dos promedios, al entero |

Ejemplo de un acelerado con una nota por ciclo: «Habilidades 1», «Examen 1», «Habilidades 2» y «Examen 2» las ingresa el docente; «Nota del primer ciclo» es el promedio de las dos primeras y «Nota del segundo ciclo» el de las dos últimas, cada una al entero.

1. Ingrese a **Configuración > Académico > Sistemas de evaluación** y pulse **Nuevo sistema de evaluación**.
2. Escriba el **Nombre**. Elija a qué se aplica: **Idioma**, **Nivel**, **Programa** y **Régimen** (Regular o Acelerado). Deje la opción «Todos» o «Regular y acelerado» para que se aplique a todos.
3. Indique la **Nota máxima** (por defecto 20) y la **Nota mínima aprobatoria** (por defecto 11).
4. Indique **Vigente desde** y, solo si corresponde, **Vigente hasta**.
5. En **Columnas**, pulse **Agregar columna** por cada una y escriba su **Código** y **Nombre**; elija el **Tipo**. Para una columna calculada, elija el **Cálculo**, el **Redondeo** y, con **Agregar columna que usa**, las columnas anteriores que combina (con su **Peso** si el cálculo es ponderado).
6. En **¿Es la nota del ciclo?**, marque **Sí** en una columna (y, en un sistema acelerado, **Sí, del segundo ciclo** en otra).
7. Guarde.

Resultado esperado: el sistema se aplica a los grupos que inician dentro de su vigencia. Si varios sistemas se aplican a un grupo, gana el más específico: primero el del régimen del grupo, luego el de su programa, su idioma y su nivel. La pre-acta del docente muestra las columnas que él ingresa; el acta y los reportes del estudiante muestran también las calculadas. Una vez vigente, solo pueden cambiarse el nombre y la fecha de fin: para cambiar columnas, cálculos o notas, cierre su vigencia y cree un sistema nuevo. Las actas ya cerradas conservan sus notas.

Mensajes posibles: «Marca una sola columna como nota del ciclo (y, en el acelerado, una sola como nota del segundo ciclo).», «La nota del segundo ciclo solo existe en un sistema para el régimen acelerado.», «La columna «nota» solo puede usar columnas que estén antes de ella, cada una una vez.», «Cada peso de la columna «nota» debe ser un número entero de 1 a 100.», «El programa Posgrado no tiene grupos de régimen Regular.».

![Figura 6. Sistema de evaluación con sus columnas](figuras/p06-sistema-de-evaluacion.png)

## 3.5. Condiciones laborales, grados académicos, firmantes y denominación del año

1. **Condiciones laborales** y **Grados académicos** (**Configuración > Docencia**): pulse **Nueva condición laboral** o **Nuevo grado académico**, escriba el **Nombre** y el **Orden en las listas** y guarde. El grado académico del docente determina su costo por hora (sección 5.4).
2. **Firmantes** (**Configuración > Institución**): pulse **Nuevo firmante** y registre el **Nombre con grado** (por ejemplo, «Dr. …»), el **Cargo** tal como se imprime bajo la firma; en **Firma**, marque los documentos que firma (**Certificados**, **Constancias**, **Actas**; al menos uno, y **Actas** solo con el permiso «Gestionar actas»); el **Orden en el documento** (de izquierda a derecha: en el certificado de nivel, el Vicerrector Académico con orden 1 y el Director del Centro de Idiomas con orden 2) y las fechas **Firma desde** y **Firma hasta** (vacía mientras siga en el cargo). Si no hay firmantes registrados, el certificado deja las líneas «Vicerrector Académico» y «Director del Centro de Idiomas» sin nombre. Los documentos no imprimen un espacio para el sello: se imprimen, se firman y se sellan a mano.
3. **Denominación del año** (**Configuración > Institución**): pulse **Nueva denominación**, indique el **Año** y la **Denominación** sin comillas; los documentos la imprimen entre « » sobre el título.

Resultado esperado: los documentos emitidos usan automáticamente los firmantes y la denominación vigentes en su fecha de emisión.

**Datos institucionales**: en **Configuración > Institución > Datos institucionales** edite los textos que imprimen los documentos y pulse **Guardar**. El nombre de la **Universidad** y del **Centro** se usa además en todos los PDF, en los correos y en la web pública:

| Sección | Campos |
|---|---|
| Encabezado | **Universidad**, **Lema**, **Centro**, **Resolución de creación** (el certificado de nivel la imprime bajo el centro) y **Base legal de la acreditación** (la acreditación la imprime bajo su título) |
| Pie de página | **Dirección**, **Teléfono y anexo**, **Web** y **Facebook** |
| Fecha y numeración | **Ciudad** que precede a la fecha; **Número de los documentos** (por defecto «N.º {numero:06}-{anio}») y **Número de la constancia de notas** (por defecto «N° {anio} - {numero:05}»). {anio} es el año y {numero:N} el correlativo con N dígitos (N de una o dos cifras). |

El formato del número queda fijo en cada documento al emitirlo. Los demás textos no se congelan, a diferencia de los firmantes: cada PDF, incluso el de un documento ya emitido, se imprime con el encabezado, el pie y la ciudad vigentes. El cambio queda en la auditoría.

## 3.6. Catálogos de vínculos, documentos, modalidades, bancos y grados del FUT

Finalidad: mantener las listas que antes eran fijas. Todos estos catálogos funcionan igual:

- El **Código** se genera al crear el registro y no cambia; el **Orden en las listas** decide su posición en los formularios.
- Un registro no se borra: se desmarca como disponible. Deja de ofrecerse en los formularios, pero se conserva en los registros que ya lo usan.
- Cada cambio queda en la auditoría.

*Tabla 5. Catálogos nuevos y sus campos*

| Catálogo (ruta) | Campos | Efecto |
|---|---|---|
| Tipos de vínculo (**Configuración > Personas**) | **Nombre**, **Orden en las listas**, **Disponible en los formularios y para tarifas nuevas**, **Vínculo de quien crea su cuenta en el portal** | El vínculo de cada persona elige su tarifa (sección 3.1). Uno solo es el vínculo por defecto, que recibe toda cuenta creada desde la web (por ejemplo, Público general). Para un vínculo nuevo, registre también sus tarifas en cada concepto. |
| Tipos de documento (**Configuración > Personas**) | **Nombre**, **Orden en las listas**, **El número tiene solo dígitos**, **Longitud mínima**, **Longitud máxima**, **Disponible en los formularios**, **Preseleccionado al crear una cuenta en el portal** | Regla de formato del número: sin **solo dígitos**, admite letras y dígitos, sin espacios ni guiones. Por ejemplo, el DNI tiene solo dígitos y longitud 8 a 8. La regla se aplica a los registros nuevos o editados; los números ya guardados no cambian. |
| Modalidades (**Configuración > Académico**) | **Nombre**, **Orden en las listas**, **Descripción en la página pública**, **Se dicta en aula**, **Admite enlace de clase en línea**, **Disponible para grupos nuevos** | Al crear un grupo, la modalidad decide si pide **Aula**, **Enlace** o ambos (sin aula, el enlace es obligatorio). **Se dicta en aula** y **Admite enlace** no cambian después de crear la modalidad. La descripción se muestra en la web pública. |
| Bancos (**Configuración > Docencia**) | **Nombre**, **Orden en las listas**, disponibilidad | Lista del campo **Banco** de la ficha docente (sección 5.1). |
| Grados del FUT (**Configuración > Trámites**) | **Grado (como se elige en el FUT)**, **Grado que otorga (como lo imprime la acreditación)**, **Orden en las listas**, **Disponible en el FUT** | El estudiante elige el grado en la mención del FUT (por ejemplo, «Maestría»); la constancia de acreditación imprime el grado que otorga (por ejemplo, «Maestro»). El trámite guarda el grado elegido, así que editarlo después no cambia los trámites ya solicitados. |

1. Ingrese al catálogo por su ruta y pulse el botón de nuevo registro, o **Editar** sobre uno existente.
2. Complete los campos de la Tabla 5.
3. Guarde.

Resultado esperado: el registro aparece en los formularios que usan el catálogo. Si el nombre ya existe, el sistema no lo guarda e indica el motivo.

El **programa por defecto** se marca en **Oferta académica > Programas** (sección 4.5).

## 3.7. Parámetros del sistema

Finalidad: ajustar los valores de funcionamiento que antes requerían un cambio técnico.

1. Ingrese a **Configuración > Institución > Parámetros del sistema**.
2. Cambie los valores de la Tabla 6. Cada campo acepta solo enteros dentro de su rango.
3. Pulse **Guardar**.

*Tabla 6. Parámetros del sistema*

| Sección | Campo | Por defecto | Rango |
|---|---|---|---|
| Clasificación | Vigencia de la solicitud de examen (días) | 30 | 1 a 365 |
| Clasificación | Puntaje máximo del examen (el puntaje se registra de 0 a este valor) | 100 | 10 a 100 |
| Asistencia docente | Llamado de inicio: abre (minutos antes del inicio); cierra al terminar la clase | 10 | 0 a 60 |
| Asistencia docente | Llamado de cierre: abre (minutos antes del fin) | 15 | 0 a 60 |
| Asistencia docente | Llamado de cierre: cierra (minutos después del fin) | 30 | 0 a 240 |
| Asistencia docente | Confirmación del delegado (minutos después del fin); no puede vencer antes de que cierre el llamado de cierre | 120 | 30 a 1440 |
| Archivos | Adjuntos por trámite | 5 | 1 a 10 |
| Archivos | Tamaño de cada adjunto (MB) | 5 | 1 a 10 |
| Archivos | Tamaño del recibo de pago (MB) | 5 | 1 a 10 |
| Archivos | Tamaño de la fotografía (MB) | 2 | 1 a 10 |
| Archivos | Lado mínimo de la fotografía (píxeles) | 200 | 100 a 1000 |
| Archivos | Lado máximo de la fotografía (píxeles) | 3000 | 1000 a 8000 |
| Portal público | Aviso de pocas vacantes (el grupo se resalta con estas vacantes o menos; 0 lo desactiva) | 5 | 0 a 50 |

Resultado esperado: se muestra «Parámetros del sistema guardados» y los nuevos valores rigen de inmediato en el portal, el panel y la web pública. El cambio queda en la auditoría.

Tenga en cuenta:

- Las ventanas de los llamados se aplican al consultar: un cambio también modifica cómo se leen las clases ya dictadas (por ejemplo, si un llamado de cierre quedó a tiempo).
- Cada solicitud de clasificación guarda el puntaje máximo vigente al registrarla, así que cambiarlo no altera los resultados ya registrados.

## 3.8. Textos de documentos, política de privacidad y contenido de la portada

Finalidad: editar los textos que imprimen los documentos y los que muestra la web pública, sin un cambio técnico. Las tres pantallas están en **Configuración > Institución** y se guardan con **Guardar**; cada cambio queda en la auditoría.

*Tabla 7. Textos editables*

| Pantalla | Secciones y campos | Comportamiento |
|---|---|---|
| Textos de documentos | **Constancias**: **Frase final de la constancia de matrícula** (también la imprime la constancia de inscripción), **Frase final de la constancia de notas**, **Nota de la escala de calificación**. **Acreditación de conocimiento de idioma extranjero**: **Modalidad al llevar ciclos**, **Modalidad por examen de suficiencia**, **Frase del acta final**, **Frase de validez** ({mencion}: grado de la mención). **Certificado de nivel**: **Texto del certificado** ({nivel}, {idioma} y {horas} se imprimen en negrita) | Cada documento guarda los textos vigentes al emitirlo: editarlos no cambia los documentos ya emitidos. Los emitidos antes de esta versión conservan la redacción original. |
| Política de privacidad | **Política de privacidad**: **Domicilio del titular del banco de datos** (se imprime donde dice {domicilio}) y **Texto de la política** (con negrita, cursiva, enlaces, títulos, listas y tablas; {universidad} y {centro} se toman de los datos institucionales). **Registro de cuenta**: **Frase de consentimiento** ({politica} se muestra como el enlace a la política) | Al cambiar el texto o el domicilio se actualiza la fecha de la política. El HTML que se escriba se muestra como texto. |
| Contenido de la portada | **Portada**: **Foto de portada (JPG, PNG o WebP)**, hasta 5 MB (sin foto se usa la del sistema), **Texto después de la lista de idiomas** (la lista de idiomas se arma sola desde el catálogo) y los textos del **Botón principal** y del **Botón secundario**. En cada sección de la web: **Etiqueta sobre el título**, **Título**, **Introducción** y **Mostrar sección**; una sección oculta desaparece de la web junto con sus enlaces del menú y del pie. **Pie de página y contacto**: **Descripción del centro**, **Correo de contacto**, **Horario de atención** y hasta 6 **Enlaces** (**Nombre** y **Dirección**, que debe empezar con https://); la dirección y el teléfono se toman de los datos institucionales. Además: **El centro** (**Presentación del centro**; párrafos separados por una línea en blanco), **Cómo matricularse** y **Examen de clasificación** (pasos con **Paso** y **Detalle**), **Ruta de aprendizaje** (**Nivel**, **Descripción** y **Al concluir el nivel podrá**), **Preguntas frecuentes** (**Pregunta** y **Respuesta**). Cada lista admite de 1 a 12 elementos, que se reordenan arrastrándolos | {lugares_de_pago} se reemplaza por los medios de pago activos del catálogo. La web pública muestra el cambio al guardar. |

Resultado esperado: se muestra el mensaje de guardado de la pantalla («Textos de documentos guardados», «Política de privacidad guardada» o «Contenido de la portada guardado»). Si un campo obligatorio queda vacío o excede su longitud, el sistema no guarda e indica el campo.

**Vista previa de la portada.** Antes de guardar, pulse **Vista previa** en **Contenido de la portada**: se abre en otra pestaña la web pública con los cambios y el aviso «Vista previa: los cambios aún no se guardaron.». Nada se publica hasta pulsar **Guardar**. Una foto nueva se ve recién después de guardar. Si el navegador bloquea la pestaña, use **Abrir vista previa** en el aviso. En la vista previa, cada sección tiene un enlace **Editar**: lo lleva a la pestaña del formulario, en esa sección, sin perder los cambios que aún no guardó.

**Atajos «Editar».** Si inició sesión en el panel con el permiso para configurar la portada y visita la web pública, cada sección muestra un enlace **Editar** que abre esa parte del formulario. Los visitantes, los estudiantes y el personal sin ese permiso no lo ven.

# 4. OFERTA ACADÉMICA

## 4.1. Idiomas y ciclos

Finalidad: registrar los idiomas que ofrece el CILN y sus ciclos.

1. Ingrese a **Oferta académica > Idiomas y ciclos** y pulse **Nuevo idioma**, o **Editar** sobre uno existente.
2. Complete **Código** (no cambia después de crearlo), **Nombre**, **Currículo** (código del plan de estudios vigente, por defecto «01», que se imprime en la constancia de notas) y, si corresponde, **Bandera** y **Lengua originaria**. Deje marcado **Disponible para matrícula**.
3. Guarde y, en la sección **Plan de estudios**, pulse **Nuevo ciclo** para registrar cada **Ciclo** con su **Nombre**, **Nivel** (Básico, Intermedio o Avanzado) y **Horas lectivas**.

Resultado esperado: el idioma y sus ciclos quedan disponibles para crear grupos. Si se desactiva un idioma, sus grupos dejan de ofrecerse en la matrícula y no se pueden crear grupos nuevos.

![Figura 7. Idioma y sus ciclos](figuras/p07-idioma-ciclos.png)

## 4.2. Aulas

1. Ingrese a **Oferta académica > Aulas** y pulse **Nueva aula**.
2. Complete **Código**, **Nombre**, **Ubicación** y **Capacidad (estudiantes)**; deje marcado **Disponible para nuevos grupos**.
3. Guarde.

Resultado esperado: el aula puede asignarse a los grupos presenciales.

## 4.3. Periodos y sus estados

Finalidad: crear el periodo académico (mensual) y conducirlo por sus estados.

1. Ingrese a **Oferta académica > Periodos** y pulse **Nuevo periodo**.
2. Complete **Año**, **Número (mes o periodo)** y **Nombre**; las fechas de **Inicio de clases** y **Fin de clases**; **Matrícula desde** y **Matrícula hasta** (la matrícula no puede terminar después del fin de clases); opcionalmente **Matrícula extemporánea hasta** (posterior a «Matrícula hasta» y no posterior al fin de clases); y **Actas finales desde** y **Actas finales hasta**, que son los días en que el docente puede validar y cerrar el acta final.
3. Guarde. El periodo se crea en estado **Planificado**.

Matrícula extemporánea: si el periodo la tiene, los grupos siguen abiertos a la matrícula hasta esa fecha. El pago de matrícula hecho en ese plazo debe incluir el recargo de **Configuración > Pagos > Configuración de pagos** (por ejemplo, S/ 10.00 + 20 % = S/ 12.00). Al validar un pago de matrícula por la tarifa sin recargo, el sistema pide la justificación del monto.
4. Cuando corresponda, use en el listado los botones de cambio de estado, que siempre piden confirmación.

*Tabla 8. Estados del periodo*

| Estado | Botón que lo activa | Efecto |
|---|---|---|
| Planificado | (al crear) | Se preparan grupos y horarios; aún no hay matrícula. |
| Matrícula abierta | **Abrir matrícula** | Los estudiantes pueden matricularse en los grupos abiertos del periodo. |
| En curso | **Iniciar clases** | Se cierra la matrícula del periodo y comienzan las clases. Si la fecha de inicio de clases aún no llega, la confirmación advierte que se iniciarán antes. |
| Cerrado | **Cerrar periodo** | Los grupos cerrados pasan a Finalizado y los programados se cancelan. No se puede cerrar mientras algún grupo esté **Abierto**. |

Resultado esperado: el estado del periodo se actualiza en el listado.

![Figura 8. Listado de periodos con sus estados](figuras/p08-periodos.png)

> **Advertencia:** «Cerrar periodo» no se puede deshacer. Un periodo cerrado no puede volver a abrirse. Antes de confirmar, verifique que todas las actas finales del periodo estén cerradas (sección 8.1).

![Figura 9. Confirmación para cerrar un periodo](figuras/p09-cerrar-periodo.png)

## 4.4. Grupos y horarios

Finalidad: crear los grupos de cada ciclo y registrar sus horarios.

1. Ingrese a **Oferta académica > Grupos** y pulse **Nuevo grupo**.
2. En **Grupo**, elija el **Periodo** y el **Idioma y ciclo** (no cambian después de crear el grupo).
3. Elija el **Programa** (se propone el programa por defecto, sección 4.5) y complete **Código de grupo**, **Modalidad** (del catálogo de modalidades, sección 3.6), **Acelerado** si corresponde, **Aula** y/o **Enlace de la clase virtual** según la modalidad, **Cupo máximo** y **Cupo mínimo de apertura**.
4. En **Horario**, pulse **Agregar día de clase** por cada día, con su **Día**, **Desde** y **Hasta**.
5. En **Docente** (visible con el permiso «Gestionar docencia»), elija al docente o déjelo **Por asignar**. Solo aparecen los docentes activos habilitados para el idioma hasta el nivel del ciclo; el horario debe estar dentro de su disponibilidad (sección 5.2).
6. Pulse **Crear**. El grupo, su horario y su docente se guardan juntos: si el docente no puede tomar el grupo (por ejemplo, una sesión fuera de su disponibilidad), el sistema muestra el motivo y no guarda nada. Se abre la ficha del grupo, que muestra al **Docente**; desde ella, **Asignar docente** o **Cambiar docente** lo asigna o reemplaza con las mismas reglas, y la sección **Horarios** permite agregar, editar o quitar días.
7. Para conducir el grupo, use el menú **Estado** de su tarjeta en el listado (o de la cabecera de la página de edición) con las opciones **Abrir**, **Cerrar**, **Reabrir**, **Finalizar** o **Cancelar grupo**, que piden confirmación. Para abrir un grupo, este debe tener horario y docente asignado, y su periodo debe estar **Planificado** o en **Matrícula abierta**. Si al cerrarlo no alcanza el cupo mínimo, el sistema pregunta si desea iniciarlo de todas formas. No se puede finalizar mientras la matrícula del periodo esté abierta.

*Tabla 9. Estados del grupo*

| Estado | Significado |
|---|---|
| Programado | Grupo creado; aún no recibe matrículas. |
| Abierto | Recibe matrículas mientras tenga vacantes. |
| Cerrado | No recibe nuevas matrículas; puede reabrirse. |
| Finalizado | Terminó el dictado; queda para el acta final. |
| Cancelado | No se dicta. Solo se cancela un grupo sin estudiantes matriculados. |

El sistema rechaza un grupo que el régimen de su programa no admite; por ejemplo, Posgrado solo admite grupos acelerados.

Un grupo **acelerado** cubre dos ciclos seguidos del mismo nivel con una sola nota:

- Al marcar **Acelerado**, el formulario muestra el segundo ciclo («Cubre también: Básico II»), que el sistema asigna solo: es el ciclo activo siguiente del mismo idioma y nivel.
- No se puede crear un grupo acelerado en el último ciclo de un nivel.
- El acta final es una sola; al cerrarla, el récord y la constancia muestran los dos ciclos con la misma nota y las horas de cada uno, y quien aprueba queda habilitado para el ciclo que sigue al segundo.

Resultado esperado: el listado muestra cada grupo con su programa, estado, docente, matriculados y vacantes. Cada horario del grupo puede corregirse con **Editar** o quitarse con **Borrar**. Desde la vista del grupo, quien tiene el permiso «Gestionar actas» puede usar **Pre acta (PDF)** para obtener la pre acta con los estudiantes matriculados.

![Figura 10. Listado de grupos](figuras/p10-grupos.png)

![Figura 11. Grupo con sus horarios](figuras/p11-grupo-horarios.png)

## 4.5. Programas

Finalidad: definir los programas del CILN y el régimen de ciclo que admiten sus grupos.

El sistema se instala con tres programas, todos editables:

*Tabla 10. Programas iniciales*

| Código | Programa | Régimen de ciclo |
|---|---|---|
| JA | Jóvenes y Adultos | Regular y acelerado |
| NINOS | Niños | Regular y acelerado |
| POSGRADO | Posgrado | Solo acelerado |

1. Ingrese a **Oferta académica > Programas** y pulse **Nuevo programa**, o **Editar** sobre uno existente.
2. Complete **Código** (no cambia después de crearlo), **Nombre** y **Régimen de ciclo** (Solo regular, Solo acelerado, o Regular y acelerado). Deje marcado **Disponible para nuevos grupos**.
3. Marque **Programa por defecto** en el programa que debe proponerse para los grupos nuevos (al instalar, Jóvenes y Adultos). Ese programa también se imprime en los documentos de los grupos anteriores a los programas. Solo un programa es el programa por defecto y debe estar disponible: al marcar otro, el anterior deja de serlo, y para quitar la marca a uno hay que marcar antes otro.
4. Guarde.

Resultado esperado: el programa puede elegirse al crear un grupo. Un programa desactivado no recibe grupos nuevos, pero conserva los que ya tiene.

# 5. DOCENCIA

## 5.1. Docentes

Finalidad: registrar a un docente, con su persona y su cuenta, en una sola pantalla.

1. Ingrese a **Docencia > Docentes** y pulse **Crear docente**. También puede pulsar **Registrar como docente** en la ficha de la persona (sección 6.1), que abre este formulario con la persona ya elegida.
2. En **Persona**, elija el **Tipo de documento** y escriba el **Número de documento**. Si la persona ya está registrada, el sistema muestra su nombre («Ya está registrada: se usarán sus datos»). Si no lo está, complete allí mismo sus apellidos, nombres, datos de contacto, **Tipo de vínculo** y, si firmó su autorización, el consentimiento de datos personales; esto requiere el permiso «Gestionar personas».
3. En la sección **Docente**, complete **Correo institucional**, **Condición laboral** (obligatoria) y **Grado académico**; en **Datos para el expediente**, **RUC**, **Banco** (del catálogo de bancos, sección 3.6) y **Número de cuenta**.
4. En **Perfil académico**, agregue cada **Idioma** habilitado para dictar con su **Nivel máximo autorizado**.
5. En **Cuenta de acceso**, active **Crear ahora su cuenta de acceso** y escriba la **Contraseña** dos veces si desea entregarle la cuenta en el momento; su usuario será su número de documento. Puede crearla después desde su ficha.
6. Pulse **Crear**. La persona, el docente y la cuenta se guardan juntos: si algo no procede (por ejemplo, un RUC ya registrado), no se guarda nada. El sistema muestra «Docente registrado» y, si creó la cuenta, el usuario.

Para cambiar la situación de un docente, use **Más acciones > Cambiar estado** en su ficha y elija **Activo**, **Inactivo** o **De licencia**. Un docente con grupos a cargo no puede pasar a Inactivo ni a De licencia hasta que se reasignen sus grupos.

Resultado esperado: la ficha del docente lleva su nombre como título, con su código (DOC-…) y documento debajo, y muestra la sección **Cuenta de acceso** (usuario, estado y correo). Desde la cabecera se usan **Crear cuenta** o, en **Más acciones**, **Cambiar contraseña**, **Reactivar la cuenta** y **Dar de baja la cuenta** (sección 11.2). El número de cuenta bancaria se muestra enmascarado.

![Figura 12. Vista de un docente](figuras/p12-docente.png)

## 5.2. Disponibilidad horaria

1. En la vista del docente, abra la pestaña **Disponibilidad horaria**.
2. Pulse **Agregar franja** y elija el **Día**, **Desde** y **Hasta**. Use **Editar** o **Quitar** para corregir una franja.

Resultado esperado: la disponibilidad queda registrada como referencia para asignar grupos.

## 5.3. Asignar grupo (carga académica)

También puede asignar el docente desde la ficha del grupo (sección 4.4).

1. En la vista del docente, abra la pestaña **Carga académica**. La cabecera muestra el total de horas asignadas por periodo.
2. Pulse **Asignar grupo**, elija el **Grupo** y pulse **Enviar**.
3. Para retirar una asignación, pulse **Quitar** en la fila; el grupo quedará sin docente asignado.

Resultado esperado: el grupo aparece en la carga con su periodo, semanas, días de dictado y horas totales, y el docente lo ve en su portal. Un grupo cancelado o finalizado ya no admite cambios de docente.

![Figura 13. Carga académica del docente](figuras/p13-docente-carga.png)

![Figura 14. Asignar grupo a un docente](figuras/p14-asignar-grupo.png)

## 5.4. Costos por hora

1. Ingrese a **Docencia > Costos por hora** y pulse **Nuevo costo por hora**.
2. Elija el **Grado académico**, el **Costo por hora**, la fecha **Vigente desde** y, solo si corresponde, **Vigente hasta**.
3. Guarde.

Resultado esperado: el nuevo costo se aplica desde su vigencia; si el grado tenía un costo abierto, se cierra el día anterior. Solo se elimina un costo que aún no entra en vigencia.

## 5.5. Control de clases

**Docencia > Control de clases** muestra las clases registradas por los docentes, con los dos llamados de asistencia y la confirmación del delegado del curso. Filtre por **Periodo**, **Docente**, **Grupo** o **Estado** para revisar la **Fecha**, el **Horario**, el **Tema dictado**, los **Presentes al cierre**, el **Estado** de la clase, quién la confirmó (**Confirmada por**) y la **Observación** del delegado. Los estados son: registrada, con llamado de inicio, sin llamado de cierre, por confirmar, sin confirmar, confirmada, observada y registro posterior (su significado se explica en el manual del docente).

![Figura 15. Control de clases](figuras/p15-control-de-clases.png)

## 5.6. Expediente de horas

Finalidad: obtener las horas dictadas por docente en un mes y su monto referencial.

1. Ingrese a **Docencia > Expediente de horas**.
2. Elija el **Mes** y el **Año**.
3. Revise, por docente, las horas, el costo por hora, el monto y las clases confirmadas y no confirmadas.
4. Pulse **PDF** en la fila de un docente para descargar su expediente, o **Descargar CSV del mes** para el consolidado.

Resultado esperado: se descarga el archivo. El monto es bruto y referencial, sin retenciones de ley; las clases no confirmadas son solo informativas.

![Figura 16. Expediente de horas por docente](figuras/p16-expediente-de-horas.png)

## 5.7. Carga docente

**Administración > Reportes**, pestaña **Carga docente**, presenta las horas asignadas y dictadas de cada docente en el periodo. Se filtra y exporta igual que los reportes de la sección 10.2.

# 6. CAJA Y PAGOS

## 6.1. Registrar persona

Finalidad: registrar a un estudiante, docente o tercero antes de registrar su pago.

1. Ingrese a **Matrícula > Personas** y verifique con **Buscar** que la persona no esté registrada.
2. Pulse **Registrar persona**.
3. En **Documento de identidad**, elija el **Tipo de documento** y escriba el **Número de documento**.
4. En **Datos personales**, complete **Apellido paterno**, **Apellido materno**, **Nombres**, **Fecha de nacimiento**, **Celular**, **Correo personal** y **Tipo de vínculo** con la UNASAM (del catálogo de tipos de vínculo). El **Número de documento** debe cumplir la regla de formato del tipo de documento elegido (sección 3.6).
5. Si la persona firmó su autorización para el tratamiento de sus datos personales (Ley N.° 29733), marque **La persona aceptó el tratamiento de sus datos personales**. El sistema guarda la fecha y hora del consentimiento; si no lo marca, la ficha muestra "Sin consentimiento registrado" y puede registrarlo más adelante con **Editar**. Una vez registrado, el consentimiento no se borra al editar la ficha.
6. Pulse **Crear**.

Resultado esperado: la persona queda registrada. Si el documento ya existe, el sistema lo informa y no crea un duplicado. Para un docente o un estudiante nuevo no hace falta este paso: **Crear docente** (sección 5.1) y **Matricular en ventanilla** (sección 7.2) registran a la persona en el mismo formulario.

**Ficha de la persona.** Al abrir a una persona, la ficha lleva su nombre como título y reúne en pestañas lo que el sistema sabe de ella, según los permisos de su cuenta:

*Tabla 11. Pestañas de la ficha de la persona*

| Pestaña | Contenido | Permiso |
|---|---|---|
| Datos personales | Documento, nombres, contacto, consentimiento, fotografía | Ver personas |
| Matrículas | Grupo, ciclo, condición, estado y fecha de cada matrícula | Ver matrícula |
| Pagos | Concepto, operación, medio, monto, fecha y estado | Ver pagos |
| Documentos | Constancias y certificados emitidos | Gestionar documentos |
| Trámites | Trámites iniciados, con su etapa y estado | Gestionar trámites |

Cada fila abre su registro. En la cabecera, los atajos **Matricular** (abre la matrícula en ventanilla con la persona elegida), **Registrar pago** (abre el registro de pago con la persona como pagador), **Registrar como docente** o **Ver ficha de docente**, **Editar** y el menú **Cuenta del portal**.

![Figura 17. Registrar persona](figuras/p17-registrar-persona.png)

## 6.2. Registrar pago

Finalidad: registrar un pago que la persona presenta en ventanilla.

1. Ingrese a **Pagos > Pagos** y pulse **Registrar pago**.
2. En **Persona**, busque por número de documento o por apellidos y nombres.
3. Elija el **Concepto TUPA/TUSNE**. Si sus tarifas varían por nivel o programa, aparecen **Nivel pagado** (obligatorio) y **Programa** (solo si el pago es la tarifa de un programa, por ejemplo Posgrado).
4. Elija el **Medio de pago**, escriba el **N.° de recibo u operación**, la **Fecha de pago** (no puede ser posterior a hoy) y el **Monto** que figura en la boleta, con dos decimales como máximo.
5. Adjunte el **Recibo escaneado (JPG, PNG o PDF)**, del tamaño máximo fijado en **Parámetros del sistema** (5 MB por defecto).
6. Pulse **Crear**.

Resultado esperado: el pago queda en estado **Pendiente** hasta su validación. El sistema rechaza un número de operación repetido para el mismo medio de pago y un concepto sin tarifa vigente en la fecha de pago para el vínculo de la persona.

![Figura 18. Registrar pago](figuras/p18-registrar-pago.png)

Los pagos que los estudiantes registran desde su portal también llegan a este listado en estado **Pendiente**. El listado se filtra por **Estado**, **Concepto**, **Medio** y fechas de pago (**Pagado desde**, **Pagado hasta**), y cada fila ofrece directamente **Validar**, **Rechazar** o **Anular** según el estado del pago y los permisos de la cuenta.

![Figura 19. Listado de pagos](figuras/p19-pagos.png)

## 6.3. Validar un pago

Finalidad: confirmar un pago contra la boleta original.

1. En **Pagos > Pagos**, ubique el pago **Pendiente** y pulse **Ver**.
2. Revise la **Persona**, el **Concepto TUPA/TUSNE**, el **Monto** frente a la **Tarifa vigente**, el **Medio de pago**, el **N.° de recibo u operación** y la **Fecha de pago**. Use **Descargar recibo** para ver el comprobante escaneado.
3. Pulse **Validar**.
4. Marque la casilla **Tengo la boleta original y coincide con el número, la fecha y el monto registrados**. Valide solo con la boleta original en la mano.
5. Si el monto pagado es distinto de la tarifa, el sistema lo advierte y pide la **Justificación del monto distinto de la tarifa**; escríbala. Para un pago de matrícula hecho en el plazo extemporáneo, el monto esperado es la tarifa más el recargo vigente al validar (sección 4.3).
6. Pulse **Validar**.

Resultado esperado: el pago pasa a **Validado** y habilita el servicio correspondiente (por ejemplo, la matrícula o el avance de un trámite). Quedan registrados el usuario que validó y la fecha. Si es el primer pago validado de una cuenta creada desde la web, esa cuenta queda habilitada automáticamente (sección 6.7).

![Figura 20. Detalle de un pago pendiente](figuras/p20-pago-detalle.png)

![Figura 21. Validar pago con la boleta original](figuras/p21-validar-pago.png)

## 6.4. Rechazar un pago

1. En la vista del pago **Pendiente**, pulse **Rechazar**.
2. Escriba el **Motivo del rechazo** y confirme.

Resultado esperado: el pago pasa a **Rechazado** y la persona ve el motivo en su portal.

## 6.5. Anular un pago

Solo la cuenta con el permiso «Anular pagos» (administración) ve este botón.

1. En el listado o la vista de un pago **Validado**, pulse **Anular**.
2. Escriba el **Motivo de la anulación** y confirme.

Resultado esperado: el pago pasa a **Anulado**. Los pagos nunca se eliminan: el registro se conserva con el motivo, el usuario y la fecha de anulación.

*Tabla 12. Estados del pago*

| Estado | Significado |
|---|---|
| Pendiente | Registrado; espera la revisión con la boleta original. |
| Validado | Confirmado por caja; habilita el servicio. |
| Rechazado | No corresponde; la persona ve el motivo. |
| Anulado | Dejado sin efecto por administración, con motivo. |

## 6.6. Reporte de recaudación

Finalidad: obtener la recaudación validada para la DGF y Tesorería.

1. Ingrese a **Administración > Reportes** y elija la pestaña **Reporte de recaudación**.
2. Indique **Pagos desde** y **Pagos hasta**; opcionalmente elija el **Concepto** y el **Periodo académico**.
3. Revise el total recaudado, el ticket promedio, la recaudación por concepto TUPA/TUSNE, por idioma y nivel, y el detalle por estudiante.
4. Pulse **Descargar PDF** o **Descargar CSV**.

Resultado esperado: se descarga el reporte con los pagos validados del rango elegido.

![Figura 22. Reporte de recaudación](figuras/p22-reporte-de-recaudacion.png)

## 6.7. Cuentas del portal del estudiante

Resumen: cualquier persona crea su cuenta en la web sin verificar el correo, pero queda **no habilitada** hasta que caja valida su primer pago. Quien ya figura en el CILN no puede registrarse en la web: el personal le crea la cuenta en la oficina.

*Tabla 13. Origen y estado de las cuentas del portal*

| Caso | Cómo se crea | Estado inicial | Qué ve en el portal |
|---|---|---|---|
| Persona nueva que se registra en la web | La propia persona, con **Crear cuenta** | No habilitada | Inicio, Modificar datos, Subir fotografía, Preferencias y Registro de pagos |
| Persona que ya figura en el CILN (estudiante anterior, registrado en ventanilla) | El personal, con **Crear cuenta del portal** | Habilitada | Todas las opciones |
| Docente | El personal, en **Seguridad > Cuentas de docentes** (sección 11.2): usuario = DNI, contraseña entregada en la oficina | Habilitada | Portal Docente |
| Persona registrada por el personal en **Matrícula > Personas** | Registro en ventanilla (sección 6.1) | Habilitada | Todas las opciones, una vez creada su cuenta |

**Habilitación automática.** Al validar el primer pago de una persona no habilitada (sección 6.3), el sistema registra la fecha de habilitación y la persona ve todas las opciones del portal desde su siguiente pantalla. No hay un botón para habilitar a mano: si una persona necesita la cuenta habilitada, debe tener un pago validado.

**Cuenta habilitada en la ficha.** En **Matrícula > Personas**, la vista de la persona muestra **Cuenta habilitada**: la fecha y hora de habilitación, o «Pendiente: se habilita al validar su primer pago». Úselo para orientar a quien dice que no ve la matrícula o los trámites.

**Crear cuenta del portal.** Finalidad: dar acceso al portal (y al Portal Docente, si es docente) a una persona que ya figura en el CILN y no tiene cuenta.

1. Verifique en persona el documento de identidad de quien solicita la cuenta.
2. En **Matrícula > Personas**, abra la vista de la persona y confirme que su **Correo personal** sea el correcto; si no, corríjalo con **Editar**.
3. Pulse **Crear cuenta del portal**. El botón solo aparece si la persona aún no tiene cuenta y su cuenta del personal tiene el permiso «Gestionar personas».
4. Confirme en el recuadro.

Para los docentes use **Seguridad > Cuentas de docentes** (sección 11.2), que no necesita correo.

Resultado esperado: se muestra «Cuenta creada y enlace enviado». La cuenta se crea con el correo personal de la ficha y la persona recibe un enlace para crear su contraseña (vence a los 60 minutos; si venció, la persona pide otro con **¿Olvidaste tu contraseña?**). El personal nunca conoce ni escribe la contraseña. El sistema no crea la cuenta si la persona ya tiene una («Esta persona ya tiene una cuenta del portal.»), si no tiene correo personal («Registra primero el correo personal de la persona.») o si el correo ya pertenece a otra cuenta («Ya existe una cuenta con ese correo.»).

**Dar de baja la cuenta.** Finalidad: impedir que una persona ingrese al portal sin borrar nada. Úselo para una cuenta duplicada, una contraseña filtrada o un uso indebido. No dé de baja a un estudiante solo porque dejó de estudiar: más adelante puede necesitar su constancia o su certificado.

1. En **Matrícula > Personas**, abra la vista de la persona y pulse **Dar de baja la cuenta**.
2. Escriba el **Motivo** y confirme.

Resultado esperado: se muestra «Cuenta dada de baja». La persona ya no puede ingresar al portal, al Portal Docente ni a la API, y se cierran sus sesiones; al intentar ingresar ve el mismo mensaje que con una contraseña incorrecta. Sus datos, pagos, matrículas y documentos se conservan. Para devolverle el acceso, pulse **Reactivar la cuenta**. Las cuentas del personal se dan de baja desde **Seguridad > Usuarios del personal** («Es una cuenta del personal: gestiónala desde Usuarios del personal.»).

# 7. MATRÍCULA Y CLASIFICACIÓN

## 7.1. Solicitudes de clasificación

Finalidad: registrar el resultado del examen de clasificación que solicita un estudiante.

1. Ingrese a **Matrícula > Solicitudes de clasificación**. El listado muestra el estado de cada solicitud (**Pendiente de evaluación**, **Evaluado**, **Expirado** o **Pago anulado**) y la fecha en que vence (**Vence el**). Las solicitudes **Pendiente de evaluación** muestran el botón **Registrar resultado**.
2. Pulse **Registrar resultado**.
3. Indique la **Fecha del examen**, el **Puntaje**, el **Ciclo asignado** y, si corresponde, **Observaciones**.
4. Pulse **Enviar**.

Resultado esperado: la solicitud pasa a **Evaluado** y el ciclo asignado queda habilitado para la matrícula del estudiante en ese idioma. Los ciclos anteriores al asignado quedan registrados como aprobados con forma de aprobación **Clasificación**, sin nota ni horas lectivas; figuran en el récord y en la constancia de notas y cuentan para el certificado del nivel, cuyo promedio se calcula solo con los ciclos que tienen nota.

![Figura 23. Registrar resultado de clasificación](figuras/p23-registrar-resultado.png)

Para corregir un resultado ya registrado, use **Corregir resultado** en la solicitud, indique los nuevos datos y el **Motivo de la corrección**. Si cambia el ciclo asignado, la corrección solo procede cuando el ciclo habilitado aún proviene de esta clasificación y el estudiante no se matriculó en él; en caso contrario, el sistema rechaza la corrección e indica usar «Ajustar ciclo habilitado» (sección 7.5). Si la corrección sube el ciclo asignado, se registran también los ciclos que faltaban; si lo baja, los ciclos que dejan de saltarse quedan anulados con el motivo «Corrección del examen de clasificación» y el usuario que corrigió; no se eliminan, pero ya no figuran en el récord, en la constancia ni en el certificado. Los ciclos que el estudiante ya aprobó con nota en un acta no se registran por clasificación y conservan su nota.

## 7.2. Matricular en ventanilla

Finalidad: matricular a un estudiante desde el panel, por ejemplo para cargar el ciclo que ya está en curso. Requiere el permiso «Gestionar matrícula». El estudiante también puede matricularse desde su portal.

1. Si el estudiante cursa un ciclo mayor al primero, ajuste antes su ciclo en **Matrícula > Ciclos habilitados** (sección 7.5).
2. Ingrese a **Matrícula > Matrículas** y pulse **Matricular en ventanilla**, o pulse **Matricular** en la ficha de la persona (sección 6.1).
3. En **Estudiante**, escriba su documento de identidad. Si está registrado, el sistema muestra su nombre; si no, complete sus datos allí mismo (requiere el permiso «Gestionar personas»). En **Matrícula**, elija el **Grupo** (grupos con matrícula abierta del ciclo para el que está habilitado; a un estudiante nuevo se le ofrecen los grupos de inicio).
4. En **Pago**, elija una opción:
   - **Pago validado del estudiante**: elija uno de sus pagos de matrícula ya validados por caja que aún no se usaron. Es el camino normal. Si no tiene ninguno, el sistema lo indica («Sin pagos de matrícula validados»): registre antes su pago en **Pagos** y, cuando otra persona lo valide, vuelva a matricularlo.
   - **Pago anterior al sistema**: para un pago que el estudiante hizo antes de usar el sistema. Complete **Concepto TUPA/TUSNE**, **Medio de pago**, **N.° de recibo u operación**, **Fecha de pago**, el monto y el **Motivo** (por ejemplo, «Pago de noviembre de 2026, anterior al sistema»). El **Recibo escaneado** es opcional. Esta opción solo aparece si **Permitir pagos anteriores al sistema** está activado y su cuenta tiene además el permiso «Registrar pagos».
5. Pulse **Matricular**.

Resultado esperado: se muestra «Matrícula registrada». Con un pago anterior al sistema, el pago queda registrado como validado por usted, con el motivo, y la cuenta del portal del estudiante queda habilitada. Si la matrícula no procede (grupo sin vacantes, ciclo no habilitado o matrícula repetida), el sistema muestra el motivo y no registra el pago ni, si era nueva, a la persona.

## 7.3. Matrículas: ficha

1. Ingrese a **Matrícula > Matrículas** y pulse **Ver** en la matrícula. El listado se filtra por **Estado** y **Grupo**.
2. Revise el **Estudiante**, el **Grupo**, el **Horario**, la **Condición**, el **Estado**, el pago asociado y la sección **Traslados y retiros**.
3. Pulse **Ficha de matrícula** para abrir la ficha en PDF.

Resultado esperado: la ficha se abre en una pestaña nueva, desde donde puede imprimirse o descargarse; incluye un código para verificar su autenticidad.

![Figura 24. Vista de una matrícula](figuras/p24-matricula.png)

## 7.4. Trasladar y retirar

**Trasladar** cambia al estudiante a otro grupo del mismo periodo y ciclo:

1. En la matrícula (o en su fila del listado), pulse **Trasladar**.
2. Elija el **Grupo de destino** (grupos del mismo periodo y ciclo, abiertos o cerrados, con vacantes) y escriba el **Motivo del traslado**.
3. Pulse **Enviar**.

**Retirar** registra el retiro oficial:

1. En la matrícula (o en su fila del listado), pulse **Retirar**.
2. Escriba el **Motivo del retiro** y confirme.

Resultado esperado: el movimiento queda en **Traslados y retiros** con el usuario y la fecha. El retiro libera la vacante del grupo y no se puede deshacer.

![Figura 25. Trasladar una matrícula](figuras/p25-trasladar.png)

## 7.5. Ciclos habilitados

Finalidad: ajustar el ciclo en el que puede matricularse un estudiante, por ejemplo cuando continúa desde el sistema anterior o para corregir una ubicación.

1. Ingrese a **Matrícula > Ciclos habilitados**.
2. Pulse **Ajustar ciclo habilitado** (o **Ajustar** en la fila de un estudiante).
3. Elija el **Estudiante**, el **Idioma** y el **Ciclo habilitado**, y escriba el **Motivo del ajuste**.
4. Si habilita un ciclo que el estudiante ya aprobó o uno anterior, marque **Confirmo habilitar un ciclo que el estudiante ya aprobó o uno anterior**.
5. Pulse **Enviar**.

Resultado esperado: el ciclo habilitado se actualiza y el cambio queda en la auditoría con el motivo.

![Figura 26. Ajustar el ciclo habilitado](figuras/p26-ajustar-ciclo-habilitado.png)

# 8. CERTIFICACIÓN

## 8.1. Actas pendientes

**Certificación > Actas pendientes** lista, por periodo, los grupos que terminaron sus clases, o están abiertos dentro del plazo de actas, y aún no tienen su acta final cerrada, con su docente y el **Plazo de actas**. Úsela antes de cerrar un periodo: cuando todas las actas están cerradas, la pantalla lo indica.

![Figura 27. Actas pendientes](figuras/p27-actas-pendientes.png)

## 8.2. Actas finales

El docente valida y cierra el acta final de su grupo desde su portal. En **Certificación > Actas finales** el personal consulta las actas (el botón **Actas pendientes** de la cabecera lleva a la sección 8.1) y realiza las siguientes acciones desde la vista de cada acta:

- **Acta en PDF**: disponible con el acta cerrada; abre el acta con las calificaciones, los firmantes y un código de verificación.
- **Emitir constancias de notas**: emite a cada estudiante del acta la constancia de notas del nivel del acta. Quien ya recibió una de ese nivel después del cierre del acta no recibe otra; quien no aprobó ningún ciclo del nivel figura en el aviso con el motivo.
- **Autorizar rectificación**: reabre el acta para que el docente a cargo corrija las notas y la vuelva a validar.

Para autorizar una rectificación:

1. Abra el acta en estado **Cerrada** y pulse **Autorizar rectificación**.
2. Escriba el **Motivo de la rectificación** y pulse **Enviar**.

Resultado esperado: el acta pasa a **En rectificación** y el motivo queda en la sección **Rectificaciones** y en la auditoría. Cuando el docente la vuelve a cerrar, las constancias emitidas con las notas anteriores se marcan **Por revisar**.

![Figura 28. Acta final](figuras/p28-acta-final.png)

![Figura 29. Autorizar rectificación de un acta](figuras/p29-autorizar-rectificacion.png)

## 8.3. Constancias y certificados

Finalidad: emitir y controlar los documentos oficiales del CILN.

Para emitir un documento individual:

1. Ingrese a **Certificación > Constancias y certificados** y pulse **Emitir documento**.
2. Elija el **Tipo de documento**: Constancia de matrícula, Constancia de notas o Certificado de culminación de nivel.
3. Elija el **Estudiante** (busque por documento o por apellidos y nombres) y, según el tipo, la **Matrícula activa**, el **Idioma** y el **Nivel** (o **Nivel culminado** para el certificado).
4. Pulse **Enviar**.

El **certificado de nivel** es horizontal y sigue el texto del certificado real: Vicerrectoría Académica, el centro y su resolución de creación, «Otorgado a:», el nivel, el idioma y el total de horas académicas, la fotografía del estudiante si la registró, el **N° Registro**, las firmas del Vicerrector Académico y del Director, y el código QR.

La **acreditación de conocimiento de idioma extranjero** (trámite de posgrado) es vertical y sigue el formato real: base legal, otorgado a, idioma, nivel, **Calificativo** (promedio del nivel redondeado, en letras y cifras), **Modalidad** («Examen de Suficiencia» si el nivel se aprobó por suficiencia; si no, «Programa de Enseñanza de Idioma Extranjero»), **Fecha** en que se completó el nivel (cierre del acta o fecha de la aprobación más reciente), la validez para el grado según la mención, la firma del Director, el **N° Registro** y el código QR. Las acreditaciones emitidas antes conservan su formato anterior.

La constancia de notas se emite **por nivel**, como la cobra el CILN, con el formato oficial: alumno, idioma y programa, DNI, currículo, la tabla ITEM, NIVEL, CICLO, AÑO, MES, NOTA y FORMA DE APROBACIÓN con los ciclos aprobados del nivel, el **Programa Curricular** (ciclos de cada nivel del idioma), la fecha, la firma y el código QR. Requiere al menos un ciclo aprobado del nivel. Las constancias emitidas antes de este cambio conservan su formato anterior.

Para emitir los certificados de un periodo completo, pulse **Certificados del periodo**, elija el **Periodo** y confirme: el sistema emite el certificado de culminación de nivel a cada estudiante que completó su nivel con un acta cerrada del periodo; quien ya tiene su certificado vigente no recibe otro.

Para emitir las constancias de matrícula de todo un grupo, pulse **Constancias de matrícula del grupo**, elija el **Grupo** y confirme: el sistema emite una constancia a cada estudiante con matrícula activa en el grupo; quien ya tiene una constancia vigente de esa matrícula no recibe otra. El aviso final indica cuántas constancias se emitieron y cuántas ya tenían una vigente; si alguna no pudo emitirse, lista a la persona con el motivo y permanece en pantalla hasta cerrarlo.

Resultado esperado: el documento aparece en el listado con su **Código** único. El listado se filtra por **Tipo**, **Estado** y **Por revisar tras una rectificación**, y su columna **Revisión** marca los documentos por revisar. Desde la vista de un documento vigente, **PDF** abre el documento firmado y **Ver verificación pública** muestra la página que cualquier persona puede consultar en la dirección del sistema seguida de **/verificar**, ingresando el código.

![Figura 30. Listado de constancias y certificados con las acciones Emitir documento, Certificados del periodo y Constancias de matrícula del grupo](figuras/p30b-documentos.png)

![Figura 31. Emitir documento](figuras/p30-emitir-documento.png)

Para anular un documento emitido por error:

1. Abra el documento y pulse **Anular**.
2. Escriba el **Motivo de la anulación** y confirme.

Resultado esperado: el documento queda anulado y la verificación pública lo mostrará como anulado. La anulación no se puede deshacer.

# 9. TRÁMITES

Finalidad: atender las solicitudes de trámite que los estudiantes inician desde su portal.

En **Trámites > Trámites** se listan los trámites con su **Código** de seguimiento, **Estudiante**, **Trámite**, **Etapa**, **Estado** e inicio. Pulse **Ver** para abrir uno; la vista muestra la solicitud con los campos del FUT, los **Adjuntos** (pulse **Descargar** para abrir cada archivo), la tasa y el pago aplicado, el **Historial** y los **Correos enviados** al estudiante. Las acciones disponibles dependen del estado:

1. **Avanzar a «…»**: pasa el trámite a la etapa siguiente que indica el botón. Confirme; el estudiante recibe un correo con el cambio.
2. **Finalizar: «…»**: lleva el trámite a su última etapa y ejecuta la acción final del tipo de trámite (por ejemplo, emitir la constancia de notas o trasladar la matrícula; para un traslado se elige el **Grupo de destino**).
3. **Observar**: escriba la **Observación**; el estudiante la ve en su portal y la recibe por correo, y el trámite queda **Observado**.
4. **Levantar la observación**: el trámite continúa desde su etapa actual; si la tasa sigue sin pagar, vuelve a esperar el pago.
5. **Cerrar sin atender**: escriba el **Motivo del cierre**; no se ejecuta la acción final y el trámite no se elimina.

*Tabla 14. Estados del trámite*

| Estado | Significado |
|---|---|
| Pendiente de pago | Espera que Pagos valide la tasa. |
| En proceso | El personal lo atiende por etapas. |
| Observado | Espera que el estudiante subsane la observación. |
| Finalizado | Llegó al 100 % y se ejecutó la acción final. |
| Cerrado sin atender | Cerrado por el personal con motivo. |

Resultado esperado: cada acción queda en el **Historial** con fecha, estado, etapa, comentario y usuario.

*Tabla 15. Qué pide **Finalizar** según el trámite*

| Trámite (código) | Al finalizar | Efecto |
|---|---|---|
| Examen de suficiencia (SUFICIENCIA) | **Nota del examen** (0 a 20). | Si alcanza la nota mínima aprobatoria del sistema de evaluación vigente para el idioma y nivel, se registran todos los ciclos activos del nivel como aprobados por **Suficiencia** con esa nota (sin horas lectivas) y se habilita el primer ciclo del nivel siguiente (nunca se baja un ciclo ya alcanzado). Si no la alcanza, el trámite finaliza con la nota y sin aprobaciones. Sin sistema de evaluación vigente, el sistema no permite finalizar. |
| Examen de reclasificación (RECLASIFICACION) | **Ciclo asignado** (ciclos del idioma). | Se habilita ese ciclo, aunque sea menor al actual, y los ciclos anteriores se registran como aprobados por **Clasificación** (sin nota), salvo los que ya tienen nota en un acta. |
| Acreditación (ACREDITACION) | Nada adicional. | Se emite la **Constancia de acreditación** del nivel con la mención del grado; tiene numeración anual propia y código QR. |
| Media beca (MEDIA-BECA) y Otro (OTRO) | **Respuesta al estudiante** (5 a 500 caracteres, obligatoria). | El trámite finaliza con la respuesta; no aplica descuentos automáticos. |

El estudiante ve el resultado (nota, ciclo asignado o respuesta) en **Mis trámites** y en el correo de finalización. La vista del trámite lo muestra en **Resultado**.

Notas de configuración:

- La reclasificación solo se acepta si la última matrícula del estudiante en el idioma terminó hace al menos los **Meses sin estudiar** del tipo (por defecto 2), contados desde el fin del periodo.
- La acreditación y el **Certificado de estudios (posgrado)** solo están disponibles para el programa Posgrado; el certificado de posgrado requiere la acreditación finalizada del mismo idioma y nivel. El certificado de culminación de nivel regular sigue abierto a todos los programas.
- Los tipos nuevos se crean sin **Concepto de pago**: vincule el concepto y sus tarifas en **Configuración > Trámites > Tipos de trámite** para que cada tasa solo acepte su propio pago. Mientras no se vinculen, la suficiencia y la reclasificación aceptan cualquier pago validado de tipo «Otro». Un concepto de tipo «Examen de clasificación» no puede vincularse a un tipo de trámite.

![Figura 32. Vista de un trámite observado](figuras/p31-tramite.png)

![Figura 33. Observar un trámite](figuras/p32-observar-tramite.png)

# 10. TABLERO GERENCIAL Y REPORTES

## 10.1. Tablero gerencial

El **Tablero gerencial** presenta indicadores financieros y académicos con los datos registrados hasta ese momento: recaudación, pagos por validar, matrículas y su crecimiento, matrícula por idioma, rentabilidad por grupo y deserción, entre otros.

1. Ingrese a **Tablero gerencial**.
2. Elija el **Periodo académico** y, opcionalmente, el **Idioma** y el **Nivel**.
3. Pulse **Exportar PDF** o **Excel** para descargar los indicadores con los filtros elegidos.

Resultado esperado: los indicadores se recalculan con los filtros y el archivo descargado refleja lo mostrado en pantalla.

![Figura 34. Tablero gerencial](figuras/p33-tablero-gerencial.png)

## 10.2. Reportes

Todos los reportes están en **Administración > Reportes**, cada uno en su pestaña: **Matriculados**, **Reporte de recaudación** (sección 6.6), **Asistencia por grupo**, **Notas por periodo** y **Carga docente**. Solo se muestran las pestañas que la cuenta puede ver. Salvo el reporte de recaudación, que tiene sus propios filtros, comparten los filtros **Periodo académico**, **Idioma**, **Nivel** y **Grupo**, y los botones **Exportar PDF** y **Excel**. Cada reporte requiere su propio permiso. El reporte **Matriculados** tiene dos pestañas: **Resumen por grupo** y **Detalle de matriculados**.

*Tabla 16. Reportes del panel*

| Reporte | Contenido | Permiso |
|---|---|---|
| Matriculados | Estudiantes matriculados del periodo por grupo, con su condición, estado y vínculo. | Ver matrículas |
| Reporte de recaudación | Pagos validados del rango de fechas, por concepto, idioma y nivel, con el detalle por estudiante (sección 6.6). | Ver reportes de pagos |
| Asistencia por grupo | Clases programadas, dictadas y confirmadas de cada grupo, y la asistencia de cada estudiante. | Ver reportes de docencia |
| Notas por periodo | Estado del acta, aprobados, desaprobados, NSP y promedio de cada grupo. | Gestionar actas |
| Carga docente | Horas asignadas y dictadas de cada docente en el periodo. | Ver reportes de docencia |

1. Ingrese a **Administración > Reportes** y elija la pestaña del reporte.
2. Elija los filtros.
3. Pulse **Exportar PDF** o **Excel**.

Resultado esperado: se descarga el reporte con los datos filtrados.

![Figura 35. Reporte de matriculados](figuras/p34-reporte-matriculados.png)

# 11. SEGURIDAD

## 11.1. Usuarios del personal

Finalidad: crear y administrar las cuentas del personal.

1. Ingrese a **Seguridad > Usuarios del personal** y pulse **Nuevo usuario**.
2. Complete **Nombre** y **Correo**.
3. Elija uno o más **Roles** y, si hace falta, marque **Permisos adicionales** (cada módulo tiene **Seleccionar todos**).
4. Pulse **Crear**.

Resultado esperado: la cuenta se crea y el sistema envía al correo un enlace para que la persona cree su contraseña. El administrador nunca conoce ni escribe la contraseña de otra persona. El listado de usuarios muestra el **Estado** de cada cuenta y si tiene el **Doble factor configurado**.

![Figura 36. Crear usuario del personal](figuras/p35-crear-usuario.png)

Sobre una cuenta existente están disponibles:

- **Reenviar enlace para crear contraseña**: envía al correo de la cuenta un enlace para crear una nueva contraseña.
- **Reiniciar doble factor**: la persona deberá configurar de nuevo su aplicación autenticadora; también se cierran sus sesiones.
- **Desactivar**: con un **Motivo**, impide que la persona ingrese al panel, al portal y a la API, y cierra sus sesiones. **Reactivar** devuelve el acceso.

## 11.2. Cuentas de docentes

Finalidad: dar a cada docente su usuario y contraseña, cambiar la contraseña cuando la olvida y dar de baja su cuenta. El usuario del docente es su número de documento (DNI); no necesita correo. Requiere el permiso «Gestionar docencia».

Las mismas acciones están en la ficha de cada docente (sección 5.1), y la cuenta también puede crearse al registrar al docente.

1. Ingrese a **Seguridad > Cuentas de docentes**. El listado muestra a cada docente con su **Usuario**, el estado de su **Cuenta** (**Activa**, **Dada de baja** o **Sin cuenta**) y el **Correo de la cuenta**, si tiene.
2. Verifique en persona el documento de identidad del docente.
3. Según el caso, pulse sobre el docente:

*Tabla 17. Acciones sobre la cuenta de un docente*

| Acción | Cuándo aparece | Qué hace |
|---|---|---|
| **Crear cuenta** | El docente no tiene cuenta | Crea la cuenta con su DNI como usuario y la **Contraseña** que usted escribe (al menos 8 caracteres, con letras y números, repetida en **Repite la contraseña**). Si su ficha tiene un correo personal que ninguna otra cuenta usa, la cuenta lo conserva. Muestra «Cuenta creada. Usuario: …». |
| **Asignar usuario** | El docente ya tiene una cuenta sin usuario (creada con su correo) | Agrega su DNI como usuario y le pone la contraseña que usted escribe; sigue pudiendo ingresar también con su correo. |
| **Cambiar contraseña** | El docente tiene cuenta | Le pone una nueva contraseña cuando la olvidó y cierra las sesiones que tenga abiertas. Muestra «Contraseña cambiada». |
| **Dar de baja** | La cuenta está activa | Con un **Motivo** (por ejemplo, dejó de enseñar en el CILN), impide que ingrese; no borra nada. Muestra «Cuenta dada de baja». |
| **Reactivar** | La cuenta está dada de baja | Devuelve el acceso con el mismo usuario y contraseña. Muestra «Cuenta reactivada». |

Resultado esperado: entregue al docente por escrito su usuario (DNI) y su contraseña. El docente no está obligado a cambiarla. El sistema rechaza un DNI que ya usa otra cuenta («Ya existe otra cuenta con el usuario …») y nunca cambia la contraseña de una cuenta del personal («Es una cuenta del personal: gestiónala desde Usuarios del personal.»).

## 11.3. Roles

1. Ingrese a **Seguridad > Roles** y pulse **Nuevo rol**, o **Editar** sobre uno existente.
2. Escriba el **Nombre** del rol y marque sus **Permisos**.
3. Guarde.

Resultado esperado: todas las cuentas con el rol reciben sus permisos. Solo se puede eliminar un rol que ninguna cuenta tiene.

## 11.4. Auditoría

**Seguridad > Auditoría** registra cada alta, modificación y baja de los datos críticos (pagos, matrículas, actas, documentos, trámites, cuentas y roles), con **Fecha y hora**, **Tabla**, **Operación**, **Registro**, **Usuario** e **IP**. Filtre por fechas (**Desde**, **Hasta**), **Usuario**, **Tabla**, **Operación** o **Registro (ID)**, y pulse **Ver** para consultar los **Cambios** de un registro. La auditoría es de solo lectura: nadie puede modificarla ni borrarla.

![Figura 37. Auditoría](figuras/p36-auditoria.png)

# 12. PREGUNTAS FRECUENTES

*Tabla 18. Preguntas frecuentes*

| Pregunta | Respuesta |
|---|---|
| No veo una opción del menú que necesito. | Su cuenta no tiene el permiso correspondiente. Solicítelo al administrador (Tabla 1). Los catálogos, parámetros y textos están en **Administración > Configuración**, y los reportes en **Administración > Reportes**. |
| Un estudiante creó su cuenta pero no ve la matrícula ni los trámites. | Su cuenta no está habilitada: falta validar su primer pago. Revise **Cuenta habilitada** en su ficha («Pendiente…») y valide el pago con la boleta original (sección 6.7). |
| Una persona que ya estudió en el CILN no puede crear su cuenta en la web. | Es lo esperado: el registro web rechaza los documentos que ya figuran en el CILN. Verifique su documento y use **Crear cuenta del portal** en su ficha (sección 6.7). |
| El sistema no me deja ingresar tras varios intentos. | Por seguridad se bloquean temporalmente los intentos y el sistema indica cuántos segundos esperar; si olvidó la contraseña, pida al administrador **Reenviar enlace para crear contraseña**. |
| Un docente olvidó su usuario o su contraseña. | Su usuario es su DNI. Verifique su documento y use **Cambiar contraseña** en **Seguridad > Cuentas de docentes** (sección 11.2). |
| Perdí el teléfono con la aplicación autenticadora. | Use un código de recuperación o pida al administrador **Reiniciar doble factor**. |
| No aparece el botón **Validar** en un pago. | Solo se validan pagos en estado **Pendiente** y con el permiso «Validar pagos». |
| El monto del pago no coincide con la tarifa. | Al validar, el sistema pide la **Justificación del monto distinto de la tarifa**; si el pago no corresponde, use **Rechazar**. Un pago de matrícula del plazo extemporáneo debe incluir el recargo. |
| Validé o registré un pago por error. | Un pago validado solo puede anularlo administración con **Anular** y un motivo; nunca se elimina. |
| Un estudiante no puede matricularse en el ciclo que le corresponde. | Revise **Ciclos habilitados** y, si corresponde, use **Ajustar ciclo habilitado** con el motivo. |
| No puedo cancelar un grupo. | Solo se cancela un grupo sin estudiantes matriculados; traslade antes a los estudiantes. |
| Un docente necesita corregir notas de un acta cerrada. | Certificación usa **Autorizar rectificación** con el motivo; el docente corrige y vuelve a cerrar el acta. |
| Una constancia aparece **Por revisar**. | El acta de la que se copiaron sus notas se rectificó después de emitirla; revise el documento y, si corresponde, anúlelo y emita uno nuevo. |
| ¿Puedo volver a abrir un periodo cerrado? | No. «Cerrar periodo» no se puede deshacer. |
| ¿Cómo comprueba un tercero que un documento es auténtico? | Ingresando el código del documento en la verificación pública del sistema (**/verificar**). |

# 13. SOPORTE

Durante el periodo de garantía técnica, ELECTRODATA INGENIERIA & SISTEMAS S.A.C., como contratista, atiende las incidencias y consultas sobre el funcionamiento del sistema conforme a las condiciones del contrato.

Para reportar una incidencia, el personal del CILN la comunica al contratista por el canal de reporte de incidencias que se formaliza al iniciar la garantía técnica, e indica:

1. Nombre y perfil del usuario que reporta.
2. Opción del menú y acción que estaba realizando.
3. Fecha y hora aproximada del problema.
4. Mensaje mostrado por el sistema y, de ser posible, una captura de pantalla.

No incluya contraseñas ni códigos del doble factor en los reportes.
