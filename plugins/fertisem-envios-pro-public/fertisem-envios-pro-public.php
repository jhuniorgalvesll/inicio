<?php
/**
 * Plugin Name: FERTISEM - Envíos PRO (Public Sanitized)
 * Description: Edición pública sanitizada del motor de envíos para WooCommerce. Las tarifas comerciales reales y las asignaciones internas de distritos por zona no están incluidas.
 * Version: 6.2.4-public
 * Author: Fertisem
 * License: GPL2+
 * Text Domain: fertisem-envios-pro-v6
 */

if (!defined('ABSPATH')) exit;

/**
 * Esta edición pública omite deliberadamente tarifas y zonas operativas reales.
 * Configure sus propios valores mediante los filtros indicados en README.md.
 */

function fs_public_normalize_text($text) {
    $text = wp_strip_all_tags((string) $text);
    $text = remove_accents($text);
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $text = str_replace(array('–', '—'), '-', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text, " \t\n\r\0\x0B,.;:_-/");
}

function fs_public_compact_text($text) {
    return preg_replace('/[^a-z0-9]+/u', '', fs_public_normalize_text($text));
}

function fs_public_lima_districts() {
    return array(
        'ancon','ate','barranco','brena','carabayllo','chaclacayo','chorrillos','cieneguilla','comas','el agustino',
        'independencia','jesus maria','la molina','la victoria','lima','lince','los olivos','lurigancho','lurin',
        'magdalena del mar','miraflores','pachacamac','pucusana','pueblo libre','puente piedra','punta hermosa',
        'punta negra','rimac','san bartolo','san borja','san isidro','san juan de lurigancho','san juan de miraflores',
        'san luis','san martin de porres','san miguel','santa anita','santa maria del mar','santa rosa','santiago de surco',
        'surquillo','villa el salvador','villa maria del triunfo','bellavista','callao','carmen de la legua reynoso',
        'la perla','la punta','mi peru','ventanilla'
    );
}

function fs_public_aliases() {
    $aliases = array(
        'vitarte' => 'ate',
        'ate vitarte' => 'ate',
        'ate-vitarte' => 'ate',
        'atevitarte' => 'ate',
        'surco' => 'santiago de surco',
        'santiago surco' => 'santiago de surco',
        'santiago del surco' => 'santiago de surco',
        'cercado de lima' => 'lima',
        'lima cercado' => 'lima',
        'magdalena' => 'magdalena del mar',
        'san martin porres' => 'san martin de porres',
        'smp' => 'san martin de porres',
        'sjl' => 'san juan de lurigancho',
        'sjm' => 'san juan de miraflores',
        'pamplona' => 'san juan de miraflores',
        'pamplona alta' => 'san juan de miraflores',
        'pamplona baja' => 'san juan de miraflores',
        'ves' => 'villa el salvador',
        'vmt' => 'villa maria del triunfo',
        'chosica' => 'lurigancho',
        'lurigancho chosica' => 'lurigancho',
        'lurigancho-chosica' => 'lurigancho'
    );
    return apply_filters('fsep_public_aliases', $aliases);
}

function fs_public_canonical_district($text) {
    $candidate = fs_public_normalize_text($text);
    if (!$candidate) return '';
    $aliases = fs_public_aliases();
    if (isset($aliases[$candidate])) return $aliases[$candidate];
    $districts = fs_public_lima_districts();
    if (in_array($candidate, $districts, true)) return $candidate;
    $compact = fs_public_compact_text($candidate);
    foreach ($aliases as $alias => $canonical) {
        if (fs_public_compact_text($alias) === $compact) return $canonical;
    }
    foreach ($districts as $district) {
        if (fs_public_compact_text($district) === $compact) return $district;
    }
    return '';
}

function fs_public_zone_districts() {
    // Intentionally blank in the public edition.
    $zones = array('zone1' => array(), 'zone2' => array(), 'zone3' => array());
    return apply_filters('fsep_public_zone_districts', $zones);
}

function fs_public_get_zone($district) {
    $district = fs_public_canonical_district($district);
    if (!$district) return '';
    foreach (fs_public_zone_districts() as $zone => $districts) {
        if (in_array($district, $districts, true)) return $zone;
    }
    return '';
}

function fs_public_tariff_matrix() {
    // Real commercial values are intentionally zeroed.
    $matrix = array(
        'zone1' => array('r1'=>0.0,'r2'=>0.0,'r3'=>0.0,'r4'=>0.0,'heavy_per_kg'=>0.0),
        'zone2' => array('r1'=>0.0,'r2'=>0.0,'r3'=>0.0,'r4'=>0.0,'heavy_per_kg'=>0.0),
        'zone3' => array('r1'=>0.0,'r2'=>0.0,'r3'=>0.0,'r4'=>0.0,'heavy_per_kg'=>0.0),
    );
    return apply_filters('fsep_public_tariff_matrix', $matrix);
}

function fs_public_package_weight_kg($package = array()) {
    $weight = 0.0;
    $store_unit = get_option('woocommerce_weight_unit', 'kg');
    if (!empty($package['contents'])) {
        foreach ($package['contents'] as $item) {
            if (empty($item['data']) || !is_object($item['data'])) continue;
            $product_weight = (float) $item['data']->get_weight();
            $qty = !empty($item['quantity']) ? (int) $item['quantity'] : 1;
            if ($product_weight > 0) {
                $kg = function_exists('wc_get_weight') ? (float) wc_get_weight($product_weight, 'kg', $store_unit) : $product_weight;
                $weight += $kg * $qty;
            }
        }
    }
    return (float) $weight;
}

function fs_public_liquid_volume_fee($qty) {
    $fees = apply_filters('fsep_public_liquid_volume_fees', array(
        'one'=>0.0,'two_three'=>0.0,'four_six'=>0.0,'seven_plus'=>0.0
    ));
    $qty = max(0, (int) $qty);
    if ($qty <= 0) return 0.0;
    if ($qty === 1) return (float) $fees['one'];
    if ($qty <= 3) return (float) $fees['two_three'];
    if ($qty <= 6) return (float) $fees['four_six'];
    return (float) $fees['seven_plus'];
}

function fs_public_seed_volume_fee($qty) {
    $fees = apply_filters('fsep_public_seed_volume_fees', array('26_50'=>0.0,'51_plus'=>0.0));
    $qty = max(0, (int) $qty);
    if ($qty > 50) return (float) $fees['51_plus'];
    if ($qty >= 26) return (float) $fees['26_50'];
    return 0.0;
}

function fs_public_weight_range($kg) {
    $kg = (float) $kg;
    if ($kg <= 0) return 'unknown';
    if ($kg <= 0.50) return 'r1';
    if ($kg <= 2.29) return 'r2';
    if ($kg <= 6.29) return 'r3';
    if ($kg <= 10.29) return 'r4';
    if ($kg < 40) return 'coord';
    return 'heavy';
}

function fs_public_calculate_tariff($zone, $weight_kg, $volume_adjustment = 0.0) {
    $matrix = fs_public_tariff_matrix();
    if (!isset($matrix[$zone])) return array('mode'=>'coord','cost'=>0.0,'range'=>'unknown');
    $range = fs_public_weight_range($weight_kg);
    if ($range === 'coord' || $range === 'unknown') return array('mode'=>'coord','cost'=>0.0,'range'=>$range);
    if ($range === 'heavy') {
        $billable = (int) ceil($weight_kg);
        return array(
            'mode'=>'heavy',
            'cost'=>$billable * (float) $matrix[$zone]['heavy_per_kg'],
            'range'=>'heavy',
            'billable_weight'=>$billable,
        );
    }
    return array(
        'mode'=>'tariff',
        'cost'=>(float) $matrix[$zone][$range] + max(0, (float) $volume_adjustment),
        'range'=>$range,
    );
}

function fs_public_shipping_init() {
    if (!class_exists('WC_Shipping_Method')) return;

    class FS_Public_Shipping_Method extends WC_Shipping_Method {
        public function __construct($instance_id = 0) {
            $this->id = 'fs_public_shipping';
            $this->instance_id = absint($instance_id);
            $this->method_title = 'Envío por zona y peso — Public Sanitized';
            $this->method_description = 'Ejemplo sanitizado. Configure zonas y tarifas mediante filtros antes de usar.';
            $this->supports = array('shipping-zones','instance-settings');
            $this->enabled = 'yes';
            $this->title = 'Envío calculado';
        }

        public function calculate_shipping($package = array()) {
            $city = !empty($package['destination']['city']) ? $package['destination']['city'] : '';
            $district = fs_public_canonical_district($city);
            $zone = fs_public_get_zone($district);
            $weight = fs_public_package_weight_kg($package);

            if (!$zone || $weight <= 0) {
                return;
            }

            $calc = fs_public_calculate_tariff($zone, $weight, 0.0);
            if ($calc['mode'] === 'coord') return;

            $this->add_rate(array(
                'id' => $this->get_rate_id(),
                'label' => $this->title,
                'cost' => $calc['cost'],
                'meta_data' => array(
                    'zone' => $zone,
                    'weight_kg' => $weight,
                    'range' => $calc['range'],
                ),
            ));
        }
    }
}
add_action('woocommerce_shipping_init', 'fs_public_shipping_init');

function fs_public_register_shipping_method($methods) {
    $methods['fs_public_shipping'] = 'FS_Public_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'fs_public_register_shipping_method');
