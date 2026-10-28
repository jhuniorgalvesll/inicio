# Farmascotas · Categorías destacadas (widget HTML para Elementor)

Bloque de 4 tarjetas de categorías (título + flecha y cuadrícula 2×2 de productos) con los colores oficiales de Farmascotas:

| Color | Uso |
|---|---|
| `#EA5D33` naranja | Acentos, flecha, borde superior de tarjeta, foco |
| `#024A8E` azul | Títulos |
| `#585856` gris | Textos y etiquetas |
| `#77B4E1` celeste | Fondos y detalles |

Solo HTML + CSS (sin JavaScript, sin CDN, sin Google Fonts, sin imágenes remotas). Las ilustraciones son SVG en línea.

## Archivos

- `widget-elementor.html` — el código a pegar en Elementor.
- `preview.html` — página completa para ver el bloque en el navegador sin WordPress (contiene una copia del widget).

## Uso en Elementor

1. Crea un **Contenedor** (o Sección) y en *Diseño* elige **Ancho del contenido: Ancho completo**; pon el relleno (padding) y el espacio entre widgets en `0`.
2. Arrastra un widget **HTML** y pega todo el contenido de `widget-elementor.html`.
3. Guarda y revisa en escritorio, tablet y móvil.

## Comportamiento responsive

- ≥ 1100 px: 4 tarjetas por fila.
- 561–1099 px: 2 tarjetas por fila.
- ≤ 560 px: carrusel horizontal deslizable (scroll-snap), una tarjeta y media visible.
- Container queries ajustan el espaciado interno según el ancho real de cada tarjeta.
- Respeta `prefers-reduced-motion`.

## Personalizar

- **Enlaces:** cambia los `href="/categoria-producto/..."` por las URLs de tus categorías de WooCommerce.
- **Imágenes:** reemplaza el `<svg>...</svg>` dentro de `.fmc-item__media` por
  `<img src="/wp-content/uploads/tu-imagen.webp" alt="" loading="lazy">` de tu biblioteca de medios (se ajusta solo).
- **Colores:** edita las variables `--fmc-*` al inicio de `.fmc-cats`.
- Todas las clases usan el prefijo `fmc-`, así no chocan con el tema ni con Elementor.
