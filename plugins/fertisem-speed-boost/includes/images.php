<?php
/**
 * Imágenes: prioridad LCP y dimensiones correctas (evita CLS).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment, $size ) {
	if ( fsb_is_bypassed() ) {
		return $attr;
	}

	/* LCP: imágenes con la clase "lcp-hero" se cargan primero */
	if ( fsb_enabled( 'lcp_hero' ) && ! empty( $attr['class'] ) && false !== strpos( $attr['class'], 'lcp-hero' ) ) {
		$attr['fetchpriority'] = 'high';
		$attr['loading']       = 'eager';
		$attr['decoding']      = 'async';
	}

	/*
	 * Dimensiones faltantes. Bug 1.x: usaba el ancho/alto del ORIGINAL para cualquier tamaño
	 * (p.ej. 1200x800 en una miniatura 300x300), deformando la proporción.
	 */
	if ( fsb_enabled( 'fix_img_dims' ) && ( empty( $attr['width'] ) || empty( $attr['height'] ) ) && $attachment instanceof WP_Post ) {
		$src = wp_get_attachment_image_src( $attachment->ID, $size );
		if ( $src && ! empty( $src[1] ) && ! empty( $src[2] ) ) {
			$attr['width']  = (int) $src[1];
			$attr['height'] = (int) $src[2];
		}
	}

	return $attr;
}, 20, 3 );

/* La misma prioridad LCP para imágenes dentro del contenido (bloques / editor clásico). */
add_filter( 'wp_content_img_tag', function ( $html ) {
	if ( ! fsb_enabled( 'lcp_hero' ) || false === strpos( $html, 'lcp-hero' ) ) {
		return $html;
	}

	$html = preg_replace( '/\s(loading|fetchpriority)=(["\'])[^"\']*\2/i', '', $html );

	return preg_replace( '/<img\b/i', '<img fetchpriority="high" loading="eager"', $html, 1 );
} );
