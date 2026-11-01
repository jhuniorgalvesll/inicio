<?php
/**
 * Lista blanca de CSS: estilos que el plugin NUNCA debe quitar.
 * (En la 1.x esta lista existía pero no se usaba en ningún lado.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fertisem_speed_boost_get_css_whitelist() {
	return apply_filters( 'fsb_css_whitelist', array(
		'elementor-frontend',
		'elementor-pro',
		'elementskit-styles',
		'widget-styles',
		'widget-styles-pro',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'woocommerce-general',
	) );
}

/**
 * Único punto por donde el plugin quita CSS: respeta la lista blanca.
 */
function fsb_dequeue_style( $handle ) {
	if ( in_array( $handle, fertisem_speed_boost_get_css_whitelist(), true ) ) {
		return false;
	}
	wp_dequeue_style( $handle );
	return true;
}
