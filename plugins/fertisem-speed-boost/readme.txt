=== Fertisem Speed Boost ===
Contributors: lliuya.pe
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 2.0.0
License: GPL-2.0-or-later

Acelera WordPress + WooCommerce + Elementor sin cambiar el diseño.

== Descripción ==
* Limpieza: emojis, <head>, oEmbed, Dashicons, Heartbeat, jQuery Migrate silenciado.
* Defer SEGURO con la API nativa de WordPress 6.3+ (respeta dependencias: sin errores "wp/_/moment is not defined").
* Retraso de widgets de terceros (Trustindex, Tawk, Clarity, Hotjar...) hasta la primera interacción.
* Navegación instantánea: Speculation Rules (precarga al pasar el mouse), excluye carrito/checkout/cuenta.
* WooCommerce: cart fragments condicional, medidor de contraseñas solo donde se usa, opciones avanzadas.
* LCP: clase lcp-hero, preload de imagen héroe y de fuentes WOFF2 locales.
* Bloqueo de Google Fonts remotas.
* Modo prueba: ?fsb_off=1 en cualquier URL. Modo diagnóstico en consola para administradores.

== Instalación ==
1. Plugins → Añadir nuevo → Subir plugin → selecciona el ZIP → Reemplazar la versión actual.
2. Ajustes → Fertisem Speed Boost. Revisa la sección "Diagnóstico".
3. Guarda y VACÍA LA CACHÉ (hosting / plugin de caché / CDN).
4. Prueba home, producto, carrito y checkout. Compara en PageSpeed con y sin ?fsb_off=1.
5. (Recomendado) Reglas de servidor: server-rules.txt.

== Notas ==
* Opciones con etiqueta "avanzado" vienen apagadas: actívalas una por una y prueba.
* Si un script se rompe con defer, añade su handle en "Handles extra a excluir".
* Si un widget retrasado no debe esperar, añade data-fsb-nodelay o quita su palabra clave.
* Si usas WP Rocket / LiteSpeed / Perfmatters, no dupliques defer/retraso de JS.

== Changelog ==
= 2.0.0 =
* Defer reescrito con la API de estrategias de WordPress (antes rompía el orden de scripts).
* El retraso de widgets ahora funciona (antes nunca ejecutaba los scripts).
* Nuevo: Speculation Rules, limpieza de <head>, medidor de contraseñas, resource hints configurables, modo ?fsb_off=1, diagnóstico.
* Corregido: deregister de dashicons/cart-fragments rompía dependencias; jQuery Migrate se eliminaba también en el admin; width/height erróneos en miniaturas; filtro clean_url vaciaba enlaces de Google Fonts en todo el sitio; meta robots duplicadas; aviso permanente en todo el admin; opción sin autoload (consulta extra por visita); reglas .htaccess que podían dar error 500.
* Eliminados archivos vacíos y los "stubs" falsos de wp/lodash/moment (ocultaban errores y rompían plugins).
