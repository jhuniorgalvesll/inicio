<?php
/**
 * Red: resource hints, bloqueo de Google Fonts, preloads, Speculation Rules y robots.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza una URL de opción. Rutas que empiezan con "/" se dejan relativas a la raíz
 * (funciona igual en el navegador y no se rompe si WordPress está en un subdirectorio).
 */
function fsb_normalize_url( $maybe_url ) {
	$maybe_url = trim( (string) $maybe_url );

	if ( '' === $maybe_url ) {
		return '';
	}
	if ( preg_match( '~^(https?:)?//~i', $maybe_url ) || 0 === strpos( $maybe_url, '/' ) ) {
		return esc_url( $maybe_url );
	}

	return '';
}

/* ---------- Resource hints (configurables; antes había 6 preconnect fijos en cada página) ---------- */
add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( ! fsb_is_frontend() ) {
		return $urls;
	}

	if ( fsb_enabled( 'block_google_fonts' ) ) {
		// Quita hints a Google Fonts que añadan el tema u otros plugins.
		$urls = array_filter( $urls, function ( $u ) {
			$href = is_array( $u ) ? ( isset( $u['href'] ) ? $u['href'] : '' ) : $u;
			return false === strpos( $href, 'fonts.googleapis.com' ) && false === strpos( $href, 'fonts.gstatic.com' );
		} );
	}

	if ( ! fsb_enabled( 'resource_hints' ) ) {
		return array_values( $urls );
	}

	if ( 'preconnect' === $relation_type ) {
		foreach ( fsb_lines( 'preconnect_urls' ) as $u ) {
			$u = fsb_normalize_url( $u );
			if ( ! $u ) {
				continue;
			}
			// Las fuentes se piden en modo CORS: el preconnect necesita crossorigin.
			$urls[] = ( false !== strpos( $u, 'fonts.gstatic.com' ) ) ? array( 'href' => $u, 'crossorigin' ) : $u;
		}
	} elseif ( 'dns-prefetch' === $relation_type ) {
		foreach ( fsb_lines( 'dns_prefetch_urls' ) as $u ) {
			$u = fsb_normalize_url( $u );
			if ( $u ) {
				$urls[] = $u;
			}
		}
	}

	return array_values( array_unique( $urls, SORT_REGULAR ) );
}, 20, 2 );

/* ---------- Bloquear Google Fonts remotas (solo front) ---------- */
add_action( 'wp', function () {
	if ( ! fsb_enabled( 'block_google_fonts' ) || ! fsb_is_frontend() ) {
		return;
	}

	add_filter( 'elementor/frontend/print_google_fonts', '__return_false', 100 );

	add_filter( 'style_loader_tag', function ( $html, $handle, $href ) {
		if ( fsb_is_google_fonts_url( $href ) ) {
			return '';
		}
		return $html;
	}, 10, 3 );
	// Nota: se eliminó el filtro global "clean_url" de la 1.x: vaciaba CUALQUIER enlace
	// a Google Fonts en todo el sitio (incluido el admin y el contenido).
} );

function fsb_is_google_fonts_url( $url ) {
	return is_string( $url ) && ( false !== strpos( $url, 'fonts.googleapis.com' ) || false !== strpos( $url, 'fonts.gstatic.com' ) );
}

/* ---------- Preload de imagen héroe y fuentes locales ---------- */
add_action( 'wp_head', function () {
	if ( ! fsb_is_frontend() || fsb_is_bypassed() ) {
		return;
	}

	$opts = fsb_get_options();

	if ( ! empty( $opts['preload_hero_enable'] ) && ! empty( $opts['preload_hero_url'] ) && is_front_page() ) {
		$url = fsb_normalize_url( $opts['preload_hero_url'] );
		if ( $url ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $url ) . '" fetchpriority="high">' . "\n";
		}
	}

	if ( ! empty( $opts['preload_fonts_enable'] ) ) {
		foreach ( array_slice( fsb_lines( 'preload_fonts_urls' ), 0, 4 ) as $line ) {
			$href = fsb_normalize_url( $line );
			$path = (string) wp_parse_url( $href, PHP_URL_PATH );

			if ( ! $href ) {
				continue;
			}
			if ( preg_match( '~\.woff2$~i', $path ) ) {
				$type = 'font/woff2';
			} elseif ( preg_match( '~\.woff$~i', $path ) ) {
				$type = 'font/woff';
			} else {
				continue;
			}

			echo '<link rel="preload" as="font" href="' . esc_url( $href ) . '" type="' . esc_attr( $type ) . '" crossorigin>' . "\n";
		}
	}
}, 1 );

/* ---------- Speculation Rules: precarga la página al pasar el mouse/tocar un enlace ---------- */

function fsb_speculation_exclude_paths() {
	$paths = array(
		'/wp-admin/*',
		'/wp-login.php*',
		'/*\\?*add-to-cart=*',
		'/*\\?*remove_item=*',
		'/*\\?*wc-ajax=*',
		'/*\\?*_wpnonce=*',
		'/*customer-logout*',
	);

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
			$path = wp_parse_url( wc_get_page_permalink( $page ), PHP_URL_PATH );
			$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
			if ( $path && '/' !== $path && trailingslashit( $path ) !== trailingslashit( $home ) ) {
				// Rutas relativas al home (WP core antepone el subdirectorio).
				$rel     = '/' . ltrim( substr( $path, strlen( untrailingslashit( $home ) ) ), '/' );
				$paths[] = trailingslashit( $rel ) . '*';
			}
		}
	}

	return apply_filters( 'fsb_speculation_exclude_paths', $paths );
}

// WordPress 6.8+: usamos la implementación del núcleo.
add_filter( 'wp_speculation_rules_configuration', function ( $config ) {
	if ( ! fsb_enabled( 'speculative' ) ) {
		return $config;
	}
	if ( null === $config ) {
		return $config; // El núcleo lo desactivó (usuario logueado, permalinks simples...).
	}
	$eager = in_array( fsb_opt( 'speculative_eagerness' ), array( 'conservative', 'moderate' ), true ) ? fsb_opt( 'speculative_eagerness' ) : 'moderate';

	return array( 'mode' => 'prefetch', 'eagerness' => $eager );
} );

add_filter( 'wp_speculation_rules_href_exclude_paths', function ( $paths ) {
	if ( ! fsb_enabled( 'speculative' ) ) {
		return $paths;
	}
	return array_merge( (array) $paths, fsb_speculation_exclude_paths() );
} );

// WordPress < 6.8: imprimimos las reglas nosotros.
add_action( 'wp_footer', function () {
	if ( function_exists( 'wp_get_speculation_rules_configuration' ) ) {
		return;
	}
	if ( ! fsb_enabled( 'speculative' ) || ! fsb_is_frontend() || is_user_logged_in() || ! get_option( 'permalink_structure' ) ) {
		return;
	}

	$home  = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	$not   = array();
	foreach ( fsb_speculation_exclude_paths() as $p ) {
		$not[] = array( 'href_matches' => $home . $p );
	}
	$not[] = array( 'selector_matches' => 'a[rel~=nofollow], .no-prefetch, .no-prefetch a, a[target=_blank]' );

	$eager = in_array( fsb_opt( 'speculative_eagerness' ), array( 'conservative', 'moderate' ), true ) ? fsb_opt( 'speculative_eagerness' ) : 'moderate';

	$rules = array(
		'prefetch' => array(
			array(
				'source'    => 'document',
				'where'     => array(
					'and' => array(
						array( 'href_matches' => $home . '/*' ),
						array( 'not' => array( 'or' => $not ) ),
					),
				),
				'eagerness' => $eager,
			),
		),
	);

	echo '<script type="speculationrules">' . wp_json_encode( $rules, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 20 );

/* ---------- Robots en búsquedas (WooCommerce ya marca carrito/checkout/cuenta como noindex) ---------- */
add_filter( 'wp_robots', function ( $robots ) {
	if ( ! fsb_enabled( 'seo_robots' ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
		return $robots;
	}
	if ( is_search() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );
