<?php
/**
 * Plugin Name: E-commerce Header Suite
 * Description: Proporciona un encabezado flexible para sitios de comercio electrónico, con búsqueda, resumen de carrito y accesos directos personalizables.
 * Version: 1.1.0
 * Author: Inicio Dev Team
 * Text Domain: ecommerce-header-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ECHP_VERSION', '1.1.0' );
define( 'ECHP_PLUGIN_FILE', __FILE__ );
define( 'ECHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ECHP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, [ 'Ecommerce_Header_Plugin', 'activate' ] );

class Ecommerce_Header_Plugin {
    /**
     * Track if the header has already been rendered.
     *
     * @var bool
     */
    private $rendered = false;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'register_menu_location' ] );
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_body_open', [ $this, 'render_header' ] );
        add_action( 'template_redirect', [ $this, 'maybe_attach_legacy_hook' ] );
        add_action( 'wp_footer', [ $this, 'render_whatsapp_widget' ] );
        add_action( 'wp_ajax_echp_cart_count', [ $this, 'ajax_cart_count' ] );
        add_action( 'wp_ajax_nopriv_echp_cart_count', [ $this, 'ajax_cart_count' ] );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'echp-header', ECHP_PLUGIN_URL . 'assets/css/header.css', [], ECHP_VERSION );
        wp_enqueue_script( 'echp-header', ECHP_PLUGIN_URL . 'assets/js/header.js', [], ECHP_VERSION, true );

        wp_localize_script(
            'echp-header',
            'echpHeader',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            ]
        );
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'ecommerce-header-suite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Ajax handler for retrieving cart count.
     */
    public function ajax_cart_count() {
        if ( function_exists( 'wc_load_cart' ) ) {
            wc_load_cart();
        }

        wp_send_json_success(
            [
                'count' => $this->get_cart_count(),
            ]
        );
    }

    /**
     * Runs on plugin activation.
     */
    public static function activate() {
        $default = self::get_default_options();
        $saved   = get_option( 'echp_options', [] );

        update_option( 'echp_options', wp_parse_args( $saved, $default ) );
    }

    /**
     * Returns default options.
     *
     * @return array
     */
    public static function get_default_options() {
        return [
            'logo_url'               => '',
            'cta_label'              => __( 'Ver catálogo', 'ecommerce-header-suite' ),
            'cta_link'               => home_url( '/shop' ),
            'support_text'           => __( 'Envío gratis en pedidos superiores a $50', 'ecommerce-header-suite' ),
            'contact_label'          => __( 'Asesoría inmediata', 'ecommerce-header-suite' ),
            'contact_phone'          => '+51 999 999 999',
            'top_links'              => "Mayoristas|/mayoristas\nPromociones|/promociones\nBlog|/blog",
            'mockup_badges'          => "Stock actualizado\nEnvío nacional\nAsesoría personalizada",
            'show_search'            => 1,
            'show_account'           => 1,
            'show_cart'              => 1,
            'whatsapp_enabled'       => 1,
            'whatsapp_title'         => __( 'Habla con Fertisem', 'ecommerce-header-suite' ),
            'whatsapp_description'   => __( 'Elige a la persona indicada para ayudarte.', 'ecommerce-header-suite' ),
            'whatsapp_button_label'  => __( 'WhatsApp', 'ecommerce-header-suite' ),
            'whatsapp_position'      => 'right',
            'whatsapp_contacts'      => "Ventas|+51999999999|Hola, quiero cotizar\nSoporte|+51999999998|Necesito ayuda con mi pedido",
        ];
    }

    /**
     * Register header menu location.
     */
    public function register_menu_location() {
        register_nav_menu( 'echp_header_menu', __( 'Menú encabezado e-commerce', 'ecommerce-header-suite' ) );
    }

    /**
     * Register settings page under Appearance menu.
     */
    public function register_settings_page() {
        add_theme_page(
            __( 'Encabezado E-commerce', 'ecommerce-header-suite' ),
            __( 'Encabezado E-commerce', 'ecommerce-header-suite' ),
            'manage_options',
            'echp-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'echp_settings', 'echp_options', [ $this, 'sanitize_options' ] );

        add_settings_section(
            'echp_settings_general',
            __( 'Ajustes generales', 'ecommerce-header-suite' ),
            function () {
                echo '<p>' . esc_html__( 'Personaliza los elementos que aparecerán en el encabezado.', 'ecommerce-header-suite' ) . '</p>';
            },
            'echp-settings'
        );

        add_settings_field( 'echp_logo_url', __( 'URL del logo', 'ecommerce-header-suite' ), [ $this, 'field_logo_url' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_cta_label', __( 'Texto del botón principal', 'ecommerce-header-suite' ), [ $this, 'field_cta_label' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_cta_link', __( 'Enlace del botón principal', 'ecommerce-header-suite' ), [ $this, 'field_cta_link' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_support_text', __( 'Texto informativo', 'ecommerce-header-suite' ), [ $this, 'field_support_text' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_contact_label', __( 'Etiqueta de contacto', 'ecommerce-header-suite' ), [ $this, 'field_contact_label' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_contact_phone', __( 'Teléfono de contacto', 'ecommerce-header-suite' ), [ $this, 'field_contact_phone' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_top_links', __( 'Enlaces rápidos superiores', 'ecommerce-header-suite' ), [ $this, 'field_top_links' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_mockup_badges', __( 'Badges del mockup', 'ecommerce-header-suite' ), [ $this, 'field_mockup_badges' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_show_search', __( 'Mostrar buscador', 'ecommerce-header-suite' ), [ $this, 'field_show_search' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_show_account', __( 'Mostrar acceso a cuenta', 'ecommerce-header-suite' ), [ $this, 'field_show_account' ], 'echp-settings', 'echp_settings_general' );
        add_settings_field( 'echp_show_cart', __( 'Mostrar resumen de carrito', 'ecommerce-header-suite' ), [ $this, 'field_show_cart' ], 'echp-settings', 'echp_settings_general' );

        add_settings_section(
            'echp_settings_whatsapp',
            __( 'WhatsApp flotante', 'ecommerce-header-suite' ),
            function () {
                echo '<p>' . esc_html__( 'Configura el botón flotante de WhatsApp para Fertisem con varias personas de contacto.', 'ecommerce-header-suite' ) . '</p>';
            },
            'echp-settings'
        );

        add_settings_field( 'echp_whatsapp_enabled', __( 'Activar botón flotante', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_enabled' ], 'echp-settings', 'echp_settings_whatsapp' );
        add_settings_field( 'echp_whatsapp_title', __( 'Título del panel', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_title' ], 'echp-settings', 'echp_settings_whatsapp' );
        add_settings_field( 'echp_whatsapp_description', __( 'Descripción corta', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_description' ], 'echp-settings', 'echp_settings_whatsapp' );
        add_settings_field( 'echp_whatsapp_button_label', __( 'Etiqueta del botón', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_button_label' ], 'echp-settings', 'echp_settings_whatsapp' );
        add_settings_field( 'echp_whatsapp_position', __( 'Posición del botón', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_position' ], 'echp-settings', 'echp_settings_whatsapp' );
        add_settings_field( 'echp_whatsapp_contacts', __( 'Personas de contacto', 'ecommerce-header-suite' ), [ $this, 'field_whatsapp_contacts' ], 'echp-settings', 'echp_settings_whatsapp' );
    }

    /**
     * Sanitize options before saving.
     *
     * @param array $input Incoming values.
     *
     * @return array
     */
    public function sanitize_options( $input ) {
        $defaults = self::get_default_options();
        $output   = $defaults;

        $output['logo_url']              = isset( $input['logo_url'] ) ? esc_url_raw( $input['logo_url'] ) : $defaults['logo_url'];
        $output['cta_label']             = isset( $input['cta_label'] ) ? sanitize_text_field( $input['cta_label'] ) : $defaults['cta_label'];
        $output['cta_link']              = isset( $input['cta_link'] ) ? esc_url_raw( $input['cta_link'] ) : $defaults['cta_link'];
        $output['support_text']          = isset( $input['support_text'] ) ? sanitize_text_field( $input['support_text'] ) : $defaults['support_text'];
        $output['contact_label']         = isset( $input['contact_label'] ) ? sanitize_text_field( $input['contact_label'] ) : $defaults['contact_label'];
        $output['contact_phone']         = isset( $input['contact_phone'] ) ? sanitize_text_field( $input['contact_phone'] ) : $defaults['contact_phone'];
        $output['top_links']             = isset( $input['top_links'] ) ? sanitize_textarea_field( $input['top_links'] ) : $defaults['top_links'];
        $output['mockup_badges']         = isset( $input['mockup_badges'] ) ? sanitize_textarea_field( $input['mockup_badges'] ) : $defaults['mockup_badges'];
        $output['show_search']           = empty( $input['show_search'] ) ? 0 : 1;
        $output['show_account']          = empty( $input['show_account'] ) ? 0 : 1;
        $output['show_cart']             = empty( $input['show_cart'] ) ? 0 : 1;
        $output['whatsapp_enabled']      = empty( $input['whatsapp_enabled'] ) ? 0 : 1;
        $output['whatsapp_title']        = isset( $input['whatsapp_title'] ) ? sanitize_text_field( $input['whatsapp_title'] ) : $defaults['whatsapp_title'];
        $output['whatsapp_description']  = isset( $input['whatsapp_description'] ) ? sanitize_text_field( $input['whatsapp_description'] ) : $defaults['whatsapp_description'];
        $output['whatsapp_button_label'] = isset( $input['whatsapp_button_label'] ) ? sanitize_text_field( $input['whatsapp_button_label'] ) : $defaults['whatsapp_button_label'];
        $output['whatsapp_position']     = isset( $input['whatsapp_position'] ) && in_array( $input['whatsapp_position'], [ 'left', 'right' ], true )
            ? $input['whatsapp_position']
            : $defaults['whatsapp_position'];
        $output['whatsapp_contacts']     = isset( $input['whatsapp_contacts'] ) ? sanitize_textarea_field( $input['whatsapp_contacts'] ) : $defaults['whatsapp_contacts'];

        return $output;
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Encabezado E-commerce', 'ecommerce-header-suite' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'echp_settings' );
                do_settings_sections( 'echp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render floating WhatsApp widget.
     */
    public function render_whatsapp_widget() {
        if ( is_admin() ) {
            return;
        }

        $options  = $this->get_options();
        $contacts = $this->parse_whatsapp_contacts( $options['whatsapp_contacts'] );

        if ( empty( $options['whatsapp_enabled'] ) || empty( $contacts ) ) {
            return;
        }

        $position_class = $options['whatsapp_position'] === 'left' ? 'echp-whatsapp--left' : 'echp-whatsapp--right';
        $panel_id       = 'echp-whatsapp-panel';
        ?>
        <div class="echp-whatsapp <?php echo esc_attr( $position_class ); ?>" data-echp-whatsapp>
            <button class="echp-whatsapp__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" data-echp-whatsapp-toggle>
                <span class="echp-whatsapp__icon" aria-hidden="true"></span>
                <span class="echp-whatsapp__label"><?php echo esc_html( $options['whatsapp_button_label'] ); ?></span>
            </button>
            <div id="<?php echo esc_attr( $panel_id ); ?>" class="echp-whatsapp__panel" aria-hidden="true" data-echp-whatsapp-panel>
                <div class="echp-whatsapp__header">
                    <p class="echp-whatsapp__title"><?php echo esc_html( $options['whatsapp_title'] ); ?></p>
                    <?php if ( ! empty( $options['whatsapp_description'] ) ) : ?>
                        <p class="echp-whatsapp__description"><?php echo esc_html( $options['whatsapp_description'] ); ?></p>
                    <?php endif; ?>
                </div>
                <ul class="echp-whatsapp__list">
                    <?php foreach ( $contacts as $contact ) : ?>
                        <li>
                            <a class="echp-whatsapp__contact" href="<?php echo esc_url( $contact['url'] ); ?>" target="_blank" rel="noopener">
                                <span class="echp-whatsapp__name"><?php echo esc_html( $contact['name'] ); ?></span>
                                <span class="echp-whatsapp__phone"><?php echo esc_html( $contact['phone_label'] ); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render header output on front-end.
     */
    public function render_header() {
        if ( $this->rendered ) {
            return;
        }

        $this->rendered = true;

        $options = $this->get_options();

        $cta_label = $options['cta_label'];
        $cta_link  = $options['cta_link'];
        $logo_url  = $options['logo_url'];
        $support   = $options['support_text'];

        $top_links    = $this->parse_quick_links( $options['top_links'] );
        $mockup_badges = $this->parse_badges( $options['mockup_badges'] );

        $account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
        $cart_url     = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#';
        $cart_count   = $this->get_cart_count();
        $show_top_bar = ! empty( $support ) || ! empty( $options['contact_phone'] ) || ! empty( $top_links ) || $options['show_account'];

        $has_menu = has_nav_menu( 'echp_header_menu' );

        ?>
        <header class="echp-header" role="banner">
            <?php if ( $show_top_bar ) : ?>
                <div class="echp-top-bar">
                    <div class="echp-top-bar__left">
                        <?php if ( ! empty( $support ) ) : ?>
                            <span class="echp-support-text"><?php echo esc_html( $support ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $options['contact_phone'] ) ) : ?>
                            <a class="echp-contact-link" href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $options['contact_phone'] ) ); ?>">
                                <?php echo esc_html( $options['contact_label'] . ': ' . $options['contact_phone'] ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="echp-top-bar__right">
                        <?php foreach ( $top_links as $link ) : ?>
                            <a class="echp-top-link" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
                        <?php endforeach; ?>
                        <?php if ( $options['show_account'] ) : ?>
                            <a class="echp-account-link" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Mi cuenta', 'ecommerce-header-suite' ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="echp-main-bar">
                <div class="echp-branding">
                    <?php if ( ! empty( $logo_url ) ) : ?>
                        <a class="echp-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Logo', 'ecommerce-header-suite' ); ?>" />
                        </a>
                    <?php else : ?>
                        <a class="echp-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
                    <?php endif; ?>
                </div>
                <?php if ( $options['show_search'] ) : ?>
                    <div class="echp-search">
                        <?php get_search_form(); ?>
                    </div>
                <?php endif; ?>
                <div class="echp-actions">
                    <a class="echp-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_label ); ?></a>
                    <?php if ( $options['show_cart'] ) : ?>
                        <a class="echp-cart" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Ver carrito', 'ecommerce-header-suite' ); ?>">
                            <span class="echp-cart-icon" aria-hidden="true"></span>
                            <span class="echp-cart-count" data-echp-cart-count><?php echo esc_html( $cart_count ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ( $has_menu ) : ?>
                    <button class="echp-menu-toggle" type="button" aria-expanded="false" aria-controls="echp-main-navigation">
                        <span class="echp-menu-toggle-icon" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Abrir menú', 'ecommerce-header-suite' ); ?></span>
                    </button>
                <?php endif; ?>
            </div>
            <nav id="echp-main-navigation" class="echp-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Menú principal', 'ecommerce-header-suite' ); ?>">
                <?php
                if ( $has_menu ) {
                    wp_nav_menu(
                        [
                            'theme_location' => 'echp_header_menu',
                            'container'      => false,
                            'menu_class'     => 'echp-menu',
                            'menu_id'        => 'echp-menu-list',
                            'fallback_cb'    => false,
                        ]
                    );
                } else {
                    echo '<ul class="echp-menu"><li>' . esc_html__( 'Asigna un menú al "Menú encabezado e-commerce" desde Apariencia > Menús.', 'ecommerce-header-suite' ) . '</li></ul>';
                }
                ?>
            </nav>
            <?php if ( ! empty( $mockup_badges ) ) : ?>
                <div class="echp-badges" role="list" aria-label="<?php esc_attr_e( 'Diferenciales de la tienda', 'ecommerce-header-suite' ); ?>">
                    <?php foreach ( $mockup_badges as $badge ) : ?>
                        <span class="echp-badge" role="listitem"><?php echo esc_html( $badge ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>
        <?php
    }

    /**
     * Attach header for themes without wp_body_open.
     */
    public function maybe_attach_legacy_hook() {
        if ( did_action( 'wp_body_open' ) ) {
            return;
        }

        add_action( 'wp_footer', [ $this, 'render_header' ], 5 );
    }

    /**
     * Retrieve stored options merged with defaults.
     *
     * @return array
     */
    private function get_options() {
        return wp_parse_args( get_option( 'echp_options', [] ), self::get_default_options() );
    }

    /**
     * Return WooCommerce cart item count.
     *
     * @return int
     */
    private function get_cart_count() {
        if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
            $wc = WC();

            if ( null === $wc->cart && function_exists( 'wc_load_cart' ) ) {
                wc_load_cart();
            }

            if ( $wc->cart && method_exists( $wc->cart, 'get_cart' ) ) {
                $wc->cart->get_cart();
            }

            if ( $wc->cart && method_exists( $wc->cart, 'get_cart_contents_count' ) ) {
                return (int) $wc->cart->get_cart_contents_count();
            }
        }

        return 0;
    }

    /**
     * Parse quick links from textarea input.
     *
     * @param string $raw_links Raw textarea value.
     *
     * @return array
     */
    private function parse_quick_links( $raw_links ) {
        $links = [];
        $lines = preg_split( '/\r\n|\r|\n/', (string) $raw_links );

        if ( empty( $lines ) ) {
            return $links;
        }

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' === $line ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            $label = $parts[0] ?? '';
            $url   = $parts[1] ?? '';

            if ( '' === $label || '' === $url ) {
                continue;
            }

            $links[] = [
                'label' => $label,
                'url'   => $url,
            ];
        }

        return $links;
    }

    /**
     * Parse badges from textarea input.
     *
     * @param string $raw_badges Raw textarea value.
     *
     * @return array
     */
    private function parse_badges( $raw_badges ) {
        $badges = [];
        $lines  = preg_split( '/\r\n|\r|\n/', (string) $raw_badges );

        if ( empty( $lines ) ) {
            return $badges;
        }

        foreach ( $lines as $line ) {
            $value = trim( $line );
            if ( '' !== $value ) {
                $badges[] = $value;
            }
        }

        return $badges;
    }

    /**
     * Parse WhatsApp contacts from textarea input.
     *
     * @param string $raw_contacts Raw textarea value.
     *
     * @return array
     */
    private function parse_whatsapp_contacts( $raw_contacts ) {
        $contacts = [];
        $lines    = preg_split( '/\r\n|\r|\n/', (string) $raw_contacts );

        if ( empty( $lines ) ) {
            return $contacts;
        }

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                continue;
            }

            $parts = array_map( 'trim', explode( '|', $line ) );
            $name  = $parts[0] ?? '';
            $phone = $parts[1] ?? '';
            $text  = $parts[2] ?? '';

            $phone_digits = preg_replace( '/\D+/', '', $phone );
            if ( $phone_digits === '' ) {
                continue;
            }

            $url = 'https://wa.me/' . $phone_digits;
            if ( $text !== '' ) {
                $url .= '?text=' . rawurlencode( $text );
            }

            $contacts[] = [
                'name'        => $name !== '' ? $name : __( 'Asesor', 'ecommerce-header-suite' ),
                'phone_label' => $phone !== '' ? $phone : $phone_digits,
                'url'         => $url,
            ];
        }

        return $contacts;
    }

    /**
     * Field: logo URL.
     */
    public function field_logo_url() {
        $options = $this->get_options();
        ?>
        <input type="url" class="regular-text" name="echp_options[logo_url]" value="<?php echo esc_attr( $options['logo_url'] ); ?>" placeholder="https://..." />
        <p class="description"><?php esc_html_e( 'Enlace directo a la imagen del logo.', 'ecommerce-header-suite' ); ?></p>
        <?php
    }

    /**
     * Field: CTA label.
     */
    public function field_cta_label() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[cta_label]" value="<?php echo esc_attr( $options['cta_label'] ); ?>" />
        <?php
    }

    /**
     * Field: CTA link.
     */
    public function field_cta_link() {
        $options = $this->get_options();
        ?>
        <input type="url" class="regular-text" name="echp_options[cta_link]" value="<?php echo esc_attr( $options['cta_link'] ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
        <?php
    }

    /**
     * Field: Support text.
     */
    public function field_support_text() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[support_text]" value="<?php echo esc_attr( $options['support_text'] ); ?>" />
        <?php
    }

    /**
     * Field: contact label.
     */
    public function field_contact_label() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[contact_label]" value="<?php echo esc_attr( $options['contact_label'] ); ?>" />
        <?php
    }

    /**
     * Field: contact phone.
     */
    public function field_contact_phone() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[contact_phone]" value="<?php echo esc_attr( $options['contact_phone'] ); ?>" />
        <?php
    }

    /**
     * Field: top links.
     */
    public function field_top_links() {
        $options = $this->get_options();
        ?>
        <textarea class="large-text" rows="4" name="echp_options[top_links]" placeholder="Promociones|/promociones"><?php echo esc_textarea( $options['top_links'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Un enlace por línea. Formato: Etiqueta|URL.', 'ecommerce-header-suite' ); ?></p>
        <?php
    }

    /**
     * Field: mockup badges.
     */
    public function field_mockup_badges() {
        $options = $this->get_options();
        ?>
        <textarea class="large-text" rows="4" name="echp_options[mockup_badges]" placeholder="Stock actualizado"><?php echo esc_textarea( $options['mockup_badges'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Un badge por línea para la franja inferior del mockup.', 'ecommerce-header-suite' ); ?></p>
        <?php
    }

    /**
     * Field: show search.
     */
    public function field_show_search() {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="echp_options[show_search]" value="1" <?php checked( $options['show_search'], 1 ); ?> />
            <?php esc_html_e( 'Activar formulario de búsqueda en el encabezado.', 'ecommerce-header-suite' ); ?>
        </label>
        <?php
    }

    /**
     * Field: show account link.
     */
    public function field_show_account() {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="echp_options[show_account]" value="1" <?php checked( $options['show_account'], 1 ); ?> />
            <?php esc_html_e( 'Mostrar enlace a la cuenta del cliente.', 'ecommerce-header-suite' ); ?>
        </label>
        <?php
    }

    /**
     * Field: show cart link.
     */
    public function field_show_cart() {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="echp_options[show_cart]" value="1" <?php checked( $options['show_cart'], 1 ); ?> />
            <?php esc_html_e( 'Mostrar enlace al carrito con cantidad de productos.', 'ecommerce-header-suite' ); ?>
        </label>
        <?php
    }

    /**
     * Field: WhatsApp enabled.
     */
    public function field_whatsapp_enabled() {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="echp_options[whatsapp_enabled]" value="1" <?php checked( $options['whatsapp_enabled'], 1 ); ?> />
            <?php esc_html_e( 'Mostrar el botón flotante de WhatsApp.', 'ecommerce-header-suite' ); ?>
        </label>
        <?php
    }

    /**
     * Field: WhatsApp title.
     */
    public function field_whatsapp_title() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[whatsapp_title]" value="<?php echo esc_attr( $options['whatsapp_title'] ); ?>" />
        <?php
    }

    /**
     * Field: WhatsApp description.
     */
    public function field_whatsapp_description() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[whatsapp_description]" value="<?php echo esc_attr( $options['whatsapp_description'] ); ?>" />
        <?php
    }

    /**
     * Field: WhatsApp button label.
     */
    public function field_whatsapp_button_label() {
        $options = $this->get_options();
        ?>
        <input type="text" class="regular-text" name="echp_options[whatsapp_button_label]" value="<?php echo esc_attr( $options['whatsapp_button_label'] ); ?>" />
        <?php
    }

    /**
     * Field: WhatsApp position.
     */
    public function field_whatsapp_position() {
        $options = $this->get_options();
        ?>
        <select name="echp_options[whatsapp_position]">
            <option value="right" <?php selected( $options['whatsapp_position'], 'right' ); ?>><?php esc_html_e( 'Derecha', 'ecommerce-header-suite' ); ?></option>
            <option value="left" <?php selected( $options['whatsapp_position'], 'left' ); ?>><?php esc_html_e( 'Izquierda', 'ecommerce-header-suite' ); ?></option>
        </select>
        <?php
    }

    /**
     * Field: WhatsApp contacts.
     */
    public function field_whatsapp_contacts() {
        $options = $this->get_options();
        ?>
        <textarea class="large-text" rows="5" name="echp_options[whatsapp_contacts]" placeholder="Nombre|+51999999999|Mensaje personalizado"><?php echo esc_textarea( $options['whatsapp_contacts'] ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Una persona por línea. Formato: Nombre|Teléfono|Mensaje opcional.', 'ecommerce-header-suite' ); ?>
        </p>
        <?php
    }
}

new Ecommerce_Header_Plugin();
