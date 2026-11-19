<!--
titulo: Manual de Usuario – Docente
sistema: Sistema de Gestión Académica y Administrativa del CILN
version: 1.1
fecha: noviembre de 2026
autor: ELECTRODATA INGENIERIA & SISTEMAS S.A.C.
-->

# HISTORIAL DE VERSIONES

*Tabla 1. Historial de versiones del documento*

| Versión | Fecha | Descripción | Elaborado por | Revisado por |
|---|---|---|---|---|
| 1.0 | 18/11/2026 | Versión inicial | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |
| 1.1 | 19/11/2026 | Actualización para la versión v1.1.1 del sistema | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |

# 1. INTRODUCCIÓN

## 1.1. Objetivo

El presente manual describe, paso a paso, el uso del Portal Docente del Sistema de Gestión Académica y Administrativa del Centro de Idiomas y Lenguas Nativas (CILN) de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM). En él se explica cómo crear la cuenta e ingresar al portal, consultar los grupos a cargo, registrar la asistencia y el tema dictado en cada clase, designar al delegado, registrar las notas en la pre-acta, y validar y cerrar el acta final de cada grupo.

## 1.2. A quién está dirigido

El manual está dirigido a los docentes del CILN que tienen grupos asignados por la coordinación académica. No se requieren conocimientos técnicos: basta con saber usar un navegador web.

## 1.3. Requisitos

*Tabla 2. Requisitos para usar el Portal Docente*

| Requisito | Detalle |
|---|---|
| Equipo | Computadora, tableta o teléfono con conexión a internet. |
| Navegador | Versión reciente de Google Chrome, Mozilla Firefox, Microsoft Edge o Safari. |
| Dirección del sistema | **https://ciln.unasam.edu.pe** |
| Ficha docente | La coordinación académica registra al docente en el sistema, con su documento de identidad y su correo. |
| Cuenta de usuario | El propio docente crea su cuenta con el mismo documento y el mismo correo de su ficha docente, y verifica el correo (sección 2.1). |
| Grupos asignados | La coordinación académica debe haber asignado al menos un grupo al docente. |

Las imágenes de este manual se obtuvieron del sistema con datos de demostración generados para las pruebas: los nombres, documentos de identidad, correos, teléfonos, grupos y fechas que aparecen en ellas son ficticios.

# 2. ACCESO AL PORTAL DOCENTE

## 2.1. Crear la cuenta y verificar el correo (primer acceso)

**Propósito:** obtener el acceso al Portal Docente la primera vez. El docente usa la misma cuenta y la misma página de ingreso que los estudiantes; el sistema lo reconoce como docente porque su cuenta queda vinculada a su ficha docente.

1. Ingrese a **https://ciln.unasam.edu.pe** y presione **Crear cuenta** en la parte superior de la página.
2. En **Documento de identidad**, elija el **Tipo de documento** y escriba el **Número de documento** registrado en su ficha docente (el DNI tiene 8 dígitos; los otros documentos, de 6 a 12 letras o números).
3. En **Datos personales**, complete **Apellido paterno**, **Apellido materno (opcional)**, **Nombres**, **Fecha de nacimiento** y **Celular**.
4. En **Acceso**, escriba en **Correo** el mismo correo que figura en su ficha docente. Escriba una **Contraseña** de al menos 8 caracteres, con letras y números, y repítala en **Repite la contraseña**.
5. Marque la casilla de aceptación del tratamiento de sus datos personales. El enlace **política de privacidad** abre la política del CILN en otra pestaña.
6. Presione **Crear mi cuenta**.
7. El sistema muestra la pantalla **Verifica tu correo**. Abra el mensaje **Verifica tu correo del CILN** en su bandeja de entrada (revise también el correo no deseado) y presione **Verificar mi correo**. Si el mensaje no llegó, presione **Enviar otro enlace** (una vez por minuto).

**Resultado esperado:** el sistema muestra "Tu correo quedó verificado." y abre el portal del estudiante. Para pasar al Portal Docente, presione su nombre en la parte inferior del menú lateral y elija **Portal docente**, o ingrese de nuevo por la página de ingreso (sección 2.2).

Si el sistema responde "No podemos crear una cuenta con ese documento…", ya existe una cuenta con ese documento o el correo escrito no coincide con el de la ficha: ingrese con esa cuenta o recupere su contraseña, o pida a la coordinación académica que actualice el correo de su ficha. Si al ingresar aparece "Tu cuenta no está vinculada a un perfil docente del CILN.", la cuenta no corresponde a su ficha docente; comuníquese con la coordinación académica.

![Figura 1. Formulario «Crea tu cuenta de estudiante», que el docente usa en su primer acceso](figuras/registro.png)

## 2.2. Ingresar al portal

**Propósito:** iniciar sesión en el sistema para trabajar con los grupos a cargo.

1. Ingrese a **https://ciln.unasam.edu.pe/ingresar** (opción **Ingresar** de la parte superior de la página pública del CILN).
2. En la sección **Ingresar al portal del CILN**, escriba su correo en el campo **Correo**.
3. Escriba su contraseña en el campo **Contraseña**.
4. Si usa su propio equipo, puede marcar **Mantener la sesión iniciada en este equipo**. No la marque en computadoras compartidas.
5. Presione **Ingresar**.

**Resultado esperado:** el sistema reconoce la cuenta de docente y muestra la página **Mis grupos** del Portal Docente.

Si el correo o la contraseña no coinciden, el sistema muestra "El correo o la contraseña no son correctos.". Después de cinco intentos fallidos en un minuto, muestra "Demasiados intentos. Vuelve a intentarlo en … segundos." y espera ese tiempo antes de aceptar un nuevo intento. Si la cuenta aún no verificó su correo, el sistema muestra primero la pantalla **Verifica tu correo** (sección 2.1).

![Figura 2. Página de ingreso al portal del CILN](figuras/01-ingresar.png)

## 2.3. Recuperar la contraseña

**Propósito:** crear una nueva contraseña cuando se ha olvidado la actual. El Portal Docente no tiene una opción para cambiar la contraseña; para cambiarla, use también este procedimiento.

1. En la página de ingreso, presione **¿Olvidaste tu contraseña?**.
2. En la sección **Recuperar contraseña**, escriba su correo en el campo **Correo** y presione **Enviar enlace**. El sistema muestra "Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.". Para regresar sin hacer cambios, presione **Volver a ingresar**.
3. Abra el mensaje **Restablece tu contraseña del CILN** (revise también el correo no deseado) y presione **Restablecer mi contraseña**. El enlace vence a los 60 minutos.
4. En la página **Crea una nueva contraseña**, verifique su **Correo**, escriba la **Nueva contraseña** (al menos 8 caracteres, con letras y números) y repítala en **Repite la nueva contraseña**.
5. Presione **Guardar contraseña**.

**Resultado esperado:** el sistema abre la página de ingreso con el mensaje "Tu contraseña fue restablecida. Ya puedes ingresar.". Si el enlace ya venció, el sistema muestra "El enlace no es válido o ya venció. Solicita uno nuevo." y debe repetir el procedimiento.

![Figura 3. Página para recuperar la contraseña](figuras/02-recuperar-clave.png)

## 2.4. Menú del portal

El menú lateral del Portal Docente tiene dos opciones:

- **Mis grupos:** los grupos que el docente tiene a cargo, con su avance de asistencia, notas y acta final (sección 3).
- **Actas finales:** las actas que el docente ya validó, con su estado y su PDF (sección 7).

En la parte inferior del menú aparece el nombre del docente; al presionarlo se despliega el menú de usuario. En pantallas grandes, la opción **Contraer el menú** (atajo Ctrl o ⌘ + B) reduce el menú lateral a sus iconos para ganar espacio, y **Expandir el menú** lo devuelve a su tamaño. En teléfonos, el menú se abre con el botón de menú de la parte superior.

## 2.5. Mi portal de estudiante y cerrar sesión

**Propósito:** cambiar al portal de estudiante (cuando corresponda) o salir del sistema de forma segura.

1. Presione su nombre en la parte inferior del menú lateral.
2. Si el docente también está registrado como estudiante del CILN, aparece la opción **Mi portal de estudiante**; al presionarla se abre el portal del estudiante con la misma sesión. Esta opción no se muestra a quien no tiene ficha de estudiante.
3. Para terminar la sesión, presione **Salir**.

**Resultado esperado:** con **Salir**, el sistema cierra la sesión y ya no permite ver el portal sin volver a ingresar. Se recomienda cerrar sesión siempre al terminar, en especial en equipos compartidos.

![Figura 4. Menú de usuario con las opciones Mi portal de estudiante y Salir](figuras/05-menu-usuario.png)

# 3. MIS GRUPOS

## 3.1. Lista de grupos a cargo

**Propósito:** ver de un vistazo los grupos asignados y qué falta hacer en cada uno.

1. Presione **Mis grupos** en el menú lateral (es también la página que se abre al ingresar).
2. Revise las tarjetas de sus grupos. Si la coordinación reasigna un grupo a otro docente, ese grupo deja de aparecer en la lista.

**Resultado esperado:** se muestra una tarjeta por cada grupo a cargo, con su estado: **Programado** (aún no recibe matrículas), **Abierto** (recibe matrículas) o **Cerrado** (ya no recibe matrículas). Los grupos cancelados o finalizados no se muestran. Si aún no tiene grupos, el sistema muestra el mensaje "Aún no tienes grupos asignados."

![Figura 5. Página Mis grupos](figuras/03-mis-grupos.png)

## 3.2. Contenido de la tarjeta de un grupo

Cada tarjeta muestra:

- El código del grupo (por ejemplo, **Grupo ING-1-M**), el idioma, el ciclo y el nivel.
- El periodo académico con sus fechas de inicio y fin, y el horario (días y horas). Si el grupo aún no tiene horario, se lee "Horario por confirmar".
- La próxima clase: **Hoy**, **Próxima clase** o **Empieza**, con la hora y el aula; si ya no quedan clases, "Sin clases pendientes en el periodo".
- Los tres pasos del grupo, cada uno con su avance:
  - **Asistencia:** clases registradas frente a clases programadas (por ejemplo, "8 de 13 clases · hoy").
  - **Notas:** notas registradas frente a notas esperadas (por ejemplo, "16 de 66 notas"). Si el grupo no tiene estudiantes o sistema de evaluación, se lee "Sin estudiantes o sin esquema".
  - **Acta final:** las fechas para validar el acta (por ejemplo, "Del 25 nov al 5 dic"), "Sin fechas de actas" si el periodo aún no las tiene, o el estado del acta (**Cerrada** o **En rectificación**).
- Un botón con la siguiente acción sugerida: **Tomar asistencia**, **Completar notas**, **Cerrar acta** o **Ver acta final**.

Un paso terminado se marca con un check verde; el paso que corresponde atender se resalta. Cada paso es un enlace: al presionarlo se abre la pestaña correspondiente del grupo (**Asistencia**, **Pre-acta** o **Acta final**).

![Figura 6. Tarjeta de un grupo con sus pasos Asistencia, Notas y Acta final](figuras/04-tarjeta-grupo.png)

Dentro de un grupo, las tres pestañas **Asistencia**, **Pre-acta** y **Acta final** permiten pasar de un paso a otro sin volver a la lista.

# 4. ASISTENCIA Y CONTROL DE CLASES

## 4.1. Llamados de asistencia y sus horarios

Cada clase del horario se controla con dos llamados de asistencia: el **Llamado de inicio**, al empezar la clase, y el **Llamado de cierre**, al terminarla. Cada llamado solo puede tomarse dentro de su ventana de tiempo, calculada con la hora de Lima. Después, el delegado del grupo confirma desde su portal que la clase se dictó.

*Tabla 3. Ventanas de tiempo de los llamados de asistencia*

| Llamado | Se abre | Se cierra |
|---|---|---|
| Llamado de inicio | 10 minutos antes de la hora de inicio de la clase | A la hora de fin de la clase |
| Llamado de cierre | 15 minutos antes de la hora de fin de la clase | 30 minutos después de la hora de fin de la clase |
| Confirmación del delegado | Cuando el docente guarda el llamado de cierre | 120 minutos después de la hora de fin de la clase |

Por ejemplo, para una clase de 07:00 a 08:30, el llamado de inicio está disponible de 06:50 a 08:30 y el llamado de cierre de 08:15 a 09:00; el delegado puede confirmar la clase desde que el docente guarda el llamado de cierre hasta las 10:30. Estos tiempos son parámetros del sistema; su modificación requiere un ajuste técnico a cargo de la DSTI.

Si el grupo aún no tiene clases cuyo llamado de inicio haya abierto, la pestaña muestra "Este grupo todavía no tiene clases para registrar."; si no tiene estudiantes matriculados, "El grupo no tiene estudiantes matriculados.". En cada clase solo aparecen los estudiantes matriculados en la fecha de esa clase.

## 4.2. Tomar el llamado de inicio

**Propósito:** registrar qué estudiantes están presentes al comenzar la clase.

1. En **Mis grupos**, presione **Tomar asistencia** en la tarjeta del grupo (o el paso **Asistencia**).
2. En el campo **Clase**, verifique que esté seleccionada la clase de hoy. Solo aparecen clases del horario cuyo llamado de inicio ya abrió; nunca una clase futura.
3. En la sección **Estudiantes**, todos los estudiantes aparecen marcados como **Presente**. Marque **Ausente** a quienes faltaron. El botón **Todos presentes**, disponible solo en el llamado de inicio, vuelve a marcar a todos como presentes.
4. Opcionalmente, escriba el tema en **Tema dictado en la sesión** (será obligatorio al tomar el llamado de cierre).
5. Presione **Guardar llamado de inicio**.

**Resultado esperado:** el sistema muestra un mensaje de confirmación, por ejemplo "Llamado de inicio del miércoles 18/11/2026 de 07:00 a 08:30 guardado.". El indicador de pasos sigue mostrando el llamado de inicio **En curso** y el llamado de cierre **Pendiente**: mientras dure la ventana, puede corregir el llamado de inicio guardando de nuevo ("Llamado de inicio: abierto hasta las … Guardar de nuevo corrige este llamado."). Cuando se abre la ventana del llamado de cierre, el llamado de inicio pasa a **Tomado**.

La Figura 7 muestra la pantalla de asistencia del grupo. Fue capturada por la tarde, cuando la clase de 07:00 a 08:30 ya había terminado y sus dos llamados figuraban como **Cerrado**; por eso la sección **Estudiantes** indica "Los llamados de esta clase ya cerraron." y no aparece el botón para guardar. Durante el llamado de inicio se ven el botón **Todos presentes** y el botón para guardar; durante el llamado de cierre y el registro posterior, solo el botón para guardar.

![Figura 7. Pestaña Asistencia de un grupo, con la clase, el tema dictado, los llamados y la lista de estudiantes](figuras/06-asistencia.png)

## 4.3. Tomar el llamado de cierre y registrar el tema dictado

**Propósito:** confirmar la permanencia de los estudiantes al final de la clase y dejar constancia del tema dictado.

1. Desde 15 minutos antes de terminar la clase, vuelva a abrir la pestaña **Asistencia** del grupo y verifique la clase en el campo **Clase**. La sección **Estudiantes** indica "Llamado de cierre: solo los estudiantes presentes al inicio. Abierto hasta las …".
2. Escriba el tema desarrollado en **Tema dictado en la sesión** (hasta 1000 caracteres). En este llamado el tema es obligatorio; si falta, el sistema muestra "Escribe el tema que dictaste en esta clase.".
3. Marque **Presente** o **Ausente** para cada estudiante de la lista. Un estudiante marcado **Ausente** en el llamado de cierre cuenta como falta en esa clase.
4. Presione **Guardar llamado de cierre**.

**Resultado esperado:** el sistema confirma que el llamado de cierre fue guardado y la clase queda **por confirmar**: desde ese momento el delegado puede confirmarla (sección 4.7). Una vez guardado el llamado de cierre, el llamado de inicio ya no puede modificarse. El llamado de cierre puede corregirse guardando de nuevo mientras su ventana siga abierta y el delegado aún no haya revisado la clase. Vencida la ventana (30 minutos después del fin de la clase), o cuando el delegado confirma u observa la clase, los llamados se muestran como **Cerrado** y ya no pueden modificarse.

## 4.4. Elegir otra clase y estados de las clases

**Propósito:** consultar o completar la asistencia de una clase anterior.

1. En la pestaña **Asistencia**, abra la lista del campo **Clase**.
2. Seleccione la clase que desea revisar. Cada clase muestra su fecha, su horario y su estado.

**Resultado esperado:** la página muestra la asistencia registrada de la clase elegida y su estado.

![Figura 8. Lista de clases del grupo con su estado](figuras/08-elegir-clase.png)

*Tabla 4. Estados de una clase en la lista Clase*

| Estado | Significado |
|---|---|
| con llamado de inicio | Se tomó el llamado de inicio y aún no se guarda el llamado de cierre. |
| sin llamado de cierre | Se tomó el llamado de inicio, pero venció la ventana del llamado de cierre sin registrarlo. El docente ya no puede completarla; comuníquelo a la coordinación académica. |
| por confirmar | Se tomaron ambos llamados y el delegado aún está dentro de su plazo para confirmar. |
| sin confirmar | Venció el plazo de confirmación sin respuesta del delegado. |
| confirmada | El delegado confirmó que la clase se dictó. |
| observada | El delegado registró una observación sobre la clase. |
| registro posterior | La asistencia se registró después de la clase, en un solo llamado. |
| registrada | Asistencia registrada antes de que el sistema usara los dos llamados. Se corrige con el botón **Guardar asistencia** y el indicador muestra "Registro anterior a los llamados · Registrado". |

## 4.5. Registro posterior

**Propósito:** registrar la asistencia de una clase del horario en la que no se tomó el llamado de inicio a tiempo. Si se tomó el llamado de inicio pero no el de cierre, la clase queda **sin llamado de cierre** y no admite registro posterior.

1. En el campo **Clase**, seleccione la clase pendiente.
2. El indicador de pasos muestra un único paso, **Registro posterior**, en curso. La sección **Estudiantes** indica: "Registro posterior: un solo llamado, sin confirmación del delegado. Guardar de nuevo corrige la asistencia."
3. Escriba el tema en **Tema dictado en la sesión** (obligatorio).
4. Marque **Presente** o **Ausente** para cada estudiante.
5. Presione **Guardar registro posterior**.

**Resultado esperado:** la clase queda registrada con el estado **registro posterior**. Si se detecta un error, puede corregirse guardando de nuevo. Este registro no requiere confirmación del delegado.

![Figura 9. Registro posterior de una clase](figuras/15-registro-posterior.png)

## 4.6. Designar al delegado del grupo

**Propósito:** elegir al estudiante que confirmará, desde su portal, que cada clase se dictó.

1. En la pestaña **Asistencia** del grupo, presione **Designar delegado** (parte superior derecha).
2. En la ventana **Designar delegado**, elija al estudiante en el campo **Estudiante**. Solo se listan estudiantes con matrícula activa en el grupo.
3. Presione **Enviar**. Para salir sin cambios, presione **Cancelar**.

**Resultado esperado:** el sistema muestra "Delegado del grupo: …" con el nombre del estudiante, y la página muestra "Delegado: " seguido de su nombre. Si el grupo aún no tiene delegado, la página indica "Este grupo todavía no tiene delegado.". El delegado puede cambiarse en cualquier momento repitiendo estos pasos.

![Figura 10. Ventana para designar al delegado del grupo](figuras/07-designar-delegado.png)

## 4.7. Confirmación de la clase por el delegado

Cuando el docente guarda el llamado de cierre, la clase aparece en la opción **Confirmar clases** del portal del delegado. El delegado confirma que la clase se dictó o registra una observación (entre 5 y 500 caracteres), hasta 120 minutos después de la hora de fin de la clase. Solo puede confirmar si figura presente en el llamado de cierre. Según su respuesta, la clase pasa a **confirmada** u **observada**; si no responde dentro del plazo, queda **sin confirmar**. El docente ve el resultado en el estado de cada clase (Tabla 4), y la coordinación académica usa esta información para el control de las clases dictadas.

# 5. REGISTRO DE NOTAS (PRE-ACTA)

## 5.1. Componentes de evaluación

La pre-acta reúne las notas parciales de cada estudiante. Las notas se registran sobre los componentes del sistema de evaluación vigente del grupo, que define la coordinación académica. El sistema de evaluación del CILN tiene seis componentes, agrupados en habilidades y exámenes:

*Tabla 5. Componentes de evaluación*

| Grupo | Componente |
|---|---|
| Habilidades | Comprensión auditiva |
| Habilidades | Expresión oral |
| Habilidades | Comprensión lectora |
| Habilidades | Expresión escrita |
| Exámenes | Examen parcial |
| Exámenes | Examen final |

Reglas para registrar notas:

- Las notas van de 0 a la nota máxima del sistema de evaluación (20 por defecto), con hasta dos decimales (por ejemplo, 15.50).
- Puede dejar en blanco los componentes que aún no evalúa; la nota se completa más adelante.
- Una nota en blanco cuenta como cero en los promedios de la vista previa del acta y al cerrar el acta final.

Si el grupo no tiene un sistema de evaluación vigente, la pestaña muestra "El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre."; si no tiene estudiantes, "El grupo no tiene estudiantes matriculados.".

## 5.2. Registrar y guardar notas

**Propósito:** ingresar o corregir las notas parciales de los estudiantes.

1. En **Mis grupos**, presione el paso **Notas** o el botón **Completar notas** de la tarjeta del grupo; también puede abrir la pestaña **Pre-acta** dentro del grupo.
2. Revise la sección **Camino al acta final**, que muestra las tres etapas: **Registra las notas** (con el avance, por ejemplo "21 de 66 notas"), **Revisa el acta** y **Valida y cierra** (con las fechas del periodo para validar). El botón **Revisar el acta final** abre la pestaña **Acta final**.
3. En la sección **Notas parciales**, las fichas superiores muestran cuántas notas lleva cada componente (por ejemplo, "2 de 11"). La barra se pone verde cuando el componente está completo.
4. En la tabla, escriba la nota de cada estudiante en la columna del componente correspondiente.
5. Presione **Guardar notas**.

**Resultado esperado:** el sistema muestra el mensaje "Notas parciales guardadas." y actualiza el avance. Las notas guardadas forman el acta final. Si una nota está fuera del rango permitido, el sistema muestra el error y no guarda los cambios.

**Fechas para registrar notas:** las notas parciales se registran solo dentro del calendario académico del periodo, desde la fecha de inicio del periodo hasta el último día para validar actas finales (hora de Lima). Fuera de esas fechas, o una vez validada el acta final del grupo, la pre-acta se muestra en modo de consulta, sin el botón **Guardar notas**, con uno de estos avisos:

- "Las notas parciales del grupo … se registran del … al …", fuera del rango de fechas.
- "El acta final del grupo … ya fue validada; sus notas parciales ya no se modifican.", con el acta validada.
- "El periodo … no tiene fechas para actas finales; pide a coordinación que las registre.", si el periodo aún no tiene esas fechas.

![Figura 11. Sección Notas parciales de la pre-acta](figuras/09-pre-acta.png)

![Figura 12. Sección Camino al acta final de la pre-acta, con el avance de las notas y el botón Revisar el acta final](figuras/12-camino-acta.png)

## 5.3. Obtener la pre-acta en PDF

**Propósito:** obtener una copia en PDF de las notas parciales registradas, para revisión o archivo.

1. En la pestaña **Pre-acta** del grupo, presione **Descargar pre acta (PDF)** (parte superior derecha).
2. En la pestaña nueva del navegador, guarde o imprima el documento.

**Resultado esperado:** el PDF de la pre-acta, con las notas registradas hasta ese momento, se abre en una pestaña nueva del navegador.

# 6. ACTA FINAL

## 6.1. Vista previa del acta final

**Propósito:** revisar el resultado final de cada estudiante antes de validar el acta.

1. Abra la pestaña **Acta final** del grupo, o presione el paso **Acta final** en la tarjeta del grupo.
2. Mientras el acta no esté cerrada, aparece con la marca **Vista previa**: "Se calcula con las notas de la pre-acta. Al validarla, el acta se cierra y solo cambia con autorización del administrador." Debajo se indican las fechas para validar (por ejemplo, "Fechas para validar: del 25 nov 2026 al 5 dic 2026."); si el periodo aún no las tiene, se lee "El periodo aún no tiene fechas para actas finales.".
3. Revise los datos del acta: **Idioma**, **Nivel y ciclo**, **Periodo académico** y **Docente**.
4. Revise los totales **Aprobados**, **Desaprobados** (con la nota mínima aprobatoria) y **NSP**.
5. Revise la tabla **Calificaciones**: cada estudiante con su documento de identidad, sus notas por componente, **Prom. habilidades**, **Prom. exámenes**, **Promedio** y **Estado final**.

**Resultado esperado:** el docente verifica las notas finales. Si encuentra un error, lo corrige en la pestaña **Pre-acta** y la vista previa se actualiza.

![Figura 13. Acta final en vista previa, con sus etapas y totales](figuras/10-acta-final.png)

![Figura 14. Tabla Calificaciones del acta final](figuras/13-acta-calificaciones.png)

## 6.2. Cálculo de la nota final

El sistema calcula la nota final de cada estudiante de la siguiente manera:

1. **Promedio de habilidades:** promedio simple de las cuatro habilidades, mostrado con dos decimales.
2. **Promedio de exámenes:** promedio simple del examen parcial y del examen final, mostrado con dos decimales.
3. **Nota final (Promedio):** promedio de los dos promedios anteriores, calculado con sus valores sin redondear y redondeado al entero (0.5 o más sube al entero siguiente). El redondeo se aplica solo a la nota final.

*Tabla 6. Ejemplo de cálculo de la nota final*

| Concepto | Notas | Cálculo | Resultado |
|---|---|---|---|
| Promedio de habilidades | 12, 15, 18 y 9 | (12 + 15 + 18 + 9) / 4 | 13.50 |
| Promedio de exámenes | 12 y 15 | (12 + 15) / 2 | 13.50 |
| Nota final | 13.50 y 13.50 | (13.50 + 13.50) / 2 = 13.50, redondeado | 14 (en la tabla, 14.00) |
| Estado final | 14 frente a la nota mínima 11 | 14 ≥ 11 | Aprobado |

Este ejemplo corresponde a la fila del estudiante "Uribe Galarza, Maximiliano" de la Figura 14.

*Tabla 7. Estados finales del estudiante*

| Estado final | Condición |
|---|---|
| Aprobado | La nota final es igual o mayor que la nota mínima aprobatoria (11 por defecto). |
| Desaprobado | La nota final es menor que la nota mínima aprobatoria. |
| NSP | El estudiante no tiene ninguna nota registrada (no se presentó). |

La nota mínima aprobatoria la define el CILN en el sistema de evaluación; por defecto es 11.

## 6.3. Validar y cerrar el acta final

**Propósito:** cerrar oficialmente el acta del grupo con las notas finales.

1. Complete y revise las notas en la pestaña **Pre-acta**. Recuerde que una nota en blanco cuenta como cero.
2. Abra la pestaña **Acta final** y revise la vista previa (sección 6.1).
3. Presione **Validar y cerrar acta** (parte superior derecha).
4. El sistema muestra la ventana de confirmación **¿Cerrar el acta final?**, con el mensaje "Al cerrarla ya no podrás cambiar las notas."
5. Si está seguro, presione **Sí, cerrar acta**. Para volver sin cerrar, presione **Cancelar**.

**Resultado esperado:** el sistema muestra el mensaje "Acta final validada y cerrada." y el acta pasa al estado **Cerrada**, con la fecha de validación.

Consideraciones importantes:

- El cierre es irreversible para el docente: después de cerrar el acta ya no puede cambiar las notas.
- El botón **Validar y cerrar acta** solo está habilitado dentro de las fechas para actas finales del periodo. Fuera de esas fechas se muestra deshabilitado, como en la Figura 13, capturada antes del 25 de noviembre; si se intenta fuera de plazo, el sistema indica "El acta final del grupo … se valida del … al …".
- Si el periodo aún no tiene fechas para actas finales, el sistema muestra "El periodo … no tiene fechas para actas finales; pide a coordinación que las registre.".
- El acta no se valida si el grupo no tiene estudiantes activos ("El grupo … no tiene estudiantes activos para el acta final.") o si el grupo está programado o cancelado.

## 6.4. Acta cerrada y PDF oficial

**Propósito:** consultar un acta cerrada y obtener su PDF oficial.

1. Abra la pestaña **Acta final** de un grupo con el acta cerrada, o presione **Ver acta final** en su tarjeta.
2. Verifique la marca **Cerrada** y el texto "Validada el ... Para corregirla, el administrador debe autorizar una rectificación."
3. Presione **Descargar acta (PDF)** (parte superior derecha).

**Resultado esperado:** el PDF del acta final se abre en una pestaña nueva del navegador, desde donde puede guardarlo o imprimirlo. El acta en PDF lleva su número (ACTA-NNNNNN), las firmas y un código QR; cualquier persona puede comprobar su autenticidad en la opción **Verificar documento** de la página pública del CILN.

![Figura 15. Acta final cerrada con la opción Descargar acta (PDF)](figuras/16-acta-cerrada.png)

## 6.5. Rectificación del acta

Una vez cerrada, el acta solo puede corregirse si el administrador del CILN autoriza una rectificación. En ese caso:

1. El acta aparece con la marca **En rectificación** y el **Motivo autorizado**.
2. Corrija las notas directamente en la tabla **Calificaciones** de la pestaña **Acta final** y presione **Guardar notas**. El sistema muestra "Notas del acta rectificadas. Valídala para cerrarla de nuevo."
3. Presione **Validar y cerrar acta** y confirme con **Sí, cerrar acta** para cerrarla nuevamente. Durante una rectificación, el acta puede validarse aunque hayan pasado las fechas para actas finales.

Mientras el acta está en rectificación no se emite su PDF; vuelve a estar disponible al cerrarla de nuevo. Para solicitar una rectificación, el docente debe comunicarse con la coordinación académica del CILN (sección 9).

# 7. ACTAS FINALES

**Propósito:** consultar todas las actas que el docente ya validó.

1. Presione **Actas finales** en el menú lateral.
2. Revise la tabla, que muestra para cada acta el **Grupo** (con el idioma, el ciclo y el número de aprobados, por ejemplo "Inglés Básico I · 4 de 5 aprobados"), el **Periodo**, el **Estado** (**Cerrada** o **En rectificación**) y la fecha en que fue **Validada**.
3. Presione **Abrir** para ver el acta en la pestaña **Acta final** del grupo. Si el grupo fue reasignado a otro docente, el acta ya no puede abrirse desde esta lista.
4. Presione **PDF** para abrir el acta en PDF en una pestaña nueva. Las actas en rectificación no muestran este botón.

**Resultado esperado:** se muestra la lista de actas validadas por el docente. Si todavía no ha cerrado ninguna, el sistema muestra "Aún no has cerrado actas finales." con la indicación de cerrarlas desde **Mis grupos**, en la pestaña **Acta final** de cada grupo.

![Figura 16. Página Actas finales](figuras/11-actas-finales.png)

# 8. PREGUNTAS FRECUENTES

*Tabla 8. Preguntas frecuentes*

| Pregunta | Respuesta |
|---|---|
| ¿Quién me da el usuario y la contraseña? | Nadie: el docente crea su propia cuenta con el documento y el correo de su ficha docente (sección 2.1). |
| Al ingresar aparece "Tu cuenta no está vinculada a un perfil docente del CILN.". | La cuenta no corresponde a su ficha docente (otro documento u otro correo). Comuníquese con la coordinación académica. |
| No veo un grupo que me asignaron. | Solo aparecen los grupos que la coordinación tiene asignados al docente en ese momento. Si un grupo fue reasignado, deja de mostrarse. Consulte con la coordinación académica. |
| No puedo guardar el llamado de inicio. | El llamado de inicio se abre 10 minutos antes del inicio de la clase y se cierra a la hora de fin. Si no se tomó a tiempo, use el registro posterior (sección 4.5). |
| No puedo guardar el llamado de cierre. | El llamado de cierre se abre 15 minutos antes del fin de la clase y se cierra 30 minutos después. Además, requiere escribir el tema dictado y ya no se corrige si el delegado revisó la clase. |
| Olvidé tomar la asistencia de una clase. | Si no tomó el llamado de inicio, seleccione la clase en el campo **Clase** y use el **Registro posterior** (sección 4.5). Si tomó el llamado de inicio pero no el de cierre, la clase queda **sin llamado de cierre**; comuníquelo a la coordinación académica. |
| ¿Puedo registrar una clase futura? | No. Solo aparecen las clases del horario cuyo llamado de inicio ya abrió. |
| ¿Por qué mi delegado no puede confirmar la clase? | La clase aparece para el delegado solo después de guardar el llamado de cierre, y el delegado debe figurar presente en ese llamado. El plazo vence 120 minutos después del fin de la clase. |
| ¿Qué pasa si dejo una nota en blanco? | Puede completarla después, mientras la pre-acta esté habilitada. En los promedios del acta, una nota en blanco cuenta como cero. |
| No aparece el botón **Guardar notas**. | La pre-acta está fuera de las fechas para registrar notas, el acta ya fue validada o el periodo no tiene fechas de actas; el aviso de la pantalla indica el motivo (sección 5.2). |
| ¿Cuántos decimales admite una nota? | Hasta dos decimales, de 0 a la nota máxima del sistema de evaluación (20 por defecto). |
| ¿Cómo se redondea la nota final? | Solo se redondea la nota final, al entero: 0.5 o más sube al entero siguiente (13.50 pasa a 14). |
| ¿Qué significa NSP? | Que el estudiante no tiene ninguna nota registrada en el acta. |
| El botón **Validar y cerrar acta** está deshabilitado. | El acta solo puede cerrarse dentro de las fechas para actas finales del periodo, que se muestran en la pestaña **Acta final**. |
| Cerré el acta con un error. | El docente no puede modificar un acta cerrada. Solicite a la coordinación académica una rectificación autorizada por el administrador. |
| No recuerdo mi contraseña o quiero cambiarla. | Use **¿Olvidaste tu contraseña?** en la página de ingreso (sección 2.3). |

# 9. SOPORTE

Para consultas, problemas de acceso, vinculación de la cuenta, reasignación de grupos, fechas de actas o solicitudes de rectificación, el docente debe comunicarse con la coordinación académica del CILN.

Al reportar un problema, indique el código del grupo, la pantalla en la que se encontraba, la fecha y hora aproximada, y el mensaje que mostró el sistema. Nunca comparta su contraseña.
