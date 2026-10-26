# Ideas adicionales (fase final)

Funcionalidades **fuera del TdR**. Se proponen al CILN **al final del desarrollo** y solo se implementan si las aprueba. No cuentan para la conformidad.

| Módulo | Idea | Notas |
|---|---|---|
| M4 | Firma digital (Firma Perú agente automatizado o ReFirma) | Agente: firma desatendida institucional, requiere certificado de agente automatizado y credenciales de la PCM. ReFirma: firma manual de una persona (encaja con "En firma de Dirección"). El CILN decide qué firma vale en sus certificados |
| M4 | Hash SHA-256 del PDF en la verificación del QR | Detecta documentos alterados |
| M4 | Equivalencia MCER (A1–C2) | Solo si el CILN aprueba formalmente la alineación curricular |
| M2 | Antifraude de recibos | Hash de la imagen (detecta solo duplicados exactos) además de la unicidad ya obligatoria |
| M2 | Conciliación por archivo de la DGF | Si la DGF entrega el reporte de recaudación |
| M1 | Validación de identidad con RENIEC vía PIDE | Requiere convenio y coordinación con la DSTI |
| M1 | Lista de espera automática | Aviso cuando se libera un cupo |
| M3 | Alerta temprana de deserción por faltas | Regla simple, no predictiva |
| M3 | Encuesta de satisfacción por docente | Alimenta el M5 |
| M5 | Análisis de cohortes y resumen semanal por correo | Descriptivo |
| M6 | Seguimiento de trámite sin login (código + DNI) | Con rate limiting |
| Auth | Login con cuenta institucional (Socialite) | Solo si la UNASAM usa Google Workspace |
| Transversal | Notificaciones dentro del sistema (campana en el panel y en los portales) | El TdR solo exige el correo por cada cambio de estado del trámite y su registro de envíos, que ya existen. Casos propuestos. Personal: pago registrado por validar, solicitud de clasificación nueva, trámite pagado por atender, pre-acta enviada por un docente. Estudiante: pago validado o rechazado (con motivo), trámite observado o finalizado, constancia o certificado listo, matrícula confirmada. Docente: grupo asignado, acta aprobada u observada. En el panel, los contadores del menú ya muestran lo pendiente |
