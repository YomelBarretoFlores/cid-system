# Decisiones pendientes

Temas abiertos que bloquean o afectan partes del sistema. Al cerrarse uno, registrar la decisión (ADR si es de fondo) y quitarlo de aquí.

| # | Tema | Responsable | Afecta | Bloquea |
|---|---|---|---|---|
| 1 | Migración de datos del sistema actual (PHP 5.6): ¿se migra historial de estudiantes, notas y certificados? | Consultar al CILN | Personas, Matricula, Certificacion | Plan de migración y conciliación |
| 2 | Roles del sistema (coordinación, caja, administración, dirección, docente, estudiante) | Equipo | Shared/Auth, Policies | Permisos por acción |
| 3 | Límite entre pre-actas (M3) y actas finales (M4) | Equipo, durante el desarrollo | Docencia, Certificacion | — |
| 4 | Significado de "tasa de morosidad" (M5) en un modelo de pago previo | Más adelante | Tablero | KPI financiero |
| 5 | Requisitos no funcionales: usuarios concurrentes en matrícula, disponibilidad, respaldos | Acordar con CILN y DSTI | Infraestructura | Dimensionamiento y conformidad final |
| 6 | Etapas de la barra de progreso vs estados del trámite (inconsistencia del Anexo) | Corregir con el CILN | Tramites | Modelo de pipelines |
| 7 | Pesos del esquema de evaluación y nota mínima | CILN (se implementa configurable) | Academico, Certificacion | Datos iniciales |
| 8 | Entorno de despliegue de la DSTI (SO, servidor web, Docker sí/no, SMTP) | DSTI | Despliegue, PDF (DOMPDF o Gotenberg) | Guía de despliegue |
