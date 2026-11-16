# Documentación del CILN

| Documento | Para qué |
|---|---|
| [requisitos/anexo-tecnico-ciln.md](requisitos/anexo-tecnico-ciln.md) | Anexo Técnico (TdR) en Markdown; el oficial es el `.docx` |
| [requisitos/informe-tecnico-023-2026-fmaj.md](requisitos/informe-tecnico-023-2026-fmaj.md) | Diagnóstico de la DSTI: errores a no repetir (1.1–1.5) y recomendaciones (3.1) |
| [arquitectura.md](arquitectura.md) | Monolito modular, capas, carpetas y reglas de desacople |
| [tecnologia.md](tecnologia.md) | Stack, versiones, paquetes y herramientas |
| [modulos.md](modulos.md) | Funcionalidades por módulo y sus reglas |
| [modelo-de-datos.md](modelo-de-datos.md) | Esquema de PostgreSQL, diagrama ER y restricciones |
| [buenas-practicas.md](buenas-practicas.md) | Código limpio, resiliencia, integridad y lo que no se hace |
| [seguridad.md](seguridad.md) | OWASP, Ley 29733, auditoría inmutable |
| [testing.md](testing.md) | Estrategia de pruebas |
| [despliegue.md](despliegue.md) | Guía para la DSTI (borrador) |
| [decisiones-pendientes.md](decisiones-pendientes.md) | Temas abiertos |
| [sistema-actual.md](sistema-actual.md) | Inventario del sistema que hoy usa el CILN y brechas pendientes de decisión |
| [ideas-adicionales.md](ideas-adicionales.md) | Mejoras fuera del TdR para proponer al final |
| [adr/](adr/) | Registro de decisiones de arquitectura |
| [manuales/informe-tecnico-final.md](manuales/informe-tecnico-final.md) | Entregable: Informe Técnico N.° 002-2026-YJBF, informe final y matriz de cumplimiento del TdR |
| [manuales/diccionario-de-datos.md](manuales/diccionario-de-datos.md) | Entregable: diccionario de datos, generado del catálogo de PostgreSQL con `php artisan docs:diccionario-de-datos` |

Los entregables (informe técnico final, diccionario de datos y, más adelante, los manuales de usuario) van en `manuales/`, escritos en Markdown y convertidos a Word/PDF con el formato institucional del Informe Técnico N.° 001-2026-YJBF. El diccionario no se edita a mano: las descripciones viven como comentarios en la base de datos (una migración por cambio) y una prueba falla si una tabla o columna nueva queda sin descripción.
