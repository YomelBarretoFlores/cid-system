# Decisiones pendientes

Temas abiertos que bloquean o afectan partes del sistema. Al cerrarse uno, registrar la decisión (ADR si es de fondo) y quitarlo de aquí.

| # | Tema | Responsable | Afecta | Bloquea |
|---|---|---|---|---|
| 1 | Migración de datos del sistema actual (PHP 5.6): ¿se migra historial de estudiantes, notas y certificados? | Consultar al CILN | Personas, Matricula, Certificacion | Plan de migración y conciliación |
| 2 | Roles del sistema (coordinación, caja, administración, dirección, docente, estudiante). Ya existen los permisos por acción de Pagos (`pagos.ver`, `pagos.registrar`, `pagos.validar`, `pagos.anular`); falta decidir qué rol agrupa cuáles | Equipo | Shared/Auth, Policies | Asignación de permisos a roles |
| 3 | Límite entre pre-actas (M3) y actas finales (M4) | Equipo, durante el desarrollo | Docencia, Certificacion | — |
| 4 | Significado de "tasa de morosidad" (M5) en un modelo de pago previo | Más adelante | Tablero | KPI financiero |
| 5 | Requisitos no funcionales: usuarios concurrentes en matrícula, disponibilidad, respaldos | Acordar con CILN y DSTI | Infraestructura | Dimensionamiento y conformidad final |
| 6 | Etapas de la barra de progreso vs estados del trámite (inconsistencia del Anexo) | Corregir con el CILN | Tramites | Modelo de pipelines |
| 7 | Pesos del esquema de evaluación y nota mínima | CILN (se implementa configurable) | Academico, Certificacion | Datos iniciales |
| 8 | Entorno de despliegue de la DSTI (SO, servidor web, Docker sí/no, SMTP) | DSTI | Despliegue, PDF (DOMPDF o Gotenberg) | Guía de despliegue |
| 9 | Supuesto: hasta 12 ciclos por idioma, cada uno con su nivel; la distribución por nivel (por ejemplo 4/4/4) y las horas lectivas son datos configurables | Confirmar con el CILN | Academico | Carga inicial del plan de estudios |
| 10 | Supuesto: el prerrequisito es el ciclo habilitado por idioma (inicio en ciclo 1, clasificación o aprobación lo cambian) | Confirmar con el CILN | Matricula | Reglas de matrícula |
| 11 | Supuesto: reglas de vigencia y costo del examen de clasificación (`vence_en`) | Confirmar con el CILN | Matricula, Pagos | Expiración automática |
| 12 | Supuesto: las tarifas varían por concepto y tipo de vínculo, no por idioma ni nivel | Confirmar con el CILN | Pagos | Carga de tarifas reales |
| 13 | Supuesto: se agrega el estado "Anulado" a los del Anexo (Pendiente, Validado, Rechazado) para revertir pagos sin borrarlos; Rechazado y Anulado son finales | Confirmar con el CILN | Pagos | — |
| 14 | Supuesto: un pago rechazado libera su número de operación para registrarlo de nuevo con el recibo corregido | Confirmar con el CILN | Pagos | — |
| 15 | Supuesto: la matrícula exige grupo Abierto, periodo en Matrícula abierta y la fecha de hoy (hora de Lima) dentro de la ventana de matrícula | Confirmar con el CILN | Academico, Matricula | — |
| 16 | Supuesto: es Repitente quien ya tiene una matrícula no retirada en otro grupo del mismo ciclo | Confirmar con el CILN | Matricula | — |
| 17 | ¿Qué pasa con la matrícula cuando se anula el pago que la habilitó? Hoy se emite `PagoAnulado` y la matrícula sigue activa | Consultar al CILN | Pagos, Matricula | Reacción automática a la anulación |
| 18 | Supuesto: los pagos no se editan desde el panel; un pago pendiente con datos errados se rechaza y se registra de nuevo | Confirmar con el CILN | Pagos | — |
| 19 | Supuesto: las tarifas no se editan; una tarifa nueva cierra la anterior del mismo vínculo el día previo, y solo se borra una tarifa que ningún pago aplicó | Confirmar con el CILN | Pagos | — |
| 20 | Supuesto: permisos `academico.ver/gestionar` y `personas.ver/gestionar`; caja puede registrar personas | Confirmar con el CILN | Academico, Personas | Asignación a roles (#2) |
| 21 | Supuesto: los periodos avanzan de estado solo hacia adelante; los grupos pasan Programado → Abierto ⇄ Cerrado → Finalizado y se cancelan solo sin matriculados | Confirmar con el CILN | Academico | — |
| 22 | Supuesto: dos grupos del mismo periodo no comparten aula el mismo día en horas que se cruzan; la capacidad del aula limita el cupo de los grupos presenciales | Confirmar con el CILN | Academico | — |
| 23 | ¿Se pueden editar las fechas de un periodo cerrado? Hoy sí; los grupos cancelados o finalizados ya no se editan | Consultar al CILN | Academico | — |
