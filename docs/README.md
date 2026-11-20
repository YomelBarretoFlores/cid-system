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
| [despliegue.md](despliegue.md) | Guía de instalación, actualización y operación para la DSTI |
| [decisiones-pendientes.md](decisiones-pendientes.md) | Registro de decisiones del proyecto (supuestos y acuerdos con el área usuaria), citado con «#» en el Informe Técnico Final |
| [sistema-actual.md](sistema-actual.md) | Inventario del sistema anterior del CILN |
| [adr/](adr/) | Registro de decisiones de arquitectura |

Los entregables del servicio (Informe Técnico Final, manuales de usuario y Diccionario de Datos) se entregan en Word y PDF. El diccionario se genera con `php artisan docs:diccionario-de-datos`: las descripciones viven como comentarios en la base de datos (una migración por cambio) y una prueba falla si una tabla o columna nueva queda sin descripción.
