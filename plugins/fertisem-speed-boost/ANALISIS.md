# Fertisem Speed Boost — análisis de la 1.3.1 y cambios en la 2.0.0

## Errores graves (rompían cosas o no hacían nada)

| # | Dónde (1.3.1) | Problema | Solución en la 2.0.0 |
|---|---|---|---|
| 1 | D) Defer JS | `str_replace(' src', ' defer src', $tag)` sobre toda la etiqueta. Difería scripts con código inline *after* o con dependientes que no se difieren → errores `wp / _ / moment is not defined`. | Se usa la API nativa de WP 6.3+ (`strategy => defer`), que revisa dependencias y scripts inline y, si no es seguro, deja el script normal. Lista de exclusión ampliada y editable. |
| 2 | `includes/assets.php` | Objetos falsos de `wp.i18n`, `_` y `moment` (moment devolvía fechas vacías). Escondían el error real y hacían que otros plugins usaran funciones vacías. Además el archivo nunca se cargaba. | Eliminado. El origen del problema era el punto 1. |
| 3 | I) Widgets externos | Hacía `el.src = data-src` sobre la misma etiqueta: el navegador **nunca** ejecuta eso. Y nada convertía los scripts a `data-src`. La opción estaba activa pero no hacía nada. | Filtro HTML que convierte los scripts que coinciden con palabras clave a `type="fsb/delay"` y un loader que los vuelve a crear en orden al interactuar. Probado en Chromium. |
| 4 | Loader nuevo (en pruebas) | Chrome lanza un `mousemove` sintético al cargar: con ese evento como disparador el retraso no servía (Lighthouse también lo dispara). | Solo cuenta movimiento real (`movementX/Y`). |
| 5 | O) jQuery Migrate | `src = ''` lo quitaba en todo el sitio **y en el admin**. El comentario decía "silenciar avisos". | Por defecto solo silencia (`jQuery.migrateMute`). Quitarlo es una opción aparte, apagada, y nunca en el admin. |
| 6 | C) Dashicons, E) cart fragments | `wp_deregister_*`: si otro estilo/script depende de ellos, WordPress lo omite en silencio (mini-carrito, iconos). | `wp_dequeue_*`. |
| 7 | P) width/height | Usaba el tamaño del **original** para cualquier tamaño (miniatura 300×300 recibía 1200×800) → proporción y CLS mal. | Usa `wp_get_attachment_image_src( $id, $size )`. |
| 8 | J) Google Fonts | Filtro `clean_url` global: vaciaba cualquier enlace a Google Fonts en todo el sitio, también en el admin. No quitaba `<link>` escritos a mano. | Solo front: `style_loader_tag` + filtro HTML de `<link>` + quita preconnect de terceros. |
| 9 | `server-rules.txt` | `Header unset ETag` fuera de `IfModule` → **error 500** si falta mod_headers. `image/jpg` no existe. En Nginx el bloque CORS de fuentes nunca se aplicaba (gana la primera regex). `brotli on` tumba Nginx sin el módulo. | Reescrito. |

## Problemas de rendimiento

- **6 preconnect fijos en todas las páginas** (wa.me es solo un enlace, accounts.google.com casi no se usa, Facebook iba a retrasarse). Cada preconnect consume conexiones al inicio y retrasa el LCP → ahora son listas editables con valores mínimos.
- **Opción sin autoload** (`update_option(..., false)`): una consulta a la base de datos extra en cada visita → ahora autoload.
- `fsb_get_options()` se recalculaba decenas de veces por página → caché estática.
- `fertisem.js` de diagnóstico corría (con `console.warn`) para **todos** los visitantes y con versión fija `1.0.0` (el navegador seguía usando la copia vieja) → diagnóstico solo para admins y versión = fecha del archivo.

## Otros

- Versión distinta en readme (1.2.1) y plugin (1.3.1).
- 3 archivos PHP vacíos (`optimizations.php`, `performance.php`, `ucss.php`) y la lista blanca de CSS no se usaba.
- Meta robots duplicadas (WooCommerce y Yoast/Rank Math ya las ponen) → ahora vía `wp_robots`, solo búsquedas y solo sin plugin SEO.
- Aviso de Google Fonts en **todas** las pantallas del admin → eliminado; info en la página del plugin.
- Heartbeat se desregistraba en `init` → ahora dequeue en el front y 60 s en el admin (autosave sigue funcionando).
- Sin `uninstall.php`, sin enlace "Ajustes" en la lista de plugins.

## Nuevo en la 2.0.0

1. **Navegación instantánea (Speculation Rules)**: el navegador descarga la siguiente página al pasar el mouse por un enlace. Excluye carrito, checkout, cuenta, add-to-cart y cerrar sesión. Usa el núcleo en WP 6.8+.
2. **Medidor de contraseñas** (~400 KB) solo en cuenta y checkout.
3. **Limpieza de `<head>`** y oEmbed.
4. **CSS de Gutenberg** opcional (si todo se hace con Elementor).
5. **Modo prueba `?fsb_off=1`**: misma página sin optimizaciones, para comparar en PageSpeed o descartar que el plugin cause un error.
6. **Diagnóstico**: versión de WP/PHP, object cache, y aviso si hay otro plugin de rendimiento (WP Rocket, LiteSpeed...) que duplique funciones. Modo consola para admins con LCP, CLS, TTFB y errores JS.
7. Etiquetas en cada opción: seguro / probar en tu sitio / avanzado.

## Qué más acelera (fuera del plugin)

- Caché de página (LiteSpeed Cache o la del hosting) — el mayor salto en TTFB.
- Redis/Memcached (object cache) para WooCommerce.
- PHP 8.2+ en el hosting.
- Imagen héroe en WebP/AVIF al tamaño real y con la clase `lcp-hero`.
- `font-display: swap` en los `@font-face` de Poppins.
