<?php
/**
 * Optimizaciones de WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! class_exists( 'WooCommerce' ) || ! fsb_is_frontend() ) {
		return;
	}

	$critical = fsb_is_wc_critical_page();

	/* Cart fragments: la petición AJAX ?wc-ajax=get_refreshed_fragments solo si hay carrito. */
	if ( fsb_enabled( 'cart_fragments_opt' ) && ! $critical ) {
		$cart_empty = empty( $_COOKIE['woocommerce_cart_hash'] ) && empty( $_COOKIE['woocommerce_items_in_cart'] );

		if ( $cart_empty ) {
			// dequeue (no deregister): deregistrar rompía en silencio los scripts que dependen de él (mini-carritos).
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}

	/* add-to-cart.js fuera de producto/carrito/checkout (rompe AJAX en listados: dejar apagado si se usa). */
	if ( fsb_enabled( 'dequeue_add_to_cart' ) && ! $critical && ! ( function_exists( 'is_product' ) && is_product() ) ) {
		wp_dequeue_script( 'wc-add-to-cart' );
	}

	/* Medidor de contraseñas (zxcvbn ≈ 400 KB) solo donde hay formularios de contraseña. */
	if ( fsb_enabled( 'password_meter' ) && ! $critical ) {
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_dequeue_script( 'password-strength-meter' );
		wp_dequeue_script( 'zxcvbn-async' );
	}

	/* Scripts de WooCommerce en páginas sin tienda (blog, nosotros, contacto...). Los CSS se respetan. */
	if ( fsb_enabled( 'wc_scripts_non_wc' ) && ! $critical && ! ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) && ! is_front_page() ) {
		foreach ( array( 'woocommerce', 'wc-add-to-cart', 'wc-cart-fragments', 'sourcebuster-js', 'wc-order-attribution' ) as $h ) {
			wp_dequeue_script( $h );
		}
	}
}, 100 );
