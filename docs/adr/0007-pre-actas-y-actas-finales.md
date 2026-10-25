# ADR 0007: Límite entre pre-actas (M3) y actas finales (M4)

- Estado: aceptado por el equipo (2026-10-24); pendiente de confirmación del CILN ([decisiones-pendientes](../decisiones-pendientes.md) #45)

## Contexto

El Anexo pide en M3.2 "registro de calificaciones (pre-actas) durante las clases" y en M4.1 actas finales con "cálculo automático de promedios", "Estado Final (Aprobado/Desaprobado/NSP)" y "cierre y bloqueo automático de actas validadas". Sin un límite claro, las dos funciones calcularían y cerrarían lo mismo.

## Decisión

| Tema | Pre-acta (M3, Docencia) | Acta final (M4, Certificacion) |
|---|---|---|
| Qué guarda | Nota parcial por estudiante y componente del esquema vigente del grupo | Copia del esquema usado, notas, promedio y estado final |
| Cuándo | Mientras el grupo está Abierto o Cerrado | En las fechas del calendario de notas |
| Quién | El docente a cargo del grupo | El docente a cargo; cambios posteriores con autorización del Administrador |
| Cálculo | Ninguno | Promedio ponderado y Aprobado/Desaprobado/NSP |
| Cierre | No se cierra; cada cambio queda en la auditoría | Se cierra y bloquea al validarse |

El acta final toma las notas de la pre-acta como punto de partida.

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| Una sola tabla de notas con estado borrador/cerrado | Mezcla dos módulos y obliga a Docencia a conocer reglas de cierre de Certificacion |
| Calcular el promedio en la pre-acta | Duplicaría la regla de redondeo y aprobación antes de que el CILN la defina para el acta |

## Consecuencias

- Docencia expone sus notas parciales a Certificacion por un contrato cuando se construya M4.
- Un componente del esquema con notas no se puede borrar (llave foránea restrictiva); un esquema nuevo aplica a los periodos que empiezan dentro de su vigencia.

## Implementación (M4.1, 2026-10-25)

- Docencia expone `NotasParcialesDelGrupo` y `DocenteDelGrupo`; Academico expone `CursoDelGrupo` con el calendario de actas del periodo (`actas_inicio`, `actas_fin`).
- El acta no tiene borrador guardado: el docente ve una vista previa calculada desde la pre-acta y, al validarla, se guarda la copia ya cerrada (`cer_actas`, `cer_notas_finales`).
- Una corrección posterior pasa por `cer_rectificaciones`: el Administrador la autoriza con motivo, el acta queda "En rectificación", el docente corrige y vuelve a validar.
- Triggers de PostgreSQL rechazan cambios en un acta cerrada y en sus notas, y cualquier borrado, aunque se hagan por SQL directo.
