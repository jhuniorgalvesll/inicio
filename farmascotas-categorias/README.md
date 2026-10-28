# Farmascotas · Categorías destacadas (widget HTML para Elementor)

Bloque de 4 tarjetas de categorías (título + flecha y cuadrícula 2×2 de productos) con los colores oficiales de Farmascotas:

| Color | Uso |
|---|---|
| `#EA5D33` naranja | Acentos, flecha, borde superior de tarjeta, foco |
| `#024A8E` azul | Títulos |
| `#585856` gris | Textos y etiquetas |
| `#77B4E1` celeste | Fondos y detalles |

Solo HTML + CSS (sin JavaScript, sin CDN, sin Google Fonts). Las imágenes se cargan desde tu propia Biblioteca de medios de WordPress, no desde sitios externos.

## Archivos

- `widget-elementor.html` — el código a pegar en Elementor.
- `preview.html` — página completa para ver el bloque en el navegador sin WordPress (contiene una copia del widget que apunta a `imagenes/`).
- `imagenes/` — 16 imágenes de muestra de 600 × 600 px con el nombre exacto que espera el widget. Reemplázalas por tus fotos reales.

## Medidas de las imágenes

| Dato | Valor |
|---|---|
| Tamaño | **600 × 600 px** (cuadrada, 1:1) — las 16 iguales |
| Formato | WebP (recomendado) o JPG; PNG/WebP transparente si es producto recortado |
| Peso | máx. 80 KB por imagen |
| Composición | producto centrado, ~10 % de margen libre en cada borde (≈ 60 px) |
| Carpeta en WordPress | `/wp-content/uploads/farmascotas/categorias/` |

600 px cubre el tamaño más grande en pantalla (≈ 215 px) incluso en pantallas Retina (2×).

### Nombres de archivo

| Tarjeta | Producto | Archivo |
|---|---|---|
| Todo para tu perro | Alimento | `perros-alimento.webp` |
| | Snacks y premios | `perros-snacks.webp` |
| | Juguetes | `perros-juguetes.webp` |
| | Camas y descanso | `perros-camas.webp` |
| Farmacia veterinaria | Antipulgas | `farmacia-antipulgas.webp` |
| | Desparasitantes | `farmacia-desparasitantes.webp` |
| | Vitaminas y suplementos | `farmacia-vitaminas.webp` |
| | Cuidado dental | `farmacia-cuidado-dental.webp` |
| Mundo gatuno | Alimento | `gatos-alimento.webp` |
| | Arena y areneros | `gatos-arena.webp` |
| | Rascadores | `gatos-rascadores.webp` |
| | Juguetes | `gatos-juguetes.webp` |
| Accesorios | Collares y correas | `accesorios-collares.webp` |
| | Comederos y bebederos | `accesorios-comederos.webp` |
| | Transportadoras | `accesorios-transportadoras.webp` |
| | Ropa para mascotas | `accesorios-ropa.webp` |

### Foto con fondo o producto recortado

- **Foto con fondo** (lo normal): llena todo el recuadro (`object-fit: cover`). No hay que hacer nada.
- **Producto sin fondo** (PNG/WebP transparente): añade la clase `fmc-item__media--recorte` al `<span class="fmc-item__media">`. El producto se ve al 82 % sobre el fondo de color oficial de la tarjeta, con la franja diagonal, como en la referencia.

## Medidas del bloque (medidas reales en navegador)

| Ancho de pantalla | Columnas | Ancho tarjeta | Alto tarjeta | Imagen en pantalla |
|---|---|---|---|---|
| 1920 px | 4 | 365 px | 504 px | 156 × 156 px |
| 1440 px | 4 | 325 px | 483 px | 137 × 137 px |
| 1280 px | 4 | 285 px | 444 px | 119 × 119 px |
| 1100 px | 4 | 245 px | 403 px | 101 × 101 px |
| 1024 px (tablet horizontal) | 2 | 473 px | 576 px | 213 × 213 px |
| 768 px (tablet) | 2 | 354 px | 475 px | 156 × 156 px |
| 414 px (móvil) | carrusel | 334 px | 460 px | 146 × 146 px |
| 375 px (móvil) | carrusel | 301 px | 426 px | 131 × 131 px |
| 320 px (móvil pequeño) | carrusel | 254 px | 395 px | 107 × 107 px |

Otras medidas fijas:

- Ancho del bloque: 100 %; contenido centrado con máximo **1600 px**.
- Margen lateral: 12–40 px (fluido); relleno vertical: 16–40 px.
- Espacio entre tarjetas: 12–20 px. Relleno interno de tarjeta: 14–20 px.
- Esquinas: tarjeta 16 px, imagen 12 px. Borde superior naranja: 4 px.
- Botón flecha: 34 × 34 px (30 × 30 px en tarjetas estrechas).
- Títulos: bloque 22–32 px, tarjeta 17–21 px; etiquetas 13–15 px.

## Uso en Elementor

1. Sube las 16 fotos a la Biblioteca de medios dentro de `wp-content/uploads/farmascotas/categorias/` con los nombres de la tabla (por FTP o con un plugin de carpetas). Si las subes con el cargador normal, WordPress las guarda en `uploads/AAAA/MM/`: en ese caso copia la URL de cada imagen y pégala en su `src`.
2. Crea un **Contenedor** (o Sección) y en *Diseño* elige **Ancho del contenido: Ancho completo**; pon el relleno (padding) y el espacio entre widgets en `0`.
3. Arrastra un widget **HTML** y pega todo el contenido de `widget-elementor.html`.
4. Guarda y revisa en escritorio, tablet y móvil.

## Comportamiento responsive

- ≥ 1100 px: 4 tarjetas por fila.
- 561–1099 px: 2 tarjetas por fila.
- ≤ 560 px: carrusel horizontal deslizable (scroll-snap), una tarjeta y media visible.
- Container queries ajustan el espaciado interno según el ancho real de cada tarjeta.
- Respeta `prefers-reduced-motion`.

## Personalizar

- **Enlaces:** cambia los `href="/categoria-producto/..."` por las URLs de tus categorías de WooCommerce.
- **Imágenes:** cambia el `src` de cada `<img>`. Deja `width="600" height="600"` para evitar saltos de diseño al cargar.
- **Colores:** edita las variables `--fmc-*` al inicio de `.fmc-cats`.
- Todas las clases usan el prefijo `fmc-`, así no chocan con el tema ni con Elementor.
