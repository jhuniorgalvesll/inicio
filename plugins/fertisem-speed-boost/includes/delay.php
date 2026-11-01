<?php
/**
 * Retraso de scripts de terceros (reseñas, chats, mapas de calor) hasta la primera interacción.
 *
 * Bug 1.x: el loader hacía el.src = data-src sobre la MISMA etiqueta <script>. El navegador
 * nunca ejecuta una etiqueta insertada por el parser cuando se le cambia el src después,
 * así que los widgets nunca cargaban (y además nada convertía los scripts a data-src).
 *
 * Ahora: un filtro de salida HTML convierte los <script> que coinciden con las palabras clave
 * a type="fsb/delay" y un loader los vuelve a crear EN ORDEN tras scroll/click/tecla/toque
 * o tras N segundos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', function () {
	if ( ! fsb_is_frontend() || is_feed() || is_embed() || fsb_is_bypassed() ) {
		return;
	}

	$delay = fsb_enabled( 'delay_js' ) && fsb_lines( 'delay_js_keywords' );
	$fonts = fsb_enabled( 'block_google_fonts' );

	if ( ! $delay && ! $fonts ) {
		return;
	}

	ob_start( 'fsb_process_html' );
}, 1 );

function fsb_process_html( $html ) {
	// Solo documentos HTML completos (no JSON, no XML, no fragmentos AJAX).
	if ( ! is_string( $html ) || false === stripos( $html, '<html' ) || false === stripos( $html, '</body>' ) ) {
		return $html;
	}

	if ( fsb_enabled( 'block_google_fonts' ) ) {
		// <link> a Google Fonts escritos "a mano" por el tema o Elementor.
		$html = preg_replace( '~<link\b[^>]*fonts\.(googleapis|gstatic)\.com[^>]*>\s*~i', '', $html );
	}

	if ( fsb_enabled( 'delay_js' ) ) {
		$keywords = fsb_lines( 'delay_js_keywords' );

		if ( $keywords ) {
			$count = 0;
			$out   = preg_replace_callback(
				'~<script\b([^>]*)>(.*?)</script>~is',
				function ( $m ) use ( $keywords, &$count ) {
					return fsb_maybe_delay_script( $m, $keywords, $count );
				},
				$html
			);

			if ( null !== $out ) {
				$html = $out;
			}

			if ( $count > 0 ) {
				$html = preg_replace( '~</body>~i', fsb_delay_loader() . '</body>', $html, 1 );
			}
		}
	}

	return $html;
}

function fsb_maybe_delay_script( $m, $keywords, &$count ) {
	$attrs   = $m[1];
	$content = $m[2];

	if ( false !== stripos( $attrs, 'data-fsb-nodelay' ) ) {
		return $m[0];
	}

	// Solo JavaScript clásico (no JSON-LD, plantillas, speculationrules, módulos...).
	if ( preg_match( '~\btype\s*=\s*(["\']?)([^"\'\s>]+)\1~i', $attrs, $t ) ) {
		$type = strtolower( $t[2] );
		if ( ! in_array( $type, array( 'text/javascript', 'application/javascript' ), true ) ) {
			return $m[0];
		}
	}

	$src = '';
	if ( preg_match( '~\bsrc\s*=\s*(["\'])(.*?)\1~is', $attrs, $s ) ) {
		$src = $s[2];
	}

	$haystack = '' !== $src ? $src : $content;
	$match    = false;

	foreach ( $keywords as $kw ) {
		if ( '' !== $kw && false !== stripos( $haystack, $kw ) ) {
			$match = true;
			break;
		}
	}

	if ( ! $match ) {
		return $m[0];
	}

	$count++;

	$attrs = preg_replace( '~\btype\s*=\s*(["\']?)[^"\'\s>]+\1~i', '', $attrs );
	if ( '' !== $src ) {
		$attrs = preg_replace( '~\bsrc\s*=~i', 'data-fsb-src=', $attrs, 1 );
	}

	return '<script type="fsb/delay"' . $attrs . '>' . $content . '</script>';
}

function fsb_delay_loader() {
	$timeout = max( 0, (int) fsb_opt( 'delay_js_timeout' ) ) * 1000;

	$js = <<<JS
(function(w,d){
var done=false,ev=['keydown','mousedown','pointerdown','touchstart','wheel','scroll','mousemove'];
function run(e){
 /* Chrome lanza un mousemove sintético al cargar: solo cuenta movimiento real. */
 if(done||(e&&e.type==='mousemove'&&!e.movementX&&!e.movementY))return;done=true;
 ev.forEach(function(n){w.removeEventListener(n,run,{passive:true});});
 var list=[].slice.call(d.querySelectorAll('script[type="fsb/delay"],script[data-src]:not([src])'));
 (function next(){
  var old=list.shift();if(!old){w.dispatchEvent(new Event('fsb:loaded'));return;}
  var s=d.createElement('script'),src=old.getAttribute('data-fsb-src')||old.getAttribute('data-src');
  for(var i=0;i<old.attributes.length;i++){var a=old.attributes[i];
   if(['type','data-fsb-src','data-src'].indexOf(a.name)<0)s.setAttribute(a.name,a.value);}
  if(src){s.async=false;s.onload=s.onerror=next;s.src=src;old.parentNode.replaceChild(s,old);}
  else{s.text=old.text;old.parentNode.replaceChild(s,old);next();}
 })();
}
ev.forEach(function(n){w.addEventListener(n,run,{passive:true});});
if({$timeout}>0){w.addEventListener('load',function(){setTimeout(function(){run();},{$timeout});});}
})(window,document);
JS;

	return '<script id="fsb-delay-loader" data-fsb-nodelay>' . $js . '</script>';
}
