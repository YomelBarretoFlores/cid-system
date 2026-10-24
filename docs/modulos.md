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
| Matrícula autogestionada | Bloquea si: (a) no aprobó el ciclo anterior o no tiene el nivel; (b) el pago no está validado (consulta síncrona a Pagos); (c) no hay vacantes (`lockForUpdate` + `CHECK`). Condición: Regular o Repitente |
| Documentos | Ficha de Matrícula (PDF) y Constancia de Inscripción |
| Traslados y retiros | Grupo origen/destino, fecha, motivo y usuario que autoriza |

## M2 · Pagos

| Funcionalidad | Reglas |
|---|---|
| Registro y validación | Recibo digital (JPG/PNG/PDF) como evidencia; valida el administrador o cajero. Estados: Pendiente, Validado, Rechazado y Anulado (supuesto); nunca se borra y todo cambio queda auditado |
| Campos | Número de recibo / código de operación, documento del pagador, monto, fecha, medio de pago, concepto TUPA/TUSNE, usuario y fecha de validación |
| Desbloqueo | Al validar se habilitan matrícula, constancias y solicitud de exámenes |
| Unicidad | (medio de pago + número de operación) es único; un pago desbloquea una sola matrícula o solicitud |
| Tarifas | Por concepto TUPA/TUSNE y tipo de vínculo, con vigencia sin superposición |
| Reportes | Consolidado por periodo, idioma, nivel y concepto; PDF y Excel/CSV para DGF/Tesorería |
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
