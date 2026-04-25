# E-commerce Header Suite

Plugin de WordPress que añade un encabezado moderno pensado para tiendas en línea. Incluye barra superior con mensaje promocional, teléfono, enlaces rápidos, logotipo, buscador, CTA, carrito, menú adaptable y widget de WhatsApp multiagente.

## Características

- Registro de ubicación de menú específica para el encabezado.
- Panel de ajustes en **Apariencia → Encabezado E-commerce**.
- Maquetación alineada al mockup: barra superior utilitaria, barra principal comercial, menú y franja de badges.
- Datos base ya preconfigurados (promociones, mayoristas, blog, contacto y diferenciales).
- Integración con WooCommerce para mostrar cantidad de productos en carrito y actualización vía AJAX.
- Botón flotante de WhatsApp configurable con múltiples personas de contacto.

## Instalación

1. Copia la carpeta `ecommerce-header` dentro de `wp-content/plugins/`.
2. Activa **E-commerce Header Suite** desde el panel de *Plugins*.
3. Ve a **Apariencia → Encabezado E-commerce** para revisar/editar los datos cargados.
4. Asigna un menú al espacio **Menú encabezado e-commerce** en **Apariencia → Menús**.

## Campos nuevos orientados al mockup

- **Etiqueta de contacto + teléfono** en la barra superior.
- **Enlaces rápidos superiores** (una línea por enlace: `Etiqueta|URL`).
- **Badges del mockup** (una línea por badge para la franja inferior).

## Hooks personalizados

- **Evento JS** `echp-refresh-cart`: permite refrescar la burbuja del carrito desde scripts personalizados.

## Compatibilidad

- Compatible con WooCommerce.
- Fallback para temas sin `wp_body_open`, inyectando el encabezado antes del pie de página.

## Desarrollo

- Estilos: `assets/css/header.css`
- Scripts: `assets/js/header.js`
- Lógica principal y settings: `ecommerce-header.php`
