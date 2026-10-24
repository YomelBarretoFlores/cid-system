> Transcripción de `oficio-166-2026-unasam-dsti-informe-023-2026-fmaj.pdf` (fuente oficial). Ante cualquier diferencia, prevalece el PDF.

# Oficio N° 166-2026-UNASAM-DSTI/D e Informe Técnico N° 023-2026-FMAJ

| Documento | Emisor | Destinatario | Fecha |
|---|---|---|---|
| Oficio N° 166-2026-UNASAM-DSTI/D | Ing. Francisco Jesús Carranza Vega, Director DSTI | Dr. Oscar Guillermo Ellacuriaga San Martín, Director del Centro de Idiomas | 22/05/2026 |
| Informe Técnico N° 023-2026-FMAJ | Bach. Ing. Fernando Michael Alfaro Julca, Gestión de Sistemas y Desarrollo de Software | Director DSTI | 22/05/2026 |

**Conclusión del informe:** el sistema actual (`https://cid.unasam.edu.pe/`) presenta limitaciones técnicas, funcionales y de seguridad que comprometen su continuidad; resulta técnicamente recomendable un rediseño y nuevo desarrollo integral.

> **Regla del proyecto:** los problemas de las secciones 1.1 a 1.5 son errores que el nuevo sistema NO debe repetir, y las recomendaciones de la sección 3.1 son de cumplimiento obligatorio.

## 1. Análisis

### 1.1 Tecnología obsoleta

Desarrollado hace más de 10 años con PHP 5.6, sin soporte ni parches de seguridad desde 2018. Riesgos:

- No recibe parches ni actualizaciones de seguridad oficiales.
- Incompatibilidad con tecnologías y frameworks modernos.
- Altamente vulnerable a ataques informáticos.
- Incrementa el riesgo de compromiso de información institucional y datos académicos.
- Dificulta el mantenimiento, la escalabilidad y la modernización.
- Reduce la estabilidad operativa y el rendimiento.

### 1.2 Diagnóstico de seguridad informática

**Vulnerabilidades por tecnología obsoleta:** inyección SQL, ejecución remota de código, inclusión de archivos locales/remotos, Cross Site Scripting (XSS), gestión insegura de sesiones, validación insuficiente de entradas, exposición de información sensible.

**Riesgo de propagación a otros sistemas:** acceso no autorizado, compromiso de bases de datos, escalamiento de privilegios, alteración o eliminación de información, uso del sistema como vector hacia otros servicios, compromiso de servidores y recursos compartidos de la red universitaria.

**Malas prácticas de desarrollo:** código monolítico y acoplado, ausencia de arquitectura modular, validaciones insuficientes, falta de separación entre lógica de negocio y presentación, deficiente manejo de errores y excepciones, ausencia de estándares OWASP, dificultad de trazabilidad y mantenimiento.

### 1.3 Diagnóstico funcional

**Problemas de lógica funcional:** procesos poco intuitivos, flujo funcional inconsistente, duplicidad de operaciones, validaciones incompletas, errores en reglas y lógica de negocio, escasa adaptabilidad a nuevos procesos.

**Limitaciones frente a nuevos requerimientos:** arquitectura rígida, baja escalabilidad, limitada interoperabilidad, ausencia de integración con plataformas modernas, dificultad para implementar nuevas funcionalidades, dependencia de tecnologías desactualizadas.

### 1.4 Experiencia de usuario y diseño

**Diseño no responsive:** problemas de visualización, dificultades de navegación, baja accesibilidad, experiencia deficiente en móviles.

**Interfaz no intuitiva ni dinámica:** navegación poco amigable, diseño visual desactualizado, estructura rígida, interacción limitada, baja experiencia de usuario, procesos poco optimizados.

### 1.5 Impacto institucional

- **Tecnológicos:** vulneración de información, interrupción de servicios, pérdida de integridad de datos, incremento de incidentes de seguridad.
- **Operativos:** dificultad de mantenimiento, dependencia de tecnologías obsoletas, incremento de tiempos de soporte, limitaciones para nuevas implementaciones.
- **Institucionales:** afectación reputacional, exposición de datos institucionales, riesgo para otros sistemas conectados a la infraestructura universitaria.

## 3. Recomendaciones (3.1)

Rediseño y desarrollo de un sistema nuevo bajo tecnologías modernas y seguras:

| Eje | Recomendación |
|---|---|
| Tecnologías | PHP 8.x, framework moderno (Laravel, Symfony o equivalente), arquitectura MVC, API REST, bases de datos optimizadas, servicios desacoplados |
| Seguridad | OWASP Top 10, validación robusta de entradas, gestión segura de sesiones, protección CSRF/XSS/SQL Injection, cifrado de datos, auditoría y trazabilidad |
| UX/UI | Responsive, intuitiva, accesible, dinámica, adaptable a móviles, centrada en la experiencia de usuario |
| Arquitectura escalable | Integraciones futuras, crecimiento institucional, interoperabilidad, alta disponibilidad, escalabilidad tecnológica |

Cómo responde el nuevo sistema a cada punto: ver [`../buenas-practicas.md`](../buenas-practicas.md) y [`../seguridad.md`](../seguridad.md).
