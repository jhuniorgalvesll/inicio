<?php
/**
 * Plugin Name:       Fertisem Speed Boost
 * Description:       Acelera WordPress + WooCommerce + Elementor sin cambiar el diseño: limpieza de bloat, defer seguro (API nativa de WP), retraso de widgets externos hasta la interacción, precarga instantánea de páginas (Speculation Rules), optimización de WooCommerce, LCP, fuentes locales y bloqueo de Google Fonts.
 * Author:            lliuya.pe
 * Author URI:        https://instagram.com/lliuya.pe
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * License:           GPL-2.0-or-later
 * Text Domain:       fertisem-speed-boost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FSB_VERSION', '2.0.0' );
define( 'FSB_OPTION_KEY', 'fsb_options' );
define( 'FSB_FILE', __FILE__ );
define( 'FSB_DIR', plugin_dir_path( __FILE__ ) );
define( 'FSB_URL', plugin_dir_url( __FILE__ ) );

/* ================= OPCIONES ================= */

/**
 * Valores por defecto. Las opciones "riesgosas" vienen desactivadas.
 */
function fsb_default_options() {
	return array(
		// WordPress.
		'emojis'               => 1,
		'head_cleanup'         => 1,
		'wp_embed'             => 1,
		'dashicons'            => 1,
		'heartbeat'            => 1,
		'jquery_migrate_mute'  => 1,
		'remove_jquery_migrate'=> 0,
		'block_css'            => 0,

		// JavaScript.
		'defer_js'             => 1,
		'defer_exclude'        => '',
		'delay_js'             => 1,
		'delay_js_keywords'    => "trustindex.io\ntrustpilot.com\nembed.tawk.to\nclarity.ms\nhotjar.com\nclient.crisp.chat\ncode.tidio.co",
		'delay_js_timeout'     => 5,

		// Navegación instantánea.
		'speculative'          => 1,
		'speculative_eagerness'=> 'moderate',

		// WooCommerce.
		'cart_fragments_opt'   => 1,
		'dequeue_add_to_cart'  => 0,
		'password_meter'       => 1,
		'wc_scripts_non_wc'    => 0,

		// Red.
		'resource_hints'       => 1,
		'preconnect_urls'      => "https://www.googletagmanager.com",
		'dns_prefetch_urls'    => "//connect.facebook.net\n//cdn.trustindex.io",
		'block_google_fonts'   => 1,

		// LCP / imágenes.
		'lcp_hero'             => 1,
		'fix_img_dims'         => 1,
		'preload_hero_enable'  => 0,
		'preload_hero_url'     => '',

		// Fuentes locales.
		'preload_fonts_enable' => 1,
		'preload_fonts_urls'   => "/wp-content/uploads/fertisem-assets/fonts/Poppins-Regular.woff2\n/wp-content/uploads/fertisem-assets/fonts/Poppins-SemiBold.woff2\n/wp-content/uploads/fertisem-assets/fonts/Poppins-Bold.woff2",

		// Otros.
		'seo_robots'           => 1,
		'theme_js'             => 1,
		'debug'                => 0,
	);
}

function fsb_bool_keys() {
	return array(
		'emojis', 'head_cleanup', 'wp_embed', 'dashicons', 'heartbeat', 'jquery_migrate_mute',
		'remove_jquery_migrate', 'block_css', 'defer_js', 'delay_js', 'speculative',
		'cart_fragments_opt', 'dequeue_add_to_cart', 'password_meter', 'wc_scripts_non_wc',
		'resource_hints', 'block_google_fonts', 'lcp_hero', 'fix_img_dims', 'preload_hero_enable',
		'preload_fonts_enable', 'seo_robots', 'theme_js', 'debug',
	);
}

/**
 * Opciones con caché estática (antes se recalculaba en cada llamada).
 */
function fsb_get_options( $refresh = false ) {
	static $cache = null;

	if ( null === $cache || $refresh ) {
		$stored = get_option( FSB_OPTION_KEY, array() );
		$cache  = wp_parse_args( is_array( $stored ) ? $stored : array(), fsb_default_options() );
	}

	return $cache;
}

function fsb_opt( $key ) {
	$opts = fsb_get_options();
	return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
}

function fsb_enabled( $key ) {
	return ! empty( fsb_get_options()[ $key ] ) && ! fsb_is_bypassed();
}

/**
 * Lista de líneas de un textarea (sin vacíos).
 */
function fsb_lines( $key ) {
	$text = (string) fsb_opt( $key );
	return array_values( array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $text ) ) ) );
}

/**
 * Modo prueba: añade ?fsb_off=1 a cualquier URL para ver la página SIN optimizaciones.
 * Útil para comparar o descartar que el plugin cause un error.
 */
function fsb_is_bypassed() {
	static $bypass = null;

	if ( null === $bypass ) {
		$bypass = isset( $_GET['fsb_off'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	return $bypass;
}

/**
 * ¿Es una petición de front-end "normal" (HTML para visitantes)?
 */
function fsb_is_frontend() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
		return false;
	}
	// Editor/preview de Elementor.
	if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return false;
	}
	return true;
}

function fsb_is_wc_critical_page() {
	return ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );
}

/* ============== ACTIVACIÓN / ACTUALIZACIÓN ============== */

register_activation_hook( __FILE__, 'fsb_install' );

function fsb_install() {
	$stored = get_option( FSB_OPTION_KEY );
	$merged = wp_parse_args( is_array( $stored ) ? $stored : array(), fsb_default_options() );

	if ( false === $stored ) {
		// autoload = yes: se lee en cada visita, así evitamos una consulta extra.
		add_option( FSB_OPTION_KEY, $merged, '', 'yes' );
	} else {
		update_option( FSB_OPTION_KEY, $merged );
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( FSB_OPTION_KEY, true );
		}
	}

	update_option( 'fsb_db_version', FSB_VERSION );
}

// Migración automática al actualizar el ZIP sin desactivar/activar.
add_action( 'admin_init', function () {
	if ( get_option( 'fsb_db_version' ) !== FSB_VERSION ) {
		fsb_install();
	}
} );

/* ============== MÓDULOS ============== */

require_once FSB_DIR . 'includes/admin.php';
require_once FSB_DIR . 'includes/cleanup.php';
require_once FSB_DIR . 'includes/scripts.php';
require_once FSB_DIR . 'includes/woocommerce.php';
require_once FSB_DIR . 'includes/network.php';
require_once FSB_DIR . 'includes/images.php';
require_once FSB_DIR . 'includes/delay.php';
require_once FSB_DIR . 'includes/css-optimizer.php';
