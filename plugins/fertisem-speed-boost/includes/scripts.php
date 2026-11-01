<?php
/**
 * Defer seguro de JavaScript usando la API nativa de WordPress 6.3+ (strategy).
 *
 * Por qué cambió: la versión 1.x hacía str_replace(' src', ' defer src') sobre toda la etiqueta.
 * Eso diferia scripts que tenían código inline "after" o dependientes no diferidos, y así
 * aparecían errores tipo "wp is not defined", "_ is not defined", "moment is not defined".
 * La API nativa revisa dependencias e inline scripts y, si no es seguro, deja el script normal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fsb_defer_excluded_handles() {
	$handles = array(
		'jquery', 'jquery-core', 'jquery-migrate', 'wp-polyfill', 'regenerator-runtime',
		'react', 'react-dom', 'react-jsx-runtime', 'underscore', 'lodash', 'moment', 'backbone',
		'woocommerce', 'wc-add-to-cart', 'wc-cart-fragments', 'wc-checkout', 'wc-cart',
		'wc-add-to-cart-variation', 'wc-single-product', 'wc-country-select', 'wc-address-i18n',
		'jquery-blockui', 'js-cookie', 'selectWoo', 'select2',
		'elementor-frontend', 'elementor-frontend-modules', 'elementor-webpack-runtime',
		'elementor-pro-frontend', 'elementor-pro-webpack-runtime', 'elementor-sticky', 'pro-elements-handlers',
		'jetpack', 'googlesitekit', 'contact-form-7', 'wpforms', 'ninja-forms', 'acf',
	);

	$extra = array_map( 'trim', explode( ',', str_replace( array( "\r", "\n" ), ',', (string) fsb_opt( 'defer_exclude' ) ) ) );

	return apply_filters( 'fsb_defer_excluded_handles', array_filter( array_merge( $handles, $extra ) ) );
}

function fsb_defer_is_excluded( $handle, $src ) {
	if ( in_array( $handle, fsb_defer_excluded_handles(), true ) ) {
		return true;
	}

	foreach ( array( 'wp-', 'e-', 'woocommerce-blocks', 'wc-blocks', 'wc-settings', 'elementor' ) as $prefix ) {
		if ( 0 === strpos( $handle, $prefix ) ) {
			return true;
		}
	}

	// Paquetes de Gutenberg (wp.i18n, wp.hooks...) y jQuery UI: los usa código inline del contenido.
	if ( is_string( $src ) && ( false !== strpos( $src, '/wp-includes/js/dist/' ) || false !== strpos( $src, '/wp-includes/js/jquery/' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Marca como "defer" todos los scripts registrados que sean candidatos.
 * Se ejecuta justo antes de imprimir los scripts del <head> y de nuevo antes del footer.
 */
function fsb_apply_defer_strategy() {
	if ( ! fsb_enabled( 'defer_js' ) || ! fsb_is_frontend() ) {
		return;
	}

	$wp_scripts = wp_scripts();

	// WordPress < 6.3 no tiene la API segura: no hacemos nada antes que arriesgar errores.
	if ( ! method_exists( $wp_scripts, 'get_data' ) || ! method_exists( 'WP_Scripts', 'get_eligible_loading_strategy' ) ) {
		return;
	}

	foreach ( $wp_scripts->registered as $handle => $dep ) {
		if ( empty( $dep->src ) || in_array( $handle, $wp_scripts->done, true ) ) {
			continue;
		}
		if ( $wp_scripts->get_data( $handle, 'strategy' ) ) {
			continue; // Ya tiene async/defer propio.
		}
		if ( fsb_defer_is_excluded( $handle, $dep->src ) ) {
			continue;
		}
		$wp_scripts->add_data( $handle, 'strategy', 'defer' );
	}
}
add_action( 'wp_print_scripts', 'fsb_apply_defer_strategy', 1 );
add_action( 'wp_print_footer_scripts', 'fsb_apply_defer_strategy', 1 );

/**
 * JS propio del child theme (assets/js/fertisem.js).
 * Versión = fecha de modificación del archivo, así el navegador no usa una copia vieja.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! fsb_enabled( 'theme_js' ) ) {
		return;
	}

	$file = get_stylesheet_directory() . '/assets/js/fertisem.js';

	if ( ! file_exists( $file ) ) {
		return;
	}

	wp_enqueue_script(
		'fertisem-custom',
		get_stylesheet_directory_uri() . '/assets/js/fertisem.js',
		array( 'jquery' ),
		(string) filemtime( $file ),
		true
	);
}, 50 );

/**
 * Diagnóstico de consola: solo para administradores con la opción "debug" activa.
 * (Antes este código de diagnóstico corría para TODOS los visitantes.)
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! fsb_enabled( 'debug' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_script( 'fsb-debug', FSB_URL . 'assets/js/fsb-debug.js', array(), FSB_VERSION, true );
}, 60 );
