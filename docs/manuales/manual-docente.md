<!--
titulo: Manual de Usuario – Docente
sistema: Sistema de Gestión Académica y Administrativa del CILN
version: 1.0
fecha: noviembre de 2026
autor: ELECTRODATA INGENIERIA & SISTEMAS S.A.C.
-->

# HISTORIAL DE VERSIONES

*Tabla 1. Historial de versiones del documento*

| Versión | Fecha | Descripción | Elaborado por | Revisado por |
|---|---|---|---|---|
| 1.0 | 18/11/2026 | Versión inicial | ELECTRODATA INGENIERIA & SISTEMAS S.A.C. | CILN – UNASAM |

# 1. INTRODUCCIÓN

## 1.1. Objetivo

El presente manual describe, paso a paso, el uso del Portal Docente del Sistema de Gestión Académica y Administrativa del Centro de Idiomas y Lenguas Nativas (CILN) de la Universidad Nacional Santiago Antúnez de Mayolo (UNASAM). En él se explica cómo ingresar al portal, consultar los grupos a cargo, registrar la asistencia y el tema dictado en cada clase, registrar las notas en la pre-acta, y validar y cerrar el acta final de cada grupo.

## 1.2. A quién está dirigido

El manual está dirigido a los docentes del CILN que tienen grupos asignados por la coordinación académica. No se requieren conocimientos técnicos: basta con saber usar un navegador web.

## 1.3. Requisitos

*Tabla 2. Requisitos para usar el Portal Docente*

| Requisito | Detalle |
|---|---|
| Equipo | Computadora, tableta o teléfono con conexión a internet. |
| Navegador | Versión reciente de Google Chrome, Mozilla Firefox, Microsoft Edge o Safari. |
| Cuenta de usuario | Correo y contraseña registrados por el CILN, con el rol de docente. |
| Grupos asignados | La coordinación académica debe haber asignado al menos un grupo al docente. |

Las imágenes de este manual se obtuvieron del sistema con datos de demostración; los nombres de estudiantes, grupos y fechas que aparecen en ellas son ficticios.

# 2. ACCESO AL PORTAL DOCENTE

## 2.1. Ingresar al portal

**Propósito:** iniciar sesión en el sistema para trabajar con los grupos a cargo.

1. Abra el navegador e ingrese a la dirección del sistema, en la página **/ingresar** (por ejemplo, la opción **Ingresar** de la parte superior de la página pública del CILN).
2. En la sección **Ingresar al portal del CILN**, escriba su correo institucional en el campo **Correo**.
3. Escriba su contraseña en el campo **Contraseña**.
4. Si usa su propio equipo, puede marcar **Mantener la sesión iniciada en este equipo**. No la marque en computadoras compartidas.
5. Presione **Ingresar**.

**Resultado esperado:** el sistema reconoce la cuenta de docente y muestra la página **Mis grupos** del Portal Docente.

![Figura 1. Página de ingreso al portal del CILN](figuras/01-ingresar.png)

## 2.2. Recuperar la contraseña

**Propósito:** crear una nueva contraseña cuando se ha olvidado la actual.

1. En la página de ingreso, presione **¿Olvidaste tu contraseña?**.
2. En la sección **Recuperar contraseña**, escriba su correo en el campo **Correo**.
3. Presione **Enviar enlace**.
4. Revise su bandeja de entrada (y la carpeta de correo no deseado) y abra el enlace recibido.
5. Escriba la nueva contraseña y presione **Guardar contraseña**.

**Resultado esperado:** el sistema muestra el mensaje "Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña." Con la nueva contraseña ya puede ingresar al portal. Para regresar sin hacer cambios, presione **Volver a ingresar**.

![Figura 2. Página para recuperar la contraseña](figuras/02-recuperar-clave.png)

## 2.3. Menú del portal

El menú lateral del Portal Docente tiene dos opciones:

- **Mis grupos:** los grupos que el docente tiene a cargo, con su avance de asistencia, notas y acta final (sección 3).
- **Actas finales:** las actas que el docente ya validó, con su estado y su PDF (sección 7).

En la parte inferior del menú aparece el nombre del docente; al presionarlo se despliega el menú de usuario. La opción **Contraer el menú** reduce el menú lateral para ganar espacio en pantallas pequeñas.

## 2.4. Mi portal de estudiante y cerrar sesión

**Propósito:** cambiar al portal de estudiante (cuando corresponda) o salir del sistema de forma segura.

1. Presione su nombre en la parte inferior del menú lateral.
2. Si el docente también está registrado como estudiante del CILN, aparece la opción **Mi portal de estudiante**; al presionarla se abre el portal del estudiante con la misma sesión. Esta opción no se muestra a quien no tiene ficha de estudiante.
3. Para terminar la sesión, presione **Salir**.

**Resultado esperado:** con **Salir**, el sistema cierra la sesión y ya no permite ver el portal sin volver a ingresar. Se recomienda cerrar sesión siempre al terminar, en especial en equipos compartidos.

![Figura 3. Menú de usuario con las opciones Mi portal de estudiante y Salir](figuras/05-menu-usuario.png)

# 3. MIS GRUPOS

## 3.1. Lista de grupos a cargo

**Propósito:** ver de un vistazo los grupos asignados y qué falta hacer en cada uno.

1. Presione **Mis grupos** en el menú lateral (es también la página que se abre al ingresar).
2. Revise las tarjetas de sus grupos. Si la coordinación reasigna un grupo a otro docente, ese grupo deja de aparecer en la lista.

**Resultado esperado:** se muestra una tarjeta por cada grupo, con su estado (**Abierto** o **Cerrado**). Si aún no tiene grupos, el sistema muestra el mensaje "Aún no tienes grupos asignados."

![Figura 4. Página Mis grupos](figuras/03-mis-grupos.png)

## 3.2. Contenido de la tarjeta de un grupo

Cada tarjeta muestra:

- El código del grupo (por ejemplo, **Grupo ING-1-M**), el idioma, el ciclo y el nivel.
- El periodo académico con sus fechas de inicio y fin, y el horario (días y horas).
- La próxima clase: **Hoy**, **Próxima clase** o **Empieza**, con la hora y el aula; si ya no quedan clases, "Sin clases pendientes en el periodo".
- Los tres pasos del grupo, cada uno con su avance:
  - **Asistencia:** clases registradas frente a clases programadas (por ejemplo, "8 de 13 clases · hoy").
  - **Notas:** notas registradas frente a notas esperadas (por ejemplo, "16 de 66 notas").
  - **Acta final:** las fechas para validar el acta (por ejemplo, "Del 25 nov al 5 dic") o su estado (**Cerrada**).
- Un botón con la siguiente acción sugerida: **Tomar asistencia**, **Completar notas**, **Cerrar acta** o **Ver acta final**.

Un paso terminado se marca con un check verde; el paso que corresponde atender se resalta. Cada paso es un enlace: al presionarlo se abre la pestaña correspondiente del grupo (**Asistencia**, **Pre-acta** o **Acta final**).

![Figura 5. Tarjeta de un grupo con sus pasos Asistencia, Notas y Acta final](figuras/04-tarjeta-grupo.png)

Dentro de un grupo, las tres pestañas **Asistencia**, **Pre-acta** y **Acta final** permiten pasar de un paso a otro sin volver a la lista.

# 4. ASISTENCIA Y CONTROL DE CLASES

## 4.1. Llamados de asistencia y sus horarios

Cada clase del horario se controla con dos llamados de asistencia: el **Llamado de inicio**, al empezar la clase, y el **Llamado de cierre**, al terminarla. Cada llamado solo puede tomarse dentro de su ventana de tiempo, calculada con la hora de Lima.

*Tabla 3. Ventanas de tiempo de los llamados de asistencia*

| Llamado | Se abre | Se cierra |
|---|---|---|
| Llamado de inicio | 10 minutos antes de la hora de inicio de la clase | A la hora de fin de la clase |
| Llamado de cierre | 15 minutos antes de la hora de fin de la clase | 30 minutos después de la hora de fin de la clase |
| Confirmación del delegado | Al terminar la clase | 120 minutos después de la hora de fin de la clase |

Por ejemplo, para una clase de 07:00 a 08:30, el llamado de inicio está disponible de 06:50 a 08:30 y el llamado de cierre de 08:15 a 09:00; el delegado puede confirmar la clase hasta las 10:30. Estos tiempos los configura el CILN.

## 4.2. Tomar el llamado de inicio

**Propósito:** registrar qué estudiantes están presentes al comenzar la clase.

1. En **Mis grupos**, presione **Tomar asistencia** en la tarjeta del grupo (o el paso **Asistencia**).
2. En el campo **Clase**, verifique que esté seleccionada la clase de hoy. Solo aparecen clases del horario cuyo llamado de inicio ya abrió; nunca una clase futura.
3. Para cada estudiante de la sección **Estudiantes**, marque **Presente** o **Ausente**. Si asistieron todos, presione **Todos presentes** para marcarlos de una vez y corrija solo las excepciones.
4. Opcionalmente, escriba el tema en **Tema dictado en la sesión** (será obligatorio al tomar el llamado de cierre).
5. Presione **Guardar llamado de inicio**.

**Resultado esperado:** el sistema muestra un mensaje de confirmación, por ejemplo "Llamado de inicio del miércoles 18/11/2026 de 07:00 a 08:30 guardado.", y el indicador de pasos marca el llamado de inicio como **Tomado**.

La Figura 6 muestra la pantalla de asistencia del grupo. Fue capturada por la tarde, cuando la clase de 07:00 a 08:30 ya había terminado y sus dos llamados figuraban como **Cerrado**; por eso la sección **Estudiantes** indica "Los llamados de esta clase ya cerraron." y no aparece el botón para guardar. Durante la ventana de cada llamado, en su lugar se ven el botón **Todos presentes** y el botón para guardar el llamado en curso.

![Figura 6. Pestaña Asistencia de un grupo, con la clase, el tema dictado, los llamados y la lista de estudiantes](figuras/06-asistencia.png)

## 4.3. Tomar el llamado de cierre y registrar el tema dictado

**Propósito:** confirmar la permanencia de los estudiantes al final de la clase y dejar constancia del tema dictado.

1. Desde 15 minutos antes de terminar la clase, vuelva a abrir la pestaña **Asistencia** del grupo y verifique la clase en el campo **Clase**.
2. Escriba el tema desarrollado en **Tema dictado en la sesión**. En este llamado el tema es obligatorio.
3. Marque **Presente** o **Ausente** para cada estudiante.
4. Presione **Guardar llamado de cierre**.

**Resultado esperado:** el sistema confirma que el llamado de cierre fue guardado y ambos llamados quedan registrados. Una vez vencida la ventana del llamado de cierre (30 minutos después del fin de la clase), los llamados se muestran como **Cerrado** y el tema ya no puede modificarse.

## 4.4. Elegir otra clase y estados de las clases

**Propósito:** consultar o completar la asistencia de una clase anterior.

1. En la pestaña **Asistencia**, abra la lista del campo **Clase**.
2. Seleccione la clase que desea revisar. Cada clase muestra su fecha, su horario y su estado.

**Resultado esperado:** la página muestra la asistencia registrada de la clase elegida y su estado.

![Figura 7. Lista de clases del grupo con su estado](figuras/08-elegir-clase.png)

*Tabla 4. Estados de una clase en la lista Clase*

| Estado | Significado |
|---|---|
| con llamado de inicio | Se tomó el llamado de inicio y aún está abierta la ventana del llamado de cierre. |
| sin llamado de cierre | Se tomó el llamado de inicio, pero venció la ventana del llamado de cierre sin registrarlo. |
| por confirmar | Se tomaron ambos llamados y el delegado aún está dentro de su plazo para confirmar. |
| sin confirmar | Venció el plazo de confirmación sin respuesta del delegado. |
| confirmada | El delegado confirmó que la clase se dictó. |
| observada | El delegado registró una observación sobre la clase. |
| registro posterior | La asistencia se registró después de la clase, en un solo llamado. |
| registrada | Asistencia registrada antes de que el sistema usara los dos llamados. |

## 4.5. Registro posterior

**Propósito:** registrar la asistencia de una clase del horario cuyos llamados no se tomaron a tiempo.

1. En el campo **Clase**, seleccione la clase pendiente.
2. El indicador de pasos muestra un único paso, **Registro posterior**, en curso. La sección **Estudiantes** indica: "Registro posterior: un solo llamado, sin confirmación del delegado. Guardar de nuevo corrige la asistencia."
3. Escriba el tema en **Tema dictado en la sesión** (obligatorio).
4. Marque **Presente** o **Ausente** para cada estudiante.
5. Presione **Guardar registro posterior**.

**Resultado esperado:** la clase queda registrada con el estado **registro posterior**. Si se detecta un error, puede corregirse guardando de nuevo. Este registro no requiere confirmación del delegado.

![Figura 8. Registro posterior de una clase](figuras/15-registro-posterior.png)

## 4.6. Designar al delegado del grupo

**Propósito:** elegir al estudiante que confirmará, desde su celular, que cada clase se dictó.

1. En la pestaña **Asistencia** del grupo, presione **Designar delegado** (parte superior derecha).
2. En la ventana **Designar delegado**, elija al estudiante en el campo **Estudiante**.
3. Presione **Enviar**. Para salir sin cambios, presione **Cancelar**.

**Resultado esperado:** la página muestra "Delegado: " seguido del nombre del estudiante designado. Si el grupo aún no tiene delegado, la página indica "Este grupo todavía no tiene delegado."

![Figura 9. Ventana para designar al delegado del grupo](figuras/07-designar-delegado.png)

## 4.7. Confirmación de la clase por el delegado

Después de que el docente toma los dos llamados, el delegado del grupo ingresa a su portal de estudiante y confirma que la clase se dictó, o registra una observación. Tiene un plazo de hasta 120 minutos después de la hora de fin de la clase. Según su respuesta, la clase pasa a **confirmada** u **observada**; si no responde dentro del plazo, queda **sin confirmar**. El docente puede ver el resultado en el estado de cada clase (Tabla 4). La coordinación académica usa esta información para el control de las clases dictadas.

# 5. REGISTRO DE NOTAS (PRE-ACTA)

## 5.1. Componentes de evaluación

La pre-acta reúne las notas parciales de cada estudiante. Las notas se registran sobre seis componentes, agrupados en habilidades y exámenes.

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

- Las notas van de 0 a 20, con hasta dos decimales (por ejemplo, 15.50).
- Puede dejar en blanco los componentes que aún no evalúa; la nota se completa más adelante.
- Al cerrar el acta final, una nota en blanco cuenta como cero.

## 5.2. Registrar y guardar notas

**Propósito:** ingresar o corregir las notas parciales de los estudiantes.

1. En **Mis grupos**, presione el paso **Notas** o el botón **Completar notas** de la tarjeta del grupo; también puede abrir la pestaña **Pre-acta** dentro del grupo.
2. Revise la sección **Camino al acta final**, que muestra las tres etapas: **Registra las notas** (con el avance, por ejemplo "21 de 66 notas"), **Revisa el acta** y **Valida y cierra** (con las fechas del periodo para validar).
3. En la sección **Notas parciales**, las fichas superiores muestran cuántas notas lleva cada componente (por ejemplo, "2 de 11"). La barra se pone verde cuando el componente está completo.
4. En la tabla, escriba la nota de cada estudiante en la columna del componente correspondiente.
5. Presione **Guardar notas**.

**Resultado esperado:** el sistema muestra el mensaje "Notas parciales guardadas." y actualiza el avance. Las notas guardadas forman el acta final. Si una nota está fuera del rango permitido, el sistema muestra el error y no guarda los cambios.

**Fechas para registrar notas:** las notas parciales se registran solo dentro del calendario académico del periodo, desde el primer día de clases hasta el último día para validar actas. Fuera de esas fechas, o una vez validada el acta final del grupo, la pre-acta se muestra en modo de consulta con un aviso del rango de fechas y sin el botón **Guardar notas**. Si el periodo no tiene fechas de actas registradas, solicite a coordinación que las registre.

![Figura 10. Sección Notas parciales de la pre-acta](figuras/09-pre-acta.png)

![Figura 11. Confirmación de notas parciales guardadas y avance hacia el acta final](figuras/12-notas-guardadas.png)

## 5.3. Descargar la pre-acta en PDF

**Propósito:** obtener una copia en PDF de las notas parciales registradas, para revisión o archivo.

1. En la pestaña **Pre-acta** del grupo, presione **Descargar pre acta (PDF)** (parte superior derecha).
2. Abra o guarde el archivo descargado.

**Resultado esperado:** el navegador descarga el PDF de la pre-acta con las notas registradas hasta ese momento.

# 6. ACTA FINAL

## 6.1. Vista previa del acta final

**Propósito:** revisar el resultado final de cada estudiante antes de validar el acta.

1. Abra la pestaña **Acta final** del grupo, o presione el paso **Acta final** en la tarjeta del grupo.
2. Mientras el acta no esté cerrada, aparece con la marca **Vista previa**: "Se calcula con las notas de la pre-acta. Al validarla, el acta se cierra y solo cambia con autorización del administrador." Debajo se indican las fechas para validar (por ejemplo, "Fechas para validar: del 25 nov 2026 al 5 dic 2026.").
3. Revise los totales **Aprobados**, **Desaprobados** (con la nota mínima aprobatoria) y **NSP**.
4. Revise la tabla **Calificaciones**: notas por componente, **Prom. habilidades**, **Prom. exámenes**, **Promedio** y **Estado final** de cada estudiante.

**Resultado esperado:** el docente verifica las notas finales. Si encuentra un error, lo corrige en la pestaña **Pre-acta** y la vista previa se actualiza.

![Figura 12. Acta final en vista previa, con sus etapas y totales](figuras/10-acta-final.png)

![Figura 13. Tabla Calificaciones del acta final](figuras/13-acta-calificaciones.png)

## 6.2. Cálculo de la nota final

El sistema calcula la nota final de cada estudiante de la siguiente manera:

1. **Promedio de habilidades:** promedio simple de las cuatro habilidades, mostrado con dos decimales.
2. **Promedio de exámenes:** promedio simple del examen parcial y del examen final, mostrado con dos decimales.
3. **Nota final (Promedio):** promedio de los dos promedios anteriores, redondeado al entero (0.5 o más sube al entero siguiente). El redondeo se aplica solo a la nota final.

*Tabla 6. Ejemplo de cálculo de la nota final*

| Concepto | Notas | Cálculo | Resultado |
|---|---|---|---|
| Promedio de habilidades | 12, 15, 18 y 9 | (12 + 15 + 18 + 9) / 4 | 13.50 |
| Promedio de exámenes | 12 y 15 | (12 + 15) / 2 | 13.50 |
| Nota final | 13.50 y 13.50 | (13.50 + 13.50) / 2 = 13.50, redondeado | 14 |
| Estado final | 14 frente a la nota mínima 11 | 14 ≥ 11 | Aprobado |

Este ejemplo corresponde a la fila del estudiante "Uribe Galarza, Maximiliano" de la Figura 13.

*Tabla 7. Estados finales del estudiante*

| Estado final | Condición |
|---|---|
| Aprobado | La nota final es igual o mayor que la nota mínima aprobatoria (11 por defecto). |
| Desaprobado | La nota final es menor que la nota mínima aprobatoria. |
| NSP | El estudiante no tiene ninguna nota registrada (no se presentó). |

La nota mínima aprobatoria la define el CILN en el sistema de evaluación; por defecto es 11.

## 6.3. Validar y cerrar el acta final

**Propósito:** cerrar oficialmente el acta del grupo con las notas finales.

1. Complete y revise las notas en la pestaña **Pre-acta**. Recuerde que, al cerrar, una nota en blanco cuenta como cero.
2. Abra la pestaña **Acta final** y revise la vista previa (sección 6.1).
3. Presione **Validar y cerrar acta** (parte superior derecha).
4. El sistema muestra la ventana de confirmación **¿Cerrar el acta final?**, con el mensaje "Al cerrarla ya no podrás cambiar las notas."
5. Si está seguro, presione **Sí, cerrar acta**. Para volver sin cerrar, presione **Cancelar**.

**Resultado esperado:** el sistema muestra el mensaje "Acta final validada y cerrada." y el acta pasa al estado **Cerrada**, con la fecha de validación.

Consideraciones importantes:

- El cierre es irreversible para el docente: después de cerrar el acta ya no puede cambiar las notas.
- El botón **Validar y cerrar acta** solo está habilitado dentro de las fechas para actas finales del periodo. Fuera de esas fechas se muestra deshabilitado, como en la Figura 12, capturada antes del 25 de noviembre.
- Si el periodo aún no tiene fechas para actas finales, el sistema lo indica y se debe pedir a la coordinación que las registre.

## 6.4. Acta cerrada y descarga en PDF

**Propósito:** consultar un acta cerrada y obtener su PDF oficial.

1. Abra la pestaña **Acta final** de un grupo con el acta cerrada, o presione **Ver acta final** en su tarjeta.
2. Verifique la marca **Cerrada** y el texto "Validada el ... Para corregirla, el administrador debe autorizar una rectificación."
3. Presione **Descargar acta (PDF)** (parte superior derecha).

**Resultado esperado:** el navegador descarga el PDF del acta final con las calificaciones de los estudiantes.

![Figura 14. Acta final cerrada con la opción Descargar acta (PDF)](figuras/16-acta-cerrada.png)

## 6.5. Rectificación del acta

Una vez cerrada, el acta solo puede corregirse si el administrador del CILN autoriza una rectificación. En ese caso:

1. El acta aparece con la marca **En rectificación** y el **Motivo autorizado**.
2. Corrija las notas directamente en la tabla **Calificaciones** de la pestaña **Acta final** y presione **Guardar notas**. El sistema muestra "Notas del acta rectificadas. Valídala para cerrarla de nuevo."
3. Presione **Validar y cerrar acta** y confirme con **Sí, cerrar acta** para cerrarla nuevamente.

Para solicitar una rectificación, el docente debe comunicarse con la coordinación académica del CILN (sección 9).

# 7. ACTAS FINALES

**Propósito:** consultar todas las actas que el docente ya validó.

1. Presione **Actas finales** en el menú lateral.
2. Revise la tabla, que muestra para cada acta el **Grupo** (con el idioma, el nivel y el número de aprobados), el **Periodo**, el **Estado** y la fecha en que fue **Validada**.
3. Presione **Abrir** para ver el acta en la pestaña **Acta final** del grupo.
4. Presione **PDF** para descargar el acta en PDF.

**Resultado esperado:** se muestra la lista de actas cerradas por el docente. Si todavía no ha cerrado ninguna, el sistema muestra "Aún no has cerrado actas finales." con la indicación de cerrarlas desde **Mis grupos**, en la pestaña **Acta final** de cada grupo.

![Figura 15. Página Actas finales](figuras/11-actas-finales.png)

# 8. PREGUNTAS FRECUENTES

*Tabla 8. Preguntas frecuentes*

| Pregunta | Respuesta |
|---|---|
| No veo un grupo que me asignaron. | Solo aparecen los grupos que la coordinación tiene asignados al docente en ese momento. Si un grupo fue reasignado, deja de mostrarse. Consulte con la coordinación académica. |
| No puedo guardar el llamado de inicio. | El llamado de inicio se abre 10 minutos antes del inicio de la clase y se cierra a la hora de fin. Fuera de ese horario, use el registro posterior. |
| No puedo guardar el llamado de cierre. | El llamado de cierre se abre 15 minutos antes del fin de la clase y se cierra 30 minutos después. Además, requiere escribir el tema dictado. |
| Olvidé tomar la asistencia de una clase. | Seleccione la clase en el campo **Clase** y use el **Registro posterior** (sección 4.5). |
| ¿Puedo registrar una clase futura? | No. Solo aparecen las clases del horario cuyo llamado de inicio ya abrió. |
| ¿Qué pasa si dejo una nota en blanco? | Puede completarla después. Al cerrar el acta final, una nota en blanco cuenta como cero. |
| ¿Cuántos decimales admite una nota? | Hasta dos decimales, en el rango de 0 a 20. |
| ¿Cómo se redondea la nota final? | Solo se redondea la nota final, al entero: 0.5 o más sube al entero siguiente (13.50 pasa a 14). |
| ¿Qué significa NSP? | Que el estudiante no tiene ninguna nota registrada en el acta. |
| El botón **Validar y cerrar acta** está deshabilitado. | El acta solo puede cerrarse dentro de las fechas para actas finales del periodo, que se muestran en la pestaña **Acta final**. |
| Cerré el acta con un error. | El docente no puede modificar un acta cerrada. Solicite a la coordinación académica una rectificación autorizada por el administrador. |
| No recuerdo mi contraseña. | Use **¿Olvidaste tu contraseña?** en la página de ingreso (sección 2.2). |

# 9. SOPORTE

Para consultas, problemas de acceso, reasignación de grupos, fechas de actas o solicitudes de rectificación, el docente debe comunicarse a través de la coordinación académica del CILN o el correo institucional cid@unasam.edu.pe.

Al reportar un problema, indique el código del grupo, la pantalla en la que se encontraba, la fecha y hora aproximada, y el mensaje que mostró el sistema. Nunca comparta su contraseña.
