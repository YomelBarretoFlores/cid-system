# Módulos y funcionalidades

Resumen operativo del Anexo Técnico (fuente: [`requisitos/anexo-tecnico-ciln.md`](requisitos/anexo-tecnico-ciln.md)) con las reglas de diseño acordadas. **Primero se cumple el TdR al 100 %**; lo adicional está en [`ideas-adicionales.md`](ideas-adicionales.md).

## Academico (oferta compartida)

- Ciclo: año, mes/periodo, fecha de inicio y fin.
- Grupo: código, idioma, nivel (Básico, Intermedio, Avanzado), ciclo (I a XII), modalidad (Presencial, Virtual, Semipresencial; Acelerado).
- Capacidad: cupo máximo, cupo mínimo de apertura, vacantes en tiempo real. `CHECK` vacantes ≥ 0.
- Asignación: docente, aula (física o enlace virtual), horario (días y rango de horas).
- **Esquema de evaluación configurable** por idioma/nivel/periodo: componentes (Parcial, Final, Participación), pesos (suman 100 %) y nota mínima.

## Personas

- Tipo y número de documento (DNI/CE/Pasaporte), apellidos, nombres, fecha de nacimiento, correo personal, celular.
- Tipo de vínculo: Estudiante UNASAM, Docente/Administrativo UNASAM, Público General (define la tarifa).

## M1 · Matricula

| Funcionalidad | Reglas |
|---|---|
| Examen de clasificación | Solicitud → resultado (puntaje) → nivel asignado en el perfil. Estados: Pendiente de Evaluación, Evaluado, Expirado (el scheduler expira las vencidas) |
| Matrícula autogestionada | `MatricularEstudiante`. Bloquea si: (a) el grupo no es del ciclo habilitado (sin habilitación, solo el ciclo 1); (b) el pago no está validado, es de otra persona, es de otro concepto o ya se usó (contrato `VerificacionDePago` de Pagos); (c) no hay vacantes o la matrícula del grupo no está abierta (contrato `ReservaDeVacante` de Academico, con `lockForUpdate` + `CHECK`). Tampoco permite dos grupos del mismo ciclo en el mismo periodo, ni siquiera con solicitudes simultáneas (bloqueo por persona con `pg_advisory_xact_lock`). Condición: Repitente si ya cursó el ciclo en otro periodo sin retirarse; si no, Regular. Reintentar con el mismo pago devuelve la misma matrícula |
| Documentos | Ficha de Matrícula (PDF) y Constancia de Inscripción |
| Traslados y retiros | Grupo origen/destino, fecha, motivo y usuario que autoriza |

## M2 · Pagos

| Funcionalidad | Reglas |
|---|---|
| Registro y validación | `RegistrarPago`: recibo JPG/PNG/PDF (tipo detectado por contenido) en disco privado y tarifa vigente según concepto, vínculo y fecha de pago. `ValidarPago`, `RechazarPago` (con motivo) y `AnularPago` (con motivo): bloquean la fila, solo permiten Pendiente → Validado o Rechazado y Validado → Anulado, y repetir la misma operación no cambia nada. Nunca se borra y todo cambio queda auditado |
| Campos | Número de recibo / código de operación, documento del pagador, monto, fecha, medio de pago, concepto TUPA/TUSNE, usuario y fecha de validación |
| Desbloqueo | Al validar se habilitan matrícula, constancias y solicitud de exámenes |
| Unicidad | (medio de pago + número de operación) es único entre pagos no rechazados: tras un rechazo, la persona registra el pago de nuevo con el recibo corregido; un pago desbloquea una sola matrícula o solicitud |
| Tarifas | Por concepto TUPA/TUSNE y tipo de vínculo, con vigencia sin superposición. Pantalla "Conceptos y tarifas" (permiso `pagos.configurar`): los conceptos se crean, editan y desactivan, nunca se borran; una tarifa nueva cierra la abierta del mismo vínculo el día anterior (`RegistrarTarifa`); una tarifa se cierra en una fecha (`CerrarTarifa`) y solo se borra si ningún pago la aplicó. Una tarifa aplicada no cambia de monto, vínculo, concepto ni inicio, ni se cierra antes de su último pago (trigger `pag_tarifas_protegida`) |
| Pantallas (panel) | `PagoResource`: listado con filtros (estado, concepto, medio, fecha) y búsqueda por número de operación, documento o nombre; detalle con revisión, anulación y descarga del recibo; registro de pagos en caja; acciones Validar, Rechazar y Anular según el estado y el permiso |
| Reportes | Consolidado por periodo, idioma, nivel y concepto; PDF y Excel/CSV para DGF/Tesorería |
| Reglas aplicadas | Al usar un pago se verifica estado, persona y concepto del servicio; los datos de un pago no pendiente quedan congelados en la base; un pago desbloquea un solo servicio en total (por tabla con `UNIQUE (pago_id)` y entre servicios porque cada servicio exige su propio concepto) |
| Eventos | `PagoValidado`, `PagoRechazado`, `PagoAnulado` (después del commit). Anular un pago ya usado no retira la matrícula (pendiente de decisión) |
| Permisos | `PagoPolicy`: `pagos.ver` (listar y ver, incluido el recibo), `pagos.registrar`, `pagos.validar` (validar y rechazar) y `pagos.anular`; `ConceptoPolicy` y `TarifaPolicy`: `pagos.configurar`. Nadie edita ni borra pagos. Las Actions no verifican permisos; lo hacen la Policy y el panel |
| Fuera de alcance | Integración automática con sistemas externos |

## M3 · Docencia

| Funcionalidad | Reglas |
|---|---|
| Padrón | Especialidad/idiomas, disponibilidad, estado (Activo, Inactivo, De Licencia), RUC, cuenta bancaria (cifrada), banco, condición laboral, nivel máximo autorizado |
| Carga académica | Vincula docente con grupos; total de horas; **sin cruces de horario** |
| Asistencia y pre-actas | Durante las clases, según el cronograma (límite con M4 por definir) |
| Expediente de horas | Horas dictadas × costo por hora (con vigencia) = monto bruto referencial; PDF y Excel/CSV |
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
- Precalculado con el scheduler y caché. **Sin analítica predictiva ni ML** (excluido por el TdR).

## M6 · Tramites

| Funcionalidad | Reglas |
|---|---|
| Portal público | `https://cid.unasam.edu.pe/`: oferta, requisitos, fechas, costos TUPA/TUSNE |
| Acceso | Login de estudiantes y docentes (un guard, roles); recuperación con token al correo |
| Pipelines | Un flujo configurable por trámite (constancia, certificado, traslado, retiro): etapas, estados y condiciones en tablas propias + JSON validado, sin programar |
| Reglas | Bloqueo hasta que Pagos valide la tasa; barra de progreso; correo en cada cambio de estado (log de notificaciones) |
| Fuera de alcance | Asistente conversacional (chatbot) |

> El Anexo tiene una inconsistencia: las etapas de la barra de progreso (Solicitud recibida → Pago validado → En firma de Dirección → Listo) no coinciden con los estados (Pendiente de Pago, En Proceso, Observado, Finalizado). Ver [`decisiones-pendientes.md`](decisiones-pendientes.md).
