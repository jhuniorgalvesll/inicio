<?php
/**
 * Limpieza de WordPress: emojis, <head>, oEmbed, Dashicons, Heartbeat, jQuery Migrate, CSS de bloques.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	/* Emojis */
	if ( fsb_enabled( 'emojis' ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
		} );
		add_filter( 'wp_resource_hints', function ( $urls, $type ) {
			if ( 'dns-prefetch' !== $type ) {
				return $urls;
			}
			return array_filter( $urls, function ( $u ) {
				return is_string( $u ) ? false === strpos( $u, 's.w.org' ) : true;
			} );
		}, 10, 2 );
	}

	/* Etiquetas inútiles del <head> */
	if ( fsb_enabled( 'head_cleanup' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	/* oEmbed host (wp-embed.js + links de descubrimiento) */
	if ( fsb_enabled( 'wp_embed' ) ) {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}
}, 1 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! fsb_is_frontend() ) {
		return;
	}

	if ( fsb_enabled( 'wp_embed' ) ) {
		wp_dequeue_script( 'wp-embed' );
	}

	// dequeue (no deregister): si otro estilo depende de dashicons, no lo rompemos.
	if ( fsb_enabled( 'dashicons' ) && ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}

	if ( fsb_enabled( 'heartbeat' ) && ! is_user_logged_in() ) {
		wp_dequeue_script( 'heartbeat' );
	}

	// CSS de Gutenberg: inútil si las páginas se hacen con Elementor.
	if ( fsb_enabled( 'block_css' ) && ! is_singular( 'post' ) ) {
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $h ) {
			fsb_dequeue_style( $h );
		}
	}
}, 100 );

// En el admin no se apaga Heartbeat (lo usan autosave y bloqueo de posts), solo se espacia.
add_filter( 'heartbeat_settings', function ( $settings ) {
	if ( fsb_enabled( 'heartbeat' ) ) {
		$settings['interval'] = 60;
	}
	return $settings;
} );

/* jQuery Migrate: silenciar avisos (seguro) o quitarlo (solo si sabes que nada lo necesita) */
add_action( 'wp_default_scripts', function ( $scripts ) {
	if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
		return;
	}

	if ( fsb_enabled( 'remove_jquery_migrate' ) ) {
		$scripts->registered['jquery']->deps = array_values(
			array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
		);
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( fsb_enabled( 'jquery_migrate_mute' ) && ! fsb_enabled( 'remove_jquery_migrate' ) ) {
		wp_add_inline_script( 'jquery-migrate', 'window.jQuery&&(jQuery.migrateMute=true);', 'before' );
	}
}, 1 );
