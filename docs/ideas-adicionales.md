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
