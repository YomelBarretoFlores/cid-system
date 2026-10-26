# Módulos y funcionalidades

Resumen operativo del Anexo Técnico (fuente: [`requisitos/anexo-tecnico-ciln.md`](requisitos/anexo-tecnico-ciln.md)) con las reglas de diseño acordadas. **Primero se cumple el TdR al 100 %**; lo adicional está en [`ideas-adicionales.md`](ideas-adicionales.md).

## Academico (oferta compartida)

- Ciclo: año, mes/periodo, fecha de inicio y fin.
- Grupo: código, idioma, nivel (Básico, Intermedio, Avanzado), ciclo (I a XII), modalidad (Presencial, Virtual, Semipresencial; Acelerado).
- Capacidad: cupo máximo, cupo mínimo de apertura, vacantes en tiempo real. `CHECK` vacantes ≥ 0.
- Asignación: docente, aula (física o enlace virtual), horario (días y rango de horas).
- **Esquema de evaluación configurable** por idioma/nivel/periodo: componentes (Parcial, Final, Participación), pesos (suman 100 %) y nota mínima. Se construye con Certificacion.

| Pantalla (menú "Oferta académica") | Reglas |
|---|---|
| Idiomas y plan de estudios | Código fijo tras crearlo y único; nombre único; ciclos I–XII únicos por idioma con nivel y horas lectivas. Se desactivan, no se borran; un idioma inactivo no ofrece sus ciclos para grupos nuevos |
| Aulas | Código fijo y único; capacidad > 0. `ActualizarAula` no deja bajar la capacidad por debajo del cupo de un grupo activo que usa el aula |
| Periodos | Validan año, fechas y ventana de matrícula como la base. Estados solo hacia adelante y de a uno (`CambiarEstadoPeriodo`): Planificado → Matrícula abierta → En curso → Cerrado |
| Grupos y horarios | `GuardarGrupo` y `GuardarHorario` bloquean el grupo y el aula: grupo virtual con enlace y sin aula; presencial o semipresencial con aula activa de capacidad ≥ cupo; cupo nunca menor que los matriculados; sin choque de aula en el mismo periodo, día y horas. Estados con `CambiarEstadoGrupo`: Programado → Abierto ⇄ Cerrado → Finalizado; Cancelado solo sin matriculados. Un grupo cancelado o finalizado ya no se edita y libera su aula |

Permisos: `academico.ver` (consultar) y `academico.gestionar` (crear, editar y cambiar estados).

## Personas

- Tipo y número de documento (DNI/CE/Pasaporte), apellidos, nombres, fecha de nacimiento, correo personal, celular.
- Tipo de vínculo: Estudiante UNASAM, Docente/Administrativo UNASAM, Público General (define la tarifa).
- Pantalla "Personas" (`personas.ver`, `personas.gestionar`): búsqueda por documento o nombre, también desde el buscador general del panel (`Ctrl/⌘ + K`) para quien tiene `personas.ver`. Las búsquedas del panel ignoran tildes y mayúsculas ("nunez" encuentra "Núñez"); DNI de 8 dígitos, CE o pasaporte de 6 a 12 caracteres; documento único; celular normalizado. Sin borrado, acciones masivas ni exportación (Ley 29733).
- Autorregistro en el portal (`RegistrarCuentaDeEstudiante`): crea la ficha como Público General, o vincula la ficha que ya tiene el CILN si su correo coincide. Solo el personal del CILN cambia el tipo de vínculo, porque define la tarifa. El contrato `FichaDelUsuario` entrega a los demás módulos la ficha de quien inició sesión.

## M1 · Matricula

| Funcionalidad | Reglas |
|---|---|
| Examen de clasificación | El postulante lo solicita en el portal (`/estudiante/clasificacion`) con un idioma activo y un pago validado de "Examen de clasificación" que no haya usado; una solicitud pendiente por idioma. La coordinación (`clasificacion.ver` consulta, `clasificacion.evaluar` registra) anota en el panel la fecha del examen, el puntaje (0 a 100) y el ciclo asignado del idioma; el sistema fija ese ciclo como habilitado para la matrícula (origen "Examen de clasificación"), reemplazando el anterior. Estados: Pendiente de Evaluación, Evaluado, Expirado; la tarea diaria `clasificacion:expirar` (00:10, Lima) vence las solicitudes sin evaluar a los 30 días, y su pago no se reutiliza |
| Matrícula autogestionada | `MatricularEstudiante`. Bloquea si: (a) el grupo no es del ciclo habilitado (sin habilitación, solo el ciclo 1); (b) el pago no está validado, es de otra persona, es de otro concepto o ya se usó (contrato `VerificacionDePago` de Pagos); (c) no hay vacantes o la matrícula del grupo no está abierta (contrato `ReservaDeVacante` de Academico, con `lockForUpdate` + `CHECK`). Tampoco permite dos grupos del mismo ciclo en el mismo periodo, ni siquiera con solicitudes simultáneas (bloqueo por persona con `pg_advisory_xact_lock`). Condición: Repitente si ya cursó el ciclo en otro periodo sin retirarse; si no, Regular. Reintentar con el mismo pago devuelve la misma matrícula |
| Portal del estudiante | `/estudiante/matricula`: grupos abiertos del ciclo habilitado en cada idioma (o el ciclo I), con vacantes que se refrescan solas; el estudiante elige un pago de matrícula validado que no haya usado y se matricula con `MatricularEstudiante`. `/estudiante/matriculas`: sus matrículas con horario y aula |
| Documentos | Ficha de Matrícula y Constancia de Inscripción en PDF (DOMPDF), descargables desde "Mis matrículas" del portal. Se generan al pedirlas con los datos vigentes y llevan el ULID de la matrícula como código. La ficha muestra estudiante, grupo, horario, aula, fecha y hora, condición y el estado del pago ("Verificado" si está validado); la constancia solo se emite para matrículas activas. Cada estudiante descarga solo las suyas; la de otra persona responde 404 |
| Traslados y retiros | Pantalla "Matrículas" del panel (`matricula.ver` consulta; `matricula.gestionar` traslada y retira). `TrasladarMatricula`: una matrícula activa pasa a otro grupo del mismo periodo y ciclo, abierto o cerrado y con vacantes (aun fuera de la ventana de matrícula); conserva pago, fecha y condición, y libera la vacante de origen. `RetirarMatricula`: marca la matrícula Retirada con su fecha y libera la vacante; no aplica a grupos cancelados o finalizados. Cada movimiento guarda grupo origen y destino, fecha, motivo (hasta 500 caracteres) y usuario que autoriza; no se edita ni se borra (trigger). Bloqueos: persona → matrícula → grupos en orden de ID. El personal descarga la ficha desde la matrícula |

## M2 · Pagos

| Funcionalidad | Reglas |
|---|---|
| Registro y validación | `RegistrarPago`: recibo JPG/PNG/PDF (tipo detectado por contenido) en disco privado y tarifa vigente según concepto, vínculo y fecha de pago. `ValidarPago`, `RechazarPago` (con motivo) y `AnularPago` (con motivo): bloquean la fila, solo permiten Pendiente → Validado o Rechazado y Validado → Anulado, y repetir la misma operación no cambia nada. Nunca se borra y todo cambio queda auditado |
| Campos | Número de recibo / código de operación, documento del pagador, monto, fecha, medio de pago, concepto TUPA/TUSNE, usuario y fecha de validación |
| Desbloqueo | Al validar se habilitan matrícula, constancias y solicitud de exámenes |
| Unicidad | (medio de pago + número de operación) es único entre pagos no rechazados: tras un rechazo, la persona registra el pago de nuevo con el recibo corregido; un pago desbloquea una sola matrícula o solicitud |
| Tarifas | Por concepto TUPA/TUSNE y tipo de vínculo, con vigencia sin superposición. Pantalla "Conceptos y tarifas" (permiso `pagos.configurar`): los conceptos se crean, editan y desactivan, nunca se borran; una tarifa nueva cierra la abierta del mismo vínculo el día anterior (`RegistrarTarifa`); una tarifa se cierra en una fecha (`CerrarTarifa`) y solo se borra si ningún pago la aplicó. Una tarifa aplicada no cambia de monto, vínculo, concepto ni inicio, ni se cierra antes de su último pago (trigger `pag_tarifas_protegida`) |
| Pantallas (panel) | `PagoResource`: listado con filtros (estado, concepto, medio, fecha) y búsqueda por número de operación, documento o nombre; detalle con revisión, anulación y descarga del recibo; registro de pagos en caja; acciones Validar, Rechazar y Anular según el estado y el permiso |
| Reportes | Página "Reporte de recaudación" del panel (permiso `pagos.reportar`): pagos validados en un rango de fechas de pago (por defecto, el mes en curso), con filtros por concepto y por periodo académico. Muestra el total y el número de operaciones, el consolidado por concepto TUPA/TUSNE, el consolidado por idioma y nivel (tomados de la matrícula o de la solicitud de clasificación que pagó; los demás van a "Sin idioma") y el detalle por estudiante en una tabla paginada con búsqueda (estudiante, documento, operación o concepto, sin importar tildes), filtros por medio de pago e idioma y orden por estudiante, fecha o monto. Se descarga en PDF (A4 horizontal) y en CSV (UTF-8 con BOM, protegido contra fórmulas) para DGF/Tesorería. Los montos se suman en céntimos enteros, sin decimales flotantes |
| Reglas aplicadas | Al usar un pago se verifica estado, persona y concepto del servicio; los datos de un pago no pendiente quedan congelados en la base; un pago desbloquea un solo servicio en total (por tabla con `UNIQUE (pago_id)` y entre servicios porque cada servicio exige su propio concepto) |
| Eventos | `PagoValidado`, `PagoRechazado`, `PagoAnulado` (después del commit). Anular un pago ya usado no retira la matrícula (pendiente de decisión) |
| Permisos | `PagoPolicy`: `pagos.ver` (listar y ver, incluido el recibo), `pagos.registrar`, `pagos.validar` (validar y rechazar), `pagos.anular` y `pagos.reportar` (reporte de recaudación); `ConceptoPolicy` y `TarifaPolicy`: `pagos.configurar`. Nadie edita ni borra pagos. Las Actions no verifican permisos; lo hacen la Policy y el panel |
| Fuera de alcance | Integración automática con sistemas externos |

## M3 · Docencia

| Funcionalidad | Reglas |
|---|---|
| Padrón | Un docente es una persona del padrón de Personas (nombres, DNI/CE, correo personal, celular) más su perfil docente: ID docente `DOC-00001`, correo institucional, RUC (11 dígitos), banco, número de cuenta (cifrado en la base, enmascarado en listados y fichas), condición laboral (Nombrado, Contratado, Tercero) y estado (Activo, Inactivo, De licencia). Correo institucional, RUC y persona son únicos |
| Perfil académico | Idiomas habilitados con su nivel máximo autorizado por idioma; disponibilidad horaria en franjas semanales (día, desde, hasta) sin superposición |
| Carga académica | Un docente por grupo, asignado desde la ficha del docente. Solo se asigna un docente Activo, habilitado en el idioma hasta el nivel del grupo, con todas las sesiones dentro de su disponibilidad (franjas contiguas cuentan como una) y **sin cruces de horario** con otros grupos suyos no terminales de periodos que se superponen. Reasignar reemplaza al docente; un grupo Cancelado o Finalizado no cambia |
| Horas | Por grupo: fechas de inicio y fin del periodo, número de semanas (tramos de 7 días desde el inicio, redondeados hacia arriba), días de dictado y horas totales (cada sesión programada entre esas fechas × su duración). Total del docente por periodo; un grupo cancelado se lista sin sumar horas |
| Coherencia | Mientras el docente tenga grupos vigentes no se puede quitar ni bajar un idioma que necesitan, dejar una sesión fuera de su disponibilidad ni sacarlo de Activo. Cambiar el horario de un grupo vuelve a validar al docente asignado. Bloqueo: fila del grupo y luego fila del docente |
| Documentos | La ficha de matrícula, "Mis matrículas" del portal y el listado de grupos muestran el docente asignado ("Por asignar" si no hay) |
| Permisos | `DocentePolicy`, `DisponibilidadPolicy` y `CargaAcademicaPolicy`: `docencia.ver` (consultar) y `docencia.gestionar` (registrar, editar, cambiar estado, disponibilidad y carga). Nadie borra docentes. `CostoPorHoraPolicy`: `docencia.costos`; el expediente de horas y sus descargas: `docencia.reportar` |
| Portal del docente | `/docente`: entra quien tiene cuenta del portal con correo verificado y cuya persona tiene perfil docente (el docente crea su cuenta en el portal con su documento y el correo personal registrado). "Mis grupos" lista sus grupos vigentes; solo abre un grupo que tiene a cargo en ese momento (si lo reasignan, la página deja de mostrarse) |
| Asistencia | Por cada clase del horario semanal del grupo entre el inicio del periodo y hoy (Lima), nunca una clase futura ni fuera del periodo. Cada estudiante con matrícula activa matriculado hasta esa fecha recibe Presente, Tardanza o Falta; una lista nueva empieza con todos Presente y guardar otra vez corrige. Solo en grupos Abiertos o Cerrados. La clase guarda fecha, horas, el docente que la dictó (se conserva si otro docente la corrige después de una reasignación) y quién registró por última vez (base de las horas dictadas de M3.3) |
| Pre-actas | Notas parciales por estudiante y componente del esquema vigente del grupo (idioma y nivel, luego idioma, luego nivel, luego general; vigente al inicio del periodo), de 0 a la nota máxima con hasta dos decimales; se pueden dejar en blanco y una nota borrada queda vacía, nunca se elimina. Solo en grupos Abiertos o Cerrados. El promedio, el estado final y el cierre son del acta final (M4, [ADR 0007](adr/0007-pre-actas-y-actas-finales.md)) |
| Concurrencia | Asistencia y notas toman el bloqueo de la fila del grupo, el mismo de la asignación: un docente reasignado no puede guardar después de que la reasignación se confirma |
| Costos por hora | Un costo por categoría del docente (condición laboral: Nombrado, Contratado, Tercero) con vigencia; no se editan. Un costo nuevo cierra el abierto de la misma categoría el día anterior; si reemplaza al vigente debe empezar después de hoy y no lleva fecha de fin (las horas ya dictadas conservan su costo). Solo se elimina un costo que aún no entra en vigencia, y se reabre el que había cerrado. Sin superposición (restricción de exclusión) |
| Expediente de horas | Por mes y año (Lima): las clases registradas del docente que las dictó, agrupadas por idioma y nivel, por el costo de su categoría vigente en cada fecha (una línea por costo). Horas redondeadas a centésimas por línea y monto bruto referencial = horas × costo, en céntimos; sin retenciones de ley. Las horas sin costo vigente se listan sin monto y con aviso. Página "Expediente de horas" del panel, PDF por docente y CSV del mes con todas las líneas |
| Fuera de alcance | Planilla y retenciones de ley (competencia de la DGF) |

## M4 · Certificacion

| Funcionalidad | Reglas |
|---|---|
| Actas finales | Solo el docente asignado y en fechas del calendario; promedio según el esquema configurable (el acta guarda copia del esquema usado); estados Aprobado/Desaprobado/NSP |
| Cierre | Bloqueo automático al validar; cambios posteriores requieren autorización del Administrador y quedan en la auditoría |
| Constancias y certificados | PDF institucional, emisión masiva o individual; correlativo único |
| Verificación QR | Código único (ULID) que no caduca; portal público con datos mínimos y rate limiting |

## M5 · Tablero

- KPIs financieros: ingresos por periodo/idioma/nivel, pagos pendientes de validación, rentabilidad por grupo (matrículas frente a horas docentes × tarifa).
- KPIs académicos: matrículas y crecimiento, deserción por nivel e idioma, demanda horaria.
- Filtros por periodo, idioma y nivel; exportación PDF/Excel.
- Pantalla «Tablero gerencial» del panel, con el permiso `tablero.ver`. Se calcula en cada consulta con queries dedicadas sobre las tablas de cada módulo (tiempo real); si el volumen lo exige, se pasará a vistas materializadas. **Sin analítica predictiva ni ML** (excluido por el TdR).

> Supuestos aplicados (morosidad como pagos pendientes de validación, atribución de la recaudación, costo docente, crecimiento, deserción y demanda horaria): ver [`decisiones-pendientes.md`](decisiones-pendientes.md) (#4, #80–#85).

## M6 · Tramites

| Funcionalidad | Reglas |
|---|---|
| Portal público | `https://cid.unasam.edu.pe/`: oferta, requisitos, fechas, costos TUPA/TUSNE |
| Acceso | Login de estudiantes y docentes (un guard, roles); recuperación con token al correo |
| Pipelines | Un flujo configurable por trámite (constancia, certificado, traslado, retiro): etapas, estados y condiciones en tablas propias + JSON validado, sin programar |
| Reglas | Bloqueo hasta que Pagos valide la tasa; barra de progreso; correo en cada cambio de estado (log de notificaciones) |
| Fuera de alcance | Asistente conversacional (chatbot) |

> El Anexo tiene una inconsistencia: las etapas de la barra de progreso (Solicitud recibida → Pago validado → En firma de Dirección → Listo) no coinciden con los estados (Pendiente de Pago, En Proceso, Observado, Finalizado). Supuesto aplicado: los estados son fijos y se derivan del avance; las etapas, con su porcentaje, se configuran por tipo de trámite en el panel («Tipos de trámite»). Ver [`decisiones-pendientes.md`](decisiones-pendientes.md) (#6, #74–#79).
