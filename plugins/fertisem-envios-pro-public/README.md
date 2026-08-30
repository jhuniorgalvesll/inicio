# FERTISEM Envíos PRO — edición pública sanitizada

Versión pública de referencia del motor de envíos para WooCommerce.

## Qué se ocultó antes de publicar

- tarifas comerciales reales;
- asignaciones internas reales de distritos por zona;
- URLs operativas e imágenes alojadas en la tienda;
- historiales, pedidos y datos de clientes;
- importes reales de ajustes por volumen.

## Qué conserva

- normalización y alias de distritos;
- cálculo del peso total en kg;
- arquitectura Zona 1 / Zona 2 / Zona 3;
- rangos de peso y carga pesada;
- detección básica de envases líquidos y semillas;
- OLVA y Shalom como métodos de envío;
- filtros para que cada instalación configure sus propios valores.

## Filtros disponibles

- `fsep_public_aliases`
- `fsep_public_zone_districts`
- `fsep_public_tariff_matrix`
- `fsep_public_liquid_volume_fees`
- `fsep_public_seed_volume_fees`

## Importante

Los importes y zonas se publican vacíos o en cero deliberadamente. Esta edición no debe instalarse en producción sin configurar sus propias zonas y tarifas.

## Licencia

GPL-2.0-or-later.
