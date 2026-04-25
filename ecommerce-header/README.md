# E-commerce Header Suite

Plugin de WordPress que añade un encabezado moderno pensado para tiendas en línea. Incluye barra superior con mensaje promocional, logotipo, buscador, botón principal configurable, enlace a la cuenta, resumen de carrito y menú adaptable con control móvil.

## Características

- Registro de ubicación de menú específica para el encabezado.
- Panel de ajustes bajo **Apariencia → Encabezado E-commerce** para personalizar logo, CTA, mensaje informativo y visibilidad de cada elemento.
- Estilos responsive listos para dispositivos móviles.
- Botón hamburguesa con JavaScript ligero para mostrar/ocultar el menú en pantallas pequeñas.
- Integración con WooCommerce para mostrar la cantidad de productos en el carrito y actualizarla tras añadir productos mediante AJAX.
- Botón flotante de WhatsApp configurable con múltiples personas de contacto para Fertisem.

## Instalación

1. Copia la carpeta `ecommerce-header` dentro de `wp-content/plugins/`.
2. Activa **E-commerce Header Suite** desde el panel de *Plugins* en WordPress.
3. Dirígete a **Apariencia → Encabezado E-commerce** para personalizar los elementos.
4. Asigna un menú al nuevo espacio **Menú encabezado e-commerce** en **Apariencia → Menús**.

## Personalización rápida

- **Logo**: introduce la URL de la imagen (puedes usar la biblioteca de medios de WordPress).
- **Botón principal**: establece el texto y la URL que apuntará a tus promociones o categorías más rentables.
- **Mensaje superior**: comparte beneficios de envío, horarios o contacto.
- **Elementos opcionales**: activa o desactiva buscador, enlace de cuenta y resumen de carrito según las necesidades de tu tienda.
- **WhatsApp flotante**: define el título, la descripción, la posición del botón y una lista de personas con su teléfono y mensaje personalizado.

## Hooks personalizados

- **Evento JS** `echp-refresh-cart`: lanza este evento en `document` para forzar la actualización de la burbuja del carrito desde código personalizado.

## Compatibilidad

- Compatible con WooCommerce.
- Fallback para temas sin `wp_body_open`, inyectando el encabezado antes del pie de página.
- Incluye estilos y scripts autónomos para evitar conflictos.

## Desarrollo

Los estilos principales se encuentran en `assets/css/header.css` y el comportamiento en `assets/js/header.js`. Puedes ejecutar `npm` o tu preprocesador favorito para ampliar o minificar estos archivos.
