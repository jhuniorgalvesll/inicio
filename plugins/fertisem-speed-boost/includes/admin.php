<?php
/**
 * Panel de ajustes: Ajustes → Fertisem Speed Boost.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_options_page( 'Fertisem Speed Boost', 'Fertisem Speed Boost', 'manage_options', 'fertisem-speed-boost', 'fsb_render_options_page' );
} );

add_filter( 'plugin_action_links_' . plugin_basename( FSB_FILE ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=fertisem-speed-boost' ) ) . '">Ajustes</a>' );
	return $links;
} );

add_action( 'admin_init', function () {
	register_setting( 'fsb_options_group', FSB_OPTION_KEY, array(
		'type'              => 'array',
		'sanitize_callback' => 'fsb_sanitize_options',
		'default'           => fsb_default_options(),
	) );
} );

function fsb_sanitize_textarea_urls( $text, $pattern = null ) {
	$lines = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $text ) ) );
	$safe  = array();

	foreach ( $lines as $line ) {
		if ( ! fsb_normalize_url( $line ) ) {
			continue;
		}
		if ( $pattern && ! preg_match( $pattern, (string) wp_parse_url( $line, PHP_URL_PATH ) ) ) {
			continue;
		}
		$safe[] = $line;
	}

	return implode( "\n", array_unique( $safe ) );
}

function fsb_sanitize_options( $input ) {
	$input = is_array( $input ) ? wp_unslash( $input ) : array();
	$clean = array();

	foreach ( fsb_bool_keys() as $k ) {
		$clean[ $k ] = empty( $input[ $k ] ) ? 0 : 1;
	}

	$hero                      = isset( $input['preload_hero_url'] ) ? trim( $input['preload_hero_url'] ) : '';
	$clean['preload_hero_url'] = fsb_normalize_url( $hero ) ? esc_url_raw( $hero ) : '';

	$clean['preload_fonts_urls'] = fsb_sanitize_textarea_urls( isset( $input['preload_fonts_urls'] ) ? $input['preload_fonts_urls'] : '', '~\.woff2?$~i' );
	$clean['preconnect_urls']    = fsb_sanitize_textarea_urls( isset( $input['preconnect_urls'] ) ? $input['preconnect_urls'] : '' );
	$clean['dns_prefetch_urls']  = fsb_sanitize_textarea_urls( isset( $input['dns_prefetch_urls'] ) ? $input['dns_prefetch_urls'] : '' );

	$kw = array_filter( array_map( 'sanitize_text_field', preg_split( "/\r\n|\r|\n/", isset( $input['delay_js_keywords'] ) ? (string) $input['delay_js_keywords'] : '' ) ) );
	$clean['delay_js_keywords'] = implode( "\n", array_unique( $kw ) );

	$clean['delay_js_timeout'] = isset( $input['delay_js_timeout'] ) ? min( 30, max( 0, (int) $input['delay_js_timeout'] ) ) : 5;

	$handles                = preg_split( '/[\s,]+/', isset( $input['defer_exclude'] ) ? (string) $input['defer_exclude'] : '' );
	$clean['defer_exclude'] = implode( ', ', array_filter( array_map( 'sanitize_key', $handles ) ) );

	$eager                          = isset( $input['speculative_eagerness'] ) ? $input['speculative_eagerness'] : 'moderate';
	$clean['speculative_eagerness'] = in_array( $eager, array( 'conservative', 'moderate' ), true ) ? $eager : 'moderate';

	return array_merge( fsb_default_options(), $clean );
}

/* ---------- Campos ---------- */
function fsb_field_name( $key ) {
	return esc_attr( FSB_OPTION_KEY ) . '[' . esc_attr( $key ) . ']';
}

function fsb_checkbox( $key, $label, $badge = '' ) {
	$badges = array(
		'safe'  => '<span style="color:#008a20;font-size:11px;"> ● seguro</span>',
		'test'  => '<span style="color:#b26200;font-size:11px;"> ● probar en tu sitio</span>',
		'risky' => '<span style="color:#d63638;font-size:11px;"> ● avanzado</span>',
	);
	printf(
		'<p><label><input type="checkbox" name="%s" value="1" %s> %s</label>%s</p>',
		fsb_field_name( $key ),
		checked( ! empty( fsb_get_options()[ $key ] ), true, false ),
		wp_kses_post( $label ),
		isset( $badges[ $badge ] ) ? $badges[ $badge ] : ''
	);
}

function fsb_textarea( $key, $rows = 4 ) {
	printf(
		'<textarea class="large-text code" rows="%d" name="%s">%s</textarea>',
		(int) $rows,
		fsb_field_name( $key ),
		esc_textarea( (string) fsb_opt( $key ) )
	);
}

function fsb_desc( $html ) {
	echo '<p class="description">' . wp_kses_post( $html ) . '</p>';
}

/* ---------- Diagnóstico de entorno ---------- */
function fsb_environment_checks() {
	global $wp_version;
	$checks = array();

	$checks[] = array(
		version_compare( $wp_version, '6.3', '>=' ),
		'WordPress ' . $wp_version . ( version_compare( $wp_version, '6.3', '>=' ) ? ' — defer seguro disponible.' : ' — actualiza a 6.3+ para que funcione el defer seguro.' ),
	);
	$checks[] = array(
		true,
		function_exists( 'wp_get_speculation_rules_configuration' ) ? 'Speculation Rules del núcleo (WP 6.8+).' : 'Speculation Rules: se usará la versión propia del plugin.',
	);
	$checks[] = array( version_compare( PHP_VERSION, '8.1', '>=' ), 'PHP ' . PHP_VERSION . ( version_compare( PHP_VERSION, '8.1', '>=' ) ? '' : ' — pide a tu hosting PHP 8.1+ (más rápido).' ) );
	$checks[] = array( wp_using_ext_object_cache(), wp_using_ext_object_cache() ? 'Object cache persistente activo.' : 'Sin object cache persistente (Redis/Memcached): recomendado para WooCommerce.' );

	$conflicts = array(
		'WP_ROCKET_VERSION'         => 'WP Rocket',
		'LSCWP_V'                   => 'LiteSpeed Cache',
		'AUTOPTIMIZE_PLUGIN_VERSION'=> 'Autoptimize',
		'PERFMATTERS_VERSION'       => 'Perfmatters',
		'W3TC'                      => 'W3 Total Cache',
		'WPFC_WP_PLUGIN_DIR'        => 'WP Fastest Cache',
		'FLYING_PRESS_VERSION'      => 'FlyingPress',
	);
	foreach ( $conflicts as $const => $name ) {
		if ( defined( $const ) ) {
			$checks[] = array( false, $name . ' está activo: no actives en ambos el defer/retraso de JS ni la limpieza de CSS (se pisan).' );
		}
	}

	return $checks;
}

function fsb_render_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$sample = add_query_arg( 'fsb_off', '1', home_url( '/' ) );
	?>
	<div class="wrap">
		<h1>Fertisem Speed Boost <small style="font-weight:normal;font-size:12px;">v<?php echo esc_html( FSB_VERSION ); ?></small></h1>

		<div class="notice notice-info inline"><p>
			<strong>Modo prueba:</strong> abre cualquier página con <code>?fsb_off=1</code> (ej. <a href="<?php echo esc_url( $sample ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sample ); ?></a>) para verla SIN optimizaciones y comparar en PageSpeed o detectar si algo se rompe por el plugin.
			<br>Después de guardar cambios, <strong>vacía la caché</strong> de tu hosting/plugin de caché.
		</p></div>

		<h2>Diagnóstico</h2>
		<ul style="margin-left:1em;">
			<?php foreach ( fsb_environment_checks() as $c ) : ?>
				<li><?php echo $c[0] ? '✅' : '⚠️'; ?> <?php echo esc_html( $c[1] ); ?></li>
			<?php endforeach; ?>
		</ul>

		<form method="post" action="options.php">
			<?php settings_fields( 'fsb_options_group' ); ?>
			<table class="form-table"><tbody>

			<tr><th>WordPress</th><td>
				<?php
				fsb_checkbox( 'emojis', 'Quitar emojis (JS/CSS y dns-prefetch a s.w.org)', 'safe' );
				fsb_checkbox( 'head_cleanup', 'Limpiar <code>&lt;head&gt;</code> (RSD, wlwmanifest, generator, shortlink)', 'safe' );
				fsb_checkbox( 'wp_embed', 'Quitar <code>wp-embed.js</code> y links oEmbed (tus vídeos de YouTube siguen funcionando)', 'safe' );
				fsb_checkbox( 'dashicons', 'No cargar Dashicons a visitantes', 'safe' );
				fsb_checkbox( 'heartbeat', 'Heartbeat: apagado para visitantes y cada 60 s en el admin', 'safe' );
				fsb_checkbox( 'jquery_migrate_mute', 'Silenciar avisos de jQuery Migrate en consola', 'safe' );
				fsb_checkbox( 'remove_jquery_migrate', 'Quitar jQuery Migrate por completo (solo si nada antiguo lo necesita)', 'risky' );
				fsb_checkbox( 'block_css', 'Quitar CSS de Gutenberg en páginas (útil si todo está hecho con Elementor; se mantiene en entradas del blog)', 'test' );
				?>
			</td></tr>

			<tr><th>JavaScript</th><td>
				<?php
				fsb_checkbox( 'defer_js', 'Defer seguro con la API nativa de WordPress (respeta dependencias y scripts inline)', 'safe' );
				fsb_desc( 'Excluidos siempre: jQuery, WooCommerce, Elementor, paquetes <code>wp-*</code>, Site Kit. Handles extra a excluir (separados por coma):' );
				fsb_textarea( 'defer_exclude', 2 );
				echo '<hr>';
				fsb_checkbox( 'delay_js', 'Retrasar widgets de terceros hasta que el visitante interactúe (scroll, toque, tecla)', 'test' );
				fsb_desc( 'Una palabra clave por línea: se retrasa cualquier <code>&lt;script&gt;</code> cuyo src o código la contenga. No pongas aquí Analytics/Píxel si necesitas medir rebotes. Para excluir un script añade el atributo <code>data-fsb-nodelay</code>.' );
				fsb_textarea( 'delay_js_keywords', 6 );
				?>
				<p><label>Cargar igualmente a los <input type="number" min="0" max="30" class="small-text" name="<?php echo fsb_field_name( 'delay_js_timeout' ); // phpcs:ignore ?>" value="<?php echo esc_attr( (int) fsb_opt( 'delay_js_timeout' ) ); ?>"> segundos después de cargar la página (0 = solo con interacción).</label></p>
			</td></tr>

			<tr><th>Navegación instantánea</th><td>
				<?php fsb_checkbox( 'speculative', 'Precargar la siguiente página cuando el visitante va a hacer clic (Speculation Rules)', 'safe' ); ?>
				<p><select name="<?php echo fsb_field_name( 'speculative_eagerness' ); // phpcs:ignore ?>">
					<option value="moderate" <?php selected( fsb_opt( 'speculative_eagerness' ), 'moderate' ); ?>>Moderado — al pasar el mouse ~200 ms (recomendado)</option>
					<option value="conservative" <?php selected( fsb_opt( 'speculative_eagerness' ), 'conservative' ); ?>>Conservador — al presionar el enlace</option>
				</select></p>
				<?php fsb_desc( 'Nunca precarga carrito, checkout, mi cuenta, add-to-cart ni cerrar sesión. Solo visitantes no logueados.' ); ?>
			</td></tr>

			<tr><th>WooCommerce</th><td>
				<?php
				fsb_checkbox( 'cart_fragments_opt', 'Sin <code>wc-cart-fragments</code> (petición AJAX) si el carrito está vacío, fuera de carrito/checkout/cuenta', 'safe' );
				fsb_checkbox( 'password_meter', 'Medidor de contraseñas (~400 KB) solo en cuenta y checkout', 'safe' );
				fsb_checkbox( 'dequeue_add_to_cart', 'Quitar <code>wc-add-to-cart</code> fuera de producto (rompe "añadir al carrito" AJAX en listados)', 'risky' );
				fsb_checkbox( 'wc_scripts_non_wc', 'Quitar JS de WooCommerce en páginas sin tienda (blog, nosotros...). Nunca en la home', 'test' );
				?>
			</td></tr>

			<tr><th>Red de recursos</th><td>
				<?php
				fsb_checkbox( 'resource_hints', 'Añadir preconnect / dns-prefetch', 'safe' );
				fsb_desc( '<strong>Preconnect</strong> (máx. 2–3, solo dominios que se usan al inicio de la carga):' );
				fsb_textarea( 'preconnect_urls', 3 );
				fsb_desc( '<strong>DNS-prefetch</strong> (barato, para dominios que se usan más tarde):' );
				fsb_textarea( 'dns_prefetch_urls', 3 );
				echo '<hr>';
				fsb_checkbox( 'block_google_fonts', 'Bloquear Google Fonts remotas (Fertisem usa Poppins local WOFF2)', 'safe' );
				?>
			</td></tr>

			<tr><th>LCP / Imágenes</th><td>
				<?php
				fsb_checkbox( 'lcp_hero', 'Prioridad alta a imágenes con la clase <code>lcp-hero</code>', 'safe' );
				fsb_checkbox( 'fix_img_dims', 'Completar width/height faltantes con el tamaño correcto (evita CLS)', 'safe' );
				fsb_checkbox( 'preload_hero_enable', 'Preload de imagen héroe (solo home)', 'test' );
				?>
				<p><input type="text" class="large-text" name="<?php echo fsb_field_name( 'preload_hero_url' ); // phpcs:ignore ?>" value="<?php echo esc_attr( (string) fsb_opt( 'preload_hero_url' ) ); ?>" placeholder="/wp-content/uploads/2026/01/banner-home.webp"></p>
				<?php fsb_desc( 'Usa la URL EXACTA que muestra PageSpeed como elemento LCP (si es distinta, se descarga dos veces). WebP/AVIF al tamaño real.' ); ?>
			</td></tr>

			<tr><th>Fuentes locales</th><td>
				<?php
				fsb_checkbox( 'preload_fonts_enable', 'Preload de fuentes críticas (.woff2 / .woff, máximo 4)', 'safe' );
				fsb_textarea( 'preload_fonts_urls', 4 );
				fsb_desc( 'Una URL por línea. Solo los pesos que se ven arriba de la página (Regular, SemiBold, Bold). En tu @font-face usa <code>font-display: swap</code>.' );
				?>
			</td></tr>

			<tr><th>Otros</th><td>
				<?php
				fsb_checkbox( 'seo_robots', 'noindex en resultados de búsqueda (se omite si hay Yoast / Rank Math / SEOPress)', 'safe' );
				fsb_checkbox( 'theme_js', 'Cargar <code>assets/js/fertisem.js</code> del child theme (con versión automática)', 'safe' );
				fsb_checkbox( 'debug', 'Modo diagnóstico en consola (solo administradores): errores JS, LCP, CLS, TTFB', 'safe' );
				?>
			</td></tr>

			</tbody></table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
