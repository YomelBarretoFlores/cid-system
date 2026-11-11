# Sistema actual del CILN: inventario y brechas

Revisión del sistema que hoy usa el CILN (`cid.intranet.unasam.edu.pe`), hecha el 2026-11-10 para saber si el sistema nuevo cubre todo lo que tiene. Cada brecha queda como pendiente para decidir con el CILN; ninguna se implementa sin su aprobación.

**Cómo se revisó.** Recorrido de solo lectura con una cuenta de control académico: se abrieron menús, listados y vistas de reportes, sin enviar formularios ni usar botones que registren, editen o activen. No se copiaron datos personales; solo estructura (pantallas, campos, columnas, opciones de listas y reportes). La web pública antigua (`cid.unasam.edu.pe`) está fuera de servicio y con el certificado vencido, así que se revisó su copia archivada y la de `cid.intranet.unasam.edu.pe`.

## Inventario del sistema actual

| Menú | Pantalla | Qué hace |
|---|---|---|
| Web pública | Portada | Carrusel (Inscríbete, Cronograma, Descargar FUT, Resultados del examen de clasificación), Horarios, Workshop, Requisitos de certificado y constancia, seis idiomas, enlace a la intranet. Cronograma, horarios y comunicados son imágenes; requisitos y FUT, PDF |
| Acceso | Ingreso y registro | Ingreso con DNI y contraseña. El registro de alumno pide DNI, apellido paterno, apellido materno, nombres, correo, contraseña, fecha de nacimiento, género, dirección y celular |
| Inicio | Ficha del usuario | Foto, nombres, fecha de nacimiento, sexo, dirección, celular y correo. Aviso: la foto de perfil se usa para imprimir el carné |
| Control académico | Programación de horario | Por aula, mes y año: horarios del aula (hora, idioma, nivel, ciclo, docente). Registro de horario: idioma, modalidad, nivel, ciclo, mes, año, docente, hora (franjas fijas) y aula. Administración de grupos: hora, aula, docente, cantidad de alumnos, grupo |
| Control académico | Grupos fusionados | Un docente, en un horario ya registrado, toma además otro grupo (idioma, modalidad, nivel, ciclo) en la misma hora y aula |
| Control académico | Programación de asistencia | Por año y mes: días de clase (número de día, fecha, condición) |
| Control académico | Programación de mes regular | Por mes: inicio, fin, fecha extemporánea, entrega de registros, fecha de actas, cantidad de días; botones Editar y Activar mes |
| Control académico | Administración de mes acelerado | Registro de un grupo con docente, fecha de inicio, fecha de fin y fecha de actas propias; listado por año |
| Reportes | Horarios por aula | Cuadrícula de franjas horarias por grupos (hasta cuatro) de un aula en un mes |
| Reportes | Reporte de grupos | Filtros: modalidad, año, mes, idioma, nivel, ciclo. Columnas: idioma, modalidad, nivel, ciclo, docente, cantidad de alumnos, turno y reporte del grupo |
| Reportes | Actas y preactas | Mismos filtros; columnas con currícula. Por grupo: «PreActas y Asistencias» (registro de evaluación y control de asistencia) e «Impresión de actas» |
| Reportes | Reportes estadísticos | Matrículas de los últimos siete meses (barras); por mes y año: reporte estadístico por idioma, administrativo, financiero y de Beca 18; reporte mensual (matriculados y aprobados) y reporte intensivo |
| Reportes | Consolidado de notas | Búsqueda de alumno por apellidos y nombres: DNI, código, idioma, modalidad, nivel e impresión |
| Perfil | Modificar perfil | Foto, dirección, celular, correo y cambio de contraseña |

### Programas, currículas, niveles y ciclos

En el sistema actual «modalidad» es el **programa**, no la forma de dictado. Cada nivel pertenece a una currícula, que puede estar activa (A) o desactivada (D).

| Programa | Niveles | Ciclos |
|---|---|---|
| Niños | Pre-básico, Básico, Intermedio, Avanzado | 12 por nivel |
| Jóvenes y Adultos | Básico, Intermedio, Avanzado; en Francés además Avanzado Plus | Inglés, currícula 2 (activa): 10, 10 y 6; la currícula 1 está desactivada. Francés, currícula 1: Intermedio, Avanzado y Avanzado Plus con 6 cada uno; Básico sin ciclos |
| Intensivo | Básico | Ciclos agrupados de dos en dos (1 - 2 … 9 - 10) |
| Especialización | Módulos 1 a 4 | 4 por módulo |

Idiomas: Inglés, Francés, Italiano, Quechua, Alemán, Portugués y Japonés. Franjas fijas de lunes a viernes (8:00-9:30, 9:30-11:00, 11:00-12:30, 2:00-3:30, 3:30-5:00, 5:00-6:30, 6:30-8:00, 8:00-9:30 p. m., 7:00-8:30 a. m.) y de fin de semana (sábado o domingo de 7:00 a 3:00, domingo de 9:00 a 1:00); 31 aulas.

### Evaluación y actas

El registro de evaluación de cada grupo lleva: Escuchar (E), Hablar (H), Escribir (ES), Leer (L), Promedio de habilidades (PH), Examen parcial (EP), Examen final (EF), Promedio de exámenes (PE) y Promedio final (PF), más el control de asistencia del mes. Firmas: Vº Bº del Director(a) Académico y firma del docente.

### Pagos

El reporte financiero agrupa por idioma y programa: apellidos y nombres, modalidad de pago, lugar de pago, número de comprobante y monto, con total. Modalidades de pago observadas: Normal, Tercio superior, Normal extemporáneo, Tercio superior extemporáneo, Beca hermanos y Administrativo; hay además un reporte de Beca 18. Lugares de pago: Banco de la Nación y Tesorería.

## Brechas con el sistema nuevo

Estado de todas: **pendiente de decisión del CILN**.

| # | Brecha | Sistema nuevo hoy | Impacto si se aprueba |
|---|---|---|---|
| S1 | Programas por idioma (Niños, Jóvenes y Adultos, Intensivo, Especialización), cada uno con sus niveles y ciclos | Una sola secuencia de hasta 12 ciclos por idioma; «modalidad» es presencial, virtual o semipresencial | Alto: cambia el plan de estudios, la matrícula, la oferta y los reportes |
| S2 | Niveles Pre-básico y Avanzado Plus, y Módulos 1 a 4 | Tres niveles fijos (Básico, Intermedio, Avanzado) | Alto: va con S1 |
| S3 | Currícula con versión y estado (activa o desactivada) por nivel; el grupo indica su currícula | No existe | Medio |
| S4 | Ciclos agrupados del programa Intensivo (1 - 2, 3 - 4…) | No existe | Medio: va con S1 |
| S5 | Modalidades de pago: Tercio superior, Beca hermanos, Administrativo, Beca 18 | La tarifa depende solo del concepto y del vínculo con la UNASAM | Alto: cambia tarifas, registro y validación de pagos |
| S6 | Pago extemporáneo: recargo después de la fecha extemporánea del mes | No existe ni la fecha ni el recargo | Medio: va con S5 y S7 |
| S7 | Fechas del mes: fecha extemporánea, entrega de registros y cantidad de días | El periodo tiene matrícula, inicio y fin de clases y plazo de actas | Bajo |
| S8 | Grupos acelerados con fechas propias de inicio, fin y actas | Solo una marca «acelerado»; usa las fechas del periodo | Medio |
| S9 | Grupos fusionados: un docente con varios grupos en la misma hora y aula | Prohibido por las reglas de cruce de docente y de aula | Medio: hay que cambiar esas reglas |
| S10 | Calendario de días de clase por mes (feriados o días sin clase) | Los días de clase salen del horario semanal; sin feriados (#46) | Medio: afecta asistencia y horas del docente |
| S11 | Franjas horarias fijas | Horario libre por día, hora de inicio y fin | Bajo: se puede cargar como catálogo |
| S12 | Promedios intermedios en el acta (Promedio de habilidades y Promedio de exámenes) | Suma ponderada plana de componentes; el promedio final se reproduce, los intermedios no | Medio |
| S13 | Control de asistencia dentro de la pre-acta impresa y firmas (Vº Bº Director(a) Académico, docente) | La pre-acta no trae asistencia ni firmas; el acta final sí trae firmantes | Bajo |
| S14 | Reporte de horarios por aula (cuadrícula) | No existe | Bajo |
| S15 | Reporte de grupos con docente, programa y turno, y filtros por ciclo y programa | El reporte de matriculados no trae docente, programa ni turno | Bajo |
| S16 | Reportes estadístico por idioma, administrativo, financiero por idioma y programa, Beca 18, mensual de matriculados y aprobados, intensivo | Tablero, reporte de matriculados, de notas y de recaudación; sin Beca 18, administrativo ni corte por programa | Medio |
| S17 | Consolidado de notas de un alumno impreso por el personal | El estudiante descarga su récord; el personal emite la constancia de notas | Bajo |
| S18 | Sexo del estudiante (lo pide el registro) | No se guarda | Bajo |
| S19 | Carné del estudiante con foto | Se guarda la foto; no se imprime carné | Bajo |
| S20 | Ingreso con DNI | Ingreso con correo (#24) | Bajo |
| S21 | Japonés entre los idiomas | Los idiomas son un catálogo; se agrega sin cambiar código | Ninguno: es carga de datos |
| S22 | Web pública: Descargar FUT, resultados del examen de clasificación, comunicados, Workshop | La portada no los tiene | Bajo |

## Lo que no se revisó

- La tabla de programas, currículas y ciclos se levantó completa para Inglés (los cuatro programas) y para Francés en Jóvenes y Adultos. Falta Italiano, Quechua, Alemán, Portugués y Japonés, y los demás programas de Francés.
- Solo se vio el rol de control académico. No se vieron las pantallas de caja, dirección, docente ni estudiante del sistema actual, si existen.
- No se abrieron formularios de edición ni se pulsaron Registrar, Editar o Activar mes, así que no se conocen sus validaciones.
- No se abrieron los reportes estadístico por idioma, administrativo, Beca 18, mensual ni intensivo; solo el financiero de un mes.
- La programación de asistencia, el mes acelerado y el consolidado de notas se vieron sin datos cargados (antes de elegir mes o alumno).

## Fallas del sistema actual que no deben repetirse

- Muestra en producción los errores técnicos completos, con rutas del servidor (modo de depuración activo).
- El acta de un grupo sin notas falla por una división entre cero.
- El dominio público antiguo está caído y su certificado venció el 2026-11-07.
- La web antigua tenía un chat que restablecía la contraseña solo con el DNI, y el historial del dominio muestra rutas de spam inyectadas.
