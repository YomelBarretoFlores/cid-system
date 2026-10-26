# ADR 0008: Sistema de diseño compartido para portales y panel

- Estado: aceptado por el equipo (2026-10-24), a partir del diseño final entregado por el usuario (`design_handoff_ciln`).

## Contexto

Los portales (Blade + Livewire) y el panel (Filament) tenían estilos propios: azul genérico en los portales y el tema por defecto de Filament. El diseño final define colores, tipografía, radios y componentes de alta fidelidad que deben verse iguales en las tres interfaces, sin copiar clases de pantalla en pantalla.

## Decisión

| Tema | Decisión |
|---|---|
| Fuente única de estilos | `resources/css/ciln/tokens.css` (bloque `@theme` de Tailwind 4): paletas `piedra` y `oro`, colores semánticos (`lienzo`, `tarjeta`, `tinta`, `secundario`, `acento`, `enlace`, pares de estado), radios, sombras y tamaños. Lo importan `resources/css/app.css` (portales) y `resources/css/filament/admin/theme.css` (panel). |
| Repetición | Se resuelve con componentes Blade (`x-ciln.*` para la identidad, `x-portal.*` para controles y bloques) y con pocas utilidades `@utility` (`control`, `casilla`, `enlace`, `tarjeta-interna`, `columnas`, `tabla-reporte`). No se copian cadenas de clases entre pantallas ni se encadena `@apply`. |
| Selectores y fechas | Sin controles nativos: en los portales, `x-portal.seleccion` (lista con búsqueda y banderas) y `x-portal.fecha` (calendario con vista de años), componentes Alpine registrados una vez; en el panel, `Select` y `DatePicker` configurados como no nativos para todo el panel. |
| Estados de controles | Por atributos de accesibilidad (`aria-invalid`, `aria-current`, `:checked`), no por clases condicionales. |
| Panel | Tema propio de Filament (`->viteTheme()`); Filament conserva sus tablas, formularios y acciones. Sus paletas se pasan desde `App\Shared\Ui\PaletaCiln`, copia en PHP de `tokens.css`; la prueba `tests/Unit/PaletaCilnTest.php` falla si difieren. Sin barra superior: buscador y menú de usuario van en la barra lateral. Sin modo oscuro. |
| Tipografía | Figtree alojada en el propio servidor (manifiesto de fuentes de Vite), sin pedidos a Google Fonts en tiempo de ejecución. |
| Identidad | Emblema en WebP (`public/images/ciln/`) y banderas de idioma dibujadas en SVG (`x-ciln.hoja-bandera`): circulares junto al nombre del idioma; en forma de hoja solo en las ilustraciones de la portada y del acceso. |

## Alternativas descartadas

- **Publicar las vistas de Filament**: más control, pero cada actualización de Filament exigiría revisar vistas copiadas.
- **Clases utilitarias repetidas en cada pantalla**: rápido al inicio; cualquier ajuste del diseño obligaría a buscar y reemplazar en decenas de vistas.
- **Google Fonts**: envía la IP de cada visitante a un tercero y depende de un servicio externo.

## Consecuencias

- Cambiar un color o un radio es editar `tokens.css` (y `PaletaCiln` si es una paleta del panel; la prueba lo recuerda).
- Las pantallas nuevas (M4, M5, M6) se construyen con los mismos componentes y quedan con el estilo final desde el inicio.
- El tablero completo del diseño espera los datos de M5; la portada pública, M6.
