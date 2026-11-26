<!--
titulo: Manual de Usuario – Personal del CILN
sistema: Sistema de Gestión Académica y Administrativa del CILN
version: 1.1
fecha: noviembre de 2026
autor: ELECTRODATA INGENIERIA & SISTEMAS S.A.C.
-->

# HISTORIAL DE VERSIONES

| Versión | Fecha | Descripción | Elaborado por | Dirigido a |
|---|---|---|---|---|
| 1.0 | 18/11/2026 | Versión inicial | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.1 | 19/11/2026 | Actualización para la versión v1.1.1 del sistema | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |

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
| Dirección (tablero) | Consulta indicadores y reportes gerenciales. | Ver el tablero gerencial (opcional, según los reportes que consulte: Ver reportes de pagos, Ver reportes de docencia, Ver matrículas; ver la Tabla 9) |

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

El menú lateral muestra primero el **Tablero gerencial** y organiza las demás opciones en grupos: **Matrícula** (Personas, Solicitudes de clasificación, Matrículas, Ciclos habilitados), **Oferta académica** (Periodos, Idiomas y ciclos, Grupos, Aulas, Programas), **Pagos** (Pagos, Reporte de recaudación), **Docencia** (Docentes, Control de clases, Expediente de horas, Carga docente, Costos por hora), **Certificación** (Actas pendientes, Actas finales, Constancias y certificados), **Trámites**, **Reportes** (Matriculados, Asistencia por grupo, Notas por periodo), **Catálogos** y **Seguridad** (Usuarios del personal, Roles, Auditoría). Cada grupo puede contraerse con la flecha de su encabezado, y el menú completo se contrae con **Contraer el menú**. Las cifras resaltadas junto a una opción indican tareas pendientes, por ejemplo pagos por validar o solicitudes de clasificación por evaluar.

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

El campo **Buscar** de la parte superior del menú (atajo ⌘+K o Ctrl+K) permite ubicar registros por su nombre o código sin recorrer el menú. Además, cada listado tiene su propio campo **Buscar** y un botón de filtros que muestra entre paréntesis cuántos filtros están activos.

## 2.6. Cerrar sesión

1. Pulse su nombre en la parte inferior del menú.
2. Elija **Salir**.

Cierre siempre la sesión al terminar, en especial en equipos compartidos. Por seguridad, el sistema también cierra la sesión del panel tras 30 minutos sin actividad (valor configurable por la DSTI).

# 3. CONFIGURACIÓN INICIAL

Antes de abrir el primer periodo, el administrador revisa los catálogos del grupo **Catálogos**. La Tabla 3 resume su contenido.

*Tabla 3. Catálogos del sistema*

| Catálogo | Contenido | Permiso |
|---|---|---|
| Conceptos y tarifas | Conceptos TUPA/TUSNE (matrícula, pensión, constancias, examen de clasificación, etc.) y sus tarifas por vínculo con la UNASAM y vigencia. | Configurar conceptos y tarifas |
| Medios de pago | Bancos, agentes y canales con los que se paga (por ejemplo, Banco de la Nación, Tesorería UNASAM, Págalo.pe). | Configurar conceptos y tarifas |
| Fuentes TUPA/TUSNE | Documento normativo del que proviene cada concepto. | Configurar conceptos y tarifas |
| Tipos de trámite | Trámites que el estudiante puede iniciar, con requisitos, tasa, etapas y acción al finalizar. | Configurar tipos de trámite |
| Sistemas de evaluación | Componentes de evaluación y nota mínima aprobatoria. | Gestionar la oferta académica |
| Condiciones laborales | Condición del docente (nombrado, contratado, etc.). | Gestionar docentes y carga académica |
| Grados académicos | Grado del docente; define su costo por hora. | Gestionar docentes y carga académica |
| Firmantes | Autoridades que firman actas, constancias y certificados, con su periodo en el cargo. | Emitir y anular constancias y certificados (para marcar o editar un firmante de actas, también Gestionar actas) |
| Denominación del año | Nombre oficial del año que se imprime en los documentos. | Emitir y anular constancias y certificados para consultarla; para crearla o editarla, también Gestionar actas |
| Datos institucionales | Encabezado, pie de página, ciudad y formato del número de los documentos oficiales. | Emitir y anular constancias y certificados y Gestionar actas |
| Configuración de pagos | **Recargo por matrícula extemporánea (%)**: porcentaje entero de 0 a 100 (20 por defecto) que se suma a la tarifa de matrícula pagada en el plazo extemporáneo. 0 lo desactiva. Cada cambio queda en la auditoría. | Configurar conceptos y tarifas |

## 3.1. Conceptos y tarifas

Finalidad: registrar los conceptos que se cobran y su tarifa vigente.

1. Ingrese a **Catálogos > Conceptos y tarifas**.
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

1. Ingrese a **Catálogos > Medios de pago** o **Catálogos > Fuentes TUPA/TUSNE**.
2. Pulse **Nuevo medio de pago** o **Nueva fuente**, escriba el **Nombre**, defina el **Orden en las listas** y deje marcado **Disponible en los formularios**. El **Código** se genera al crear el registro y no cambia.
3. Guarde.

Resultado esperado: el registro aparece en los formularios de pagos y conceptos. Un registro desmarcado como disponible deja de ofrecerse, pero se conserva en los pagos ya registrados.

## 3.3. Tipos de trámite

1. Ingrese a **Catálogos > Tipos de trámite** y cree o edite un tipo.
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
| **Adjuntos** | Hasta 5 archivos con su nombre y si son obligatorios (PDF, JPG o PNG de hasta 5 MB). Por defecto ningún trámite pide adjuntos: el certificado ya no requiere la constancia autenticada en Secretaría General, porque el sistema valida las actas cerradas y los documentos llevan código QR. |

7. Guarde.

Resultado esperado: si está marcado **Disponible para nuevas solicitudes**, el tipo aparece en el portal del estudiante. Los trámites ya iniciados conservan sus etapas, sus datos y sus adjuntos; los cambios solo afectan a las nuevas solicitudes.

## 3.4. Sistemas de evaluación

Finalidad: definir cómo se calcula la nota final de los grupos.

Cada sistema de evaluación tiene dos grupos de componentes:

- **Habilidades**: las cuatro habilidades del idioma (Comprensión auditiva, Expresión oral, Comprensión lectora y Expresión escrita).
- **Exámenes**: Examen parcial y Examen final.

El sistema promedia por separado las habilidades y los exámenes; la nota final es el promedio de ambos, redondeado al entero. No se asignan pesos o porcentajes a los componentes. Aprueba quien alcanza la **Nota mínima aprobatoria**.

1. Ingrese a **Catálogos > Sistemas de evaluación** y pulse **Nuevo sistema de evaluación**.
2. Escriba el **Nombre**. Elija el **Idioma** y el **Nivel** al que se aplica, o deje **Todos los idiomas** y **Todos los niveles**.
3. Indique la **Nota máxima** (por defecto 20) y la **Nota mínima aprobatoria** (por defecto 11).
4. Indique **Vigente desde** y, solo si corresponde, **Vigente hasta**.
5. En **Componentes**, registre cada componente con su **Código**, **Nombre** y **Grupo** (Habilidades o Exámenes).
6. Guarde.

Resultado esperado: el sistema se aplica a los grupos que inician dentro de su vigencia. Una vez vigente, solo pueden cambiarse el nombre y la fecha de fin.

![Figura 6. Sistema de evaluación con cuatro habilidades y dos exámenes](figuras/p06-sistema-de-evaluacion.png)

## 3.5. Condiciones laborales, grados académicos, firmantes y denominación del año

1. **Condiciones laborales** y **Grados académicos**: pulse **Nueva condición laboral** o **Nuevo grado académico**, escriba el **Nombre** y el **Orden en las listas** y guarde. El grado académico del docente determina su costo por hora (sección 5.4).
2. **Firmantes**: pulse **Nuevo firmante** y registre el **Nombre con grado** (por ejemplo, «Dr. …»), el **Cargo** tal como se imprime bajo la firma; en **Firma**, marque los documentos que firma (**Certificados**, **Constancias**, **Actas**; al menos uno, y **Actas** solo con el permiso «Gestionar actas»); el **Orden en el documento** y las fechas **Firma desde** y **Firma hasta** (vacía mientras siga en el cargo).
3. **Denominación del año**: pulse **Nueva denominación**, indique el **Año** y la **Denominación** sin comillas; los documentos la imprimen entre « » sobre el título.

Resultado esperado: los documentos emitidos usan automáticamente los firmantes y la denominación vigentes en su fecha de emisión.

**Datos institucionales**: en **Catálogos > Datos institucionales** edite los textos que imprimen los documentos y pulse **Guardar**:

| Sección | Campos |
|---|---|
| Encabezado | **Universidad**, **Lema** y **Centro** |
| Pie de página | **Dirección**, **Teléfono y anexo**, **Web** y **Facebook** |
| Fecha y numeración | **Ciudad** que precede a la fecha; **Número de los documentos** (por defecto «N.º {numero:06}-{anio}») y **Número de la constancia de notas** (por defecto «N° {anio} - {numero:05}»). {anio} es el año y {numero:5} el correlativo con 5 dígitos. |

A diferencia de los firmantes, estos datos no se congelan: cada PDF, incluso el de un documento ya emitido, se imprime con los datos vigentes. El cambio queda en la auditoría.

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

Matrícula extemporánea: si el periodo la tiene, los grupos siguen abiertos a la matrícula hasta esa fecha. El pago de matrícula hecho en ese plazo debe incluir el recargo de **Catálogos > Configuración de pagos** (por ejemplo, S/ 10.00 + 20 % = S/ 12.00). Al validar un pago de matrícula por la tarifa sin recargo, el sistema pide la justificación del monto.
4. Cuando corresponda, use en el listado los botones de cambio de estado, que siempre piden confirmación.

*Tabla 4. Estados del periodo*

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
2. Elija el **Periodo** y el **Idioma y ciclo** (no cambian después de crear el grupo).
3. Elija el **Programa** (Jóvenes y Adultos por defecto) y complete **Código de grupo**, **Modalidad** (Presencial, Virtual o Semipresencial), **Acelerado** si corresponde, **Aula** (o **Enlace de la clase virtual**), **Cupo máximo** y **Cupo mínimo de apertura**.
4. Guarde y, en la sección **Horarios** del grupo, pulse **Agregar horario** para cada día, con su **Día**, **Desde** y **Hasta**.
5. Para conducir el grupo, use el menú **Estado** de su tarjeta en el listado (o de la cabecera de la página de edición) con las opciones **Abrir**, **Cerrar**, **Reabrir**, **Finalizar** o **Cancelar grupo**, que piden confirmación. Para abrir un grupo, este debe tener horario y docente asignado, y su periodo debe estar **Planificado** o en **Matrícula abierta**. Si al cerrarlo no alcanza el cupo mínimo, el sistema pregunta si desea iniciarlo de todas formas. No se puede finalizar mientras la matrícula del periodo esté abierta.

*Tabla 5. Estados del grupo*

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

*Tabla 6. Programas iniciales*

| Código | Programa | Régimen de ciclo |
|---|---|---|
| JA | Jóvenes y Adultos | Regular y acelerado |
| NINOS | Niños | Regular y acelerado |
| POSGRADO | Posgrado | Solo acelerado |

1. Ingrese a **Oferta académica > Programas** y pulse **Nuevo programa**, o **Editar** sobre uno existente.
2. Complete **Código** (no cambia después de crearlo), **Nombre** y **Régimen de ciclo** (Solo regular, Solo acelerado, o Regular y acelerado). Deje marcado **Disponible para nuevos grupos**.
3. Guarde.

Resultado esperado: el programa puede elegirse al crear un grupo. Un programa desactivado no recibe grupos nuevos, pero conserva los que ya tiene.

# 5. DOCENCIA

## 5.1. Docentes

Finalidad: registrar a los docentes y su perfil académico.

1. Registre antes a la persona en **Matrícula > Personas** (sección 6.1), con su **Correo personal**: es el correo con el que el docente creará su cuenta en el sistema.
2. Ingrese a **Docencia > Docentes** y pulse **Crear docente**.
3. En **Persona**, busque por documento o por apellidos y nombres.
4. En la sección **Docente**, complete **Correo institucional**, **Condición laboral** (obligatoria) y **Grado académico**; en **Datos para el expediente**, **RUC**, **Banco** y **Número de cuenta**.
5. En **Perfil académico**, agregue cada **Idioma** habilitado para dictar con su **Nivel máximo autorizado**.
6. Guarde. Para cambiar la situación de un docente, use **Cambiar estado** en su vista y elija el **Nuevo estado**: **Activo**, **Inactivo** o **De licencia**. Un docente con grupos a cargo no puede pasar a Inactivo ni a De licencia hasta que se reasignen sus grupos.

Resultado esperado: el docente aparece en el listado con su código (DOC-…) y puede recibir grupos. El número de cuenta se muestra enmascarado.

![Figura 12. Vista de un docente](figuras/p12-docente.png)

## 5.2. Disponibilidad horaria

1. En la vista del docente, abra la pestaña **Disponibilidad horaria**.
2. Pulse **Agregar franja** y elija el **Día**, **Desde** y **Hasta**. Use **Editar** o **Quitar** para corregir una franja.

Resultado esperado: la disponibilidad queda registrada como referencia para asignar grupos.

## 5.3. Asignar grupo (carga académica)

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

**Docencia > Carga docente** presenta las horas asignadas y dictadas de cada docente en el periodo. Se filtra y exporta igual que los reportes de la sección 10.2.

# 6. CAJA Y PAGOS

## 6.1. Registrar persona

Finalidad: registrar a un estudiante, docente o tercero antes de registrar su pago.

1. Ingrese a **Matrícula > Personas** y verifique con **Buscar** que la persona no esté registrada.
2. Pulse **Registrar persona**.
3. En **Documento de identidad**, elija el **Tipo de documento** y escriba el **Número de documento**.
4. En **Datos personales**, complete **Apellido paterno**, **Apellido materno**, **Nombres**, **Fecha de nacimiento**, **Celular**, **Correo personal** y **Tipo de vínculo** con la UNASAM.
5. Si la persona firmó su autorización para el tratamiento de sus datos personales (Ley N.° 29733), marque **La persona aceptó el tratamiento de sus datos personales**. El sistema guarda la fecha y hora del consentimiento; si no lo marca, la ficha muestra "Sin consentimiento registrado" y puede registrarlo más adelante con **Editar**. Una vez registrado, el consentimiento no se borra al editar la ficha.
6. Pulse **Crear**.

Resultado esperado: la persona queda registrada. Si el documento ya existe, el sistema lo informa y no crea un duplicado.

![Figura 17. Registrar persona](figuras/p17-registrar-persona.png)

## 6.2. Registrar pago

Finalidad: registrar un pago que la persona presenta en ventanilla.

1. Ingrese a **Pagos > Pagos** y pulse **Registrar pago**.
2. En **Persona**, busque por número de documento o por apellidos y nombres.
3. Elija el **Concepto TUPA/TUSNE**. Si sus tarifas varían por nivel o programa, aparecen **Nivel pagado** (obligatorio) y **Programa** (solo si el pago es la tarifa de un programa, por ejemplo Posgrado).
4. Elija el **Medio de pago**, escriba el **N.° de recibo u operación**, la **Fecha de pago** (no puede ser posterior a hoy) y el **Monto** que figura en la boleta, con dos decimales como máximo.
5. Adjunte el **Recibo escaneado (JPG, PNG o PDF)**, de hasta 5 MB.
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
5. Si el monto pagado es distinto de la tarifa, el sistema lo advierte y pide la **Justificación del monto distinto de la tarifa**; escríbala.
6. Pulse **Validar**.

Resultado esperado: el pago pasa a **Validado** y habilita el servicio correspondiente (por ejemplo, la matrícula o el avance de un trámite). Quedan registrados el usuario que validó y la fecha.

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

*Tabla 7. Estados del pago*

| Estado | Significado |
|---|---|
| Pendiente | Registrado; espera la revisión con la boleta original. |
| Validado | Confirmado por caja; habilita el servicio. |
| Rechazado | No corresponde; la persona ve el motivo. |
| Anulado | Dejado sin efecto por administración, con motivo. |

## 6.6. Reporte de recaudación

Finalidad: obtener la recaudación validada para la DGF y Tesorería.

1. Ingrese a **Pagos > Reporte de recaudación**.
2. Indique **Pagos desde** y **Pagos hasta**; opcionalmente elija el **Concepto** y el **Periodo académico**.
3. Revise el total recaudado, el ticket promedio, la recaudación por concepto TUPA/TUSNE, por idioma y nivel, y el detalle por estudiante.
4. Pulse **Descargar PDF** o **Descargar CSV**.

Resultado esperado: se descarga el reporte con los pagos validados del rango elegido.

![Figura 22. Reporte de recaudación](figuras/p22-reporte-de-recaudacion.png)

# 7. MATRÍCULA Y CLASIFICACIÓN

## 7.1. Solicitudes de clasificación

Finalidad: registrar el resultado del examen de clasificación que solicita un estudiante.

1. Ingrese a **Matrícula > Solicitudes de clasificación**. El listado muestra el estado de cada solicitud (**Pendiente de evaluación**, **Evaluado**, **Expirado** o **Pago anulado**) y la fecha en que vence (**Vence el**). Las solicitudes **Pendiente de evaluación** muestran el botón **Registrar resultado**.
2. Pulse **Registrar resultado**.
3. Indique la **Fecha del examen**, el **Puntaje**, el **Ciclo asignado** y, si corresponde, **Observaciones**.
4. Pulse **Enviar**.

Resultado esperado: la solicitud pasa a **Evaluado** y el ciclo asignado queda habilitado para la matrícula del estudiante en ese idioma. Los ciclos anteriores al asignado quedan registrados como aprobados con forma de aprobación **Clasificación**, sin nota ni horas lectivas; figuran en el récord y en la constancia de notas y cuentan para el certificado del nivel, cuyo promedio se calcula solo con los ciclos que tienen nota.

![Figura 23. Registrar resultado de clasificación](figuras/p23-registrar-resultado.png)

Para corregir un resultado ya registrado, use **Corregir resultado** en la solicitud, indique los nuevos datos y el **Motivo de la corrección**. Si cambia el ciclo asignado, la corrección solo procede cuando el ciclo habilitado aún proviene de esta clasificación y el estudiante no se matriculó en él; en caso contrario, el sistema rechaza la corrección e indica usar «Ajustar ciclo habilitado» (sección 7.4). Si la corrección sube el ciclo asignado, se registran también los ciclos que faltaban; si lo baja, los ciclos que dejan de saltarse quedan anulados con el motivo «Corrección del examen de clasificación» y el usuario que corrigió; no se eliminan, pero ya no figuran en el récord, en la constancia ni en el certificado. Los ciclos que el estudiante ya aprobó con nota en un acta no se registran por clasificación y conservan su nota.

## 7.2. Matrículas: ficha

1. Ingrese a **Matrícula > Matrículas** y pulse **Ver** en la matrícula. El listado se filtra por **Estado** y **Grupo**. Las matrículas las registra el propio estudiante desde su portal; el personal no las crea desde el panel.
2. Revise el **Estudiante**, el **Grupo**, el **Horario**, la **Condición**, el **Estado**, el pago asociado y la sección **Traslados y retiros**.
3. Pulse **Ficha de matrícula** para abrir la ficha en PDF.

Resultado esperado: la ficha se abre en una pestaña nueva, desde donde puede imprimirse o descargarse; incluye un código para verificar su autenticidad.

![Figura 24. Vista de una matrícula](figuras/p24-matricula.png)

## 7.3. Trasladar y retirar

**Trasladar** cambia al estudiante a otro grupo del mismo periodo y ciclo:

1. En la matrícula (o en su fila del listado), pulse **Trasladar**.
2. Elija el **Grupo de destino** (grupos del mismo periodo y ciclo, abiertos o cerrados, con vacantes) y escriba el **Motivo del traslado**.
3. Pulse **Enviar**.

**Retirar** registra el retiro oficial:

1. En la matrícula (o en su fila del listado), pulse **Retirar**.
2. Escriba el **Motivo del retiro** y confirme.

Resultado esperado: el movimiento queda en **Traslados y retiros** con el usuario y la fecha. El retiro libera la vacante del grupo y no se puede deshacer.

![Figura 25. Trasladar una matrícula](figuras/p25-trasladar.png)

## 7.4. Ciclos habilitados

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

*Tabla 8. Estados del trámite*

| Estado | Significado |
|---|---|
| Pendiente de pago | Espera que Pagos valide la tasa. |
| En proceso | El personal lo atiende por etapas. |
| Observado | Espera que el estudiante subsane la observación. |
| Finalizado | Llegó al 100 % y se ejecutó la acción final. |
| Cerrado sin atender | Cerrado por el personal con motivo. |

Resultado esperado: cada acción queda en el **Historial** con fecha, estado, etapa, comentario y usuario.

*Tabla 8a. Qué pide **Finalizar** según el trámite*

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
- Los tipos nuevos se crean sin **Concepto de pago**: vincule el concepto y sus tarifas en **Catálogos > Tipos de trámite** para que cada tasa solo acepte su propio pago.

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

Los reportes del grupo **Reportes** (y **Carga docente** en **Docencia**) comparten los filtros **Periodo académico**, **Idioma**, **Nivel** y **Grupo**, y los botones **Exportar PDF** y **Excel**. Cada reporte requiere su propio permiso. El reporte **Matriculados** tiene dos pestañas: **Resumen por grupo** y **Detalle de matriculados**.

*Tabla 9. Reportes del panel*

| Reporte | Contenido | Permiso |
|---|---|---|
| Matriculados | Estudiantes matriculados del periodo por grupo, con su condición, estado y vínculo. | Ver matrículas |
| Asistencia por grupo | Clases programadas, dictadas y confirmadas de cada grupo, y la asistencia de cada estudiante. | Ver reportes de docencia |
| Notas por periodo | Estado del acta, aprobados, desaprobados, NSP y promedio de cada grupo. | Gestionar actas |
| Carga docente | Horas asignadas y dictadas de cada docente en el periodo. | Ver reportes de docencia |

1. Abra el reporte desde el menú.
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

## 11.2. Roles

1. Ingrese a **Seguridad > Roles** y pulse **Nuevo rol**, o **Editar** sobre uno existente.
2. Escriba el **Nombre** del rol y marque sus **Permisos**.
3. Guarde.

Resultado esperado: todas las cuentas con el rol reciben sus permisos. Solo se puede eliminar un rol que ninguna cuenta tiene.

## 11.3. Auditoría

**Seguridad > Auditoría** registra cada alta, modificación y baja de los datos críticos (pagos, matrículas, actas, documentos, trámites, cuentas y roles), con **Fecha y hora**, **Tabla**, **Operación**, **Registro**, **Usuario** e **IP**. Filtre por fechas (**Desde**, **Hasta**), **Usuario**, **Tabla**, **Operación** o **Registro (ID)**, y pulse **Ver** para consultar los **Cambios** de un registro. La auditoría es de solo lectura: nadie puede modificarla ni borrarla.

![Figura 37. Auditoría](figuras/p36-auditoria.png)

# 12. PREGUNTAS FRECUENTES

*Tabla 10. Preguntas frecuentes*

| Pregunta | Respuesta |
|---|---|
| No veo una opción del menú que necesito. | Su cuenta no tiene el permiso correspondiente. Solicítelo al administrador (Tabla 1). |
| El sistema no me deja ingresar tras varios intentos. | Por seguridad se bloquean temporalmente los intentos y el sistema indica cuántos segundos esperar; si olvidó la contraseña, pida al administrador **Reenviar enlace para crear contraseña**. |
| Perdí el teléfono con la aplicación autenticadora. | Use un código de recuperación o pida al administrador **Reiniciar doble factor**. |
| No aparece el botón **Validar** en un pago. | Solo se validan pagos en estado **Pendiente** y con el permiso «Validar pagos». |
| El monto del pago no coincide con la tarifa. | Al validar, el sistema pide la **Justificación del monto distinto de la tarifa**; si el pago no corresponde, use **Rechazar**. |
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
