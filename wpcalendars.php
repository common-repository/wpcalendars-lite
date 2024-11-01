<?php
/**
 * Plugin Name: WPCalendars Lite
 * Plugin URI: https://wpcalendars.com
 * Description: Create your events calendar with the fast and easy-to-use builder for beginners.
 * Author: WPCalendars Team
 * Author URI: https://wpcalendars.com
 * Version: 1.2.3
 * Text Domain: wpcalendars
 * Domain Path: languages
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WPCalendars' ) ):
    
    /**
     * Deactivate if WPCalendars Lite already activated.
     */
    function wpcalendars_deactivate() {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
    
    add_action( 'admin_init', 'wpcalendars_deactivate' );
    
    /**
     * Display notice after deactivation.
     */
    function wpcalendars_lite_notice() {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please deactivate WPCalendars Lite before activating WPCalendars Pro.', 'wpcalendars' ) . '</p></div>';
        
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
    
    add_action( 'admin_notices', 'wpcalendars_lite_notice' );

else:

class WPCalendars {

    private static $_instance = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        // Define WPCALENDARS_PLUGIN_FILE.
        if ( !defined( 'WPCALENDARS_PLUGIN_FILE' ) ) {
            define( 'WPCALENDARS_PLUGIN_FILE', __FILE__ );
        }
        
        // Plugin version
        if ( !defined( 'WPCALENDARS_PLUGIN_VERSION' ) ) {
            define( 'WPCALENDARS_PLUGIN_VERSION', '1.2.3' );
        }
        
        // File base name.
        if ( !defined( 'WPCALENDARS_PLUGIN_BASENAME' ) ) {
            define( 'WPCALENDARS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        }

        // Plugin Folder Path.
        if ( !defined( 'WPCALENDARS_PLUGIN_DIR' ) ) {
            define( 'WPCALENDARS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }

        // Plugin Folder URL.
        if ( !defined( 'WPCALENDARS_PLUGIN_URL' ) ) {
            define( 'WPCALENDARS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }
        
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/general-functions.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/template-functions.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/install.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/post-type.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/blocks.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/widget.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/ajax.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/templates.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/theme-support.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/shortcodes.php';

        if ( is_admin() ) {
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/admin-functions.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/welcome.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/categories.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/overview.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/builder.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/settings.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/tools.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/meta-boxes.php';
            require_once WPCALENDARS_PLUGIN_DIR . 'includes/admin/permalinks.php';
        }
        
        // Load pre-defined calendar
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/calendars/monthly.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/calendars/multiple-months.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/calendars/weekly.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/calendars/daily.php';
        require_once WPCALENDARS_PLUGIN_DIR . 'includes/calendars/list.php';

        // Load Pro specific files
        if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'pro/wpcalendars-pro.php' ) ) {
            require_once WPCALENDARS_PLUGIN_DIR . 'pro/wpcalendars-pro.php';
        } else {
            require_once WPCALENDARS_PLUGIN_DIR . 'lite/wpcalendars-lite.php';
        }

        add_action( 'init',                  array( $this, 'init' ), 1 );
        add_action( 'template_redirect',     array( $this, 'render_colors_style' ), 0 );
        add_action( 'plugins_loaded',        array( $this, 'setup' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer',             array( $this, 'loading_panel' ) );
        add_action( 'admin_footer',          array( $this, 'event_popup_panel' ) );
        add_action( 'wp_footer',             array( $this, 'event_popup_panel' ) );
        
        add_filter( 'query_vars', array( $this, 'colors_vars' ) );
        add_filter( ( is_multisite() ? 'network_admin_' : '' ) . 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
    }

    /**
     * retrieve singleton class instance
     * @return instance reference to plugin
     */
    public static function instance() {
        if ( NULL === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Event color stylesheet initialization
     * @global type $wp
     */
    public function init() {
        global $wp;
        
        $wp->add_query_var( 'wpcalendars-color-css' );
        add_rewrite_rule( 'wpcalendars-colors\.css$', 'index.php?wpcalendars-color-css=1', 'top' );
    }
    
    /**
     * Add query variables for event color stylesheet
     * @param array $vars
     * @return string
     */
    public function colors_vars( $vars ) {
        $vars[] = 'wpcalendars-color-css';
        return $vars;
    }
    
    /**
     * Render event colors stylesheet
     */
    public function render_colors_style() {
        if ( get_query_var( 'wpcalendars-color-css' ) === '1' ) {
            header( 'Content-Type: text/css; charset: UTF-8' );
            echo wpcalendars_get_event_single_color();
            exit;
        }
    }
    
    /**
     * Setup image size and localization
     */
    public function setup() {
        add_image_size( 'wpcalendars-thumbnail-image', 400, 250, true );
        add_image_size( 'wpcalendars-featured-image', 800, 500, true );
        
        if ( ! is_textdomain_loaded( 'wpcalendars' ) ) {
            load_plugin_textdomain( 'wpcalendars', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }
    }
    
    /**
     * Add support link to the Plugins page.
     * @param array $links
     * @param string $file
     * @return array $links
     */
    public function plugin_action_links( $links, $file ) {
        if ( is_array( $links ) && dirname( plugin_basename( __FILE__ ) ) . '/wpcalendars.php' === $file ) {
            $settings_link = '<a href="' . apply_filters( 'wpcalendars_com_link', "//wpcalendars.com/" ) . '">' . __( 'Pro Support', 'wpcalendars' ) . '</a>';
            array_unshift($links, $settings_link);
        }
        
        return $links;
    }
    
    /**
     * Get admin script arguments
     * @return type
     */
    public static function admin_script_args() {
        $script_args = array(
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'wpcalendars_admin' ),
            'builderExitUrl'   => admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-overview' ),
            'mapZoom'          => wpcalendars_settings_value( 'general', 'map_zoom' ),
            'googleMapsApikey' => wpcalendars_settings_value( 'api', 'google_maps_apikey' ),
            'loading'          => __( 'Loading...', 'wpcalendars' ),
            'ajaxSaving'       => __( 'Saving...', 'wpcalendars' ),
            'ajaxRemoving'     => __( 'Removing...', 'wpcalendars' ),
            'datepickerButton' => __( 'Choose', 'wpcalendars' ),
            'doneMessage'      => __( 'Done!', 'wpcalendars' ),
            'warnDelete'       => __( 'Are you sure want to delete this item?', 'wpcalendars' ),
        );
        
        return apply_filters( 'wpcalendars_admin_script_args', $script_args );
    }
    
    /**
     * Get frontend script arguments
     * @return type
     */
    public static function frontend_script_args() {
        return apply_filters( 'wpcalendars_script_args', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wpcalendars' ),
            'loading' => __( 'Loading...', 'wpcalendars' )
        ) );
    }

    /**
     * Load admin stylesheet and script
     * @param string $hook
     */
    public function admin_enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        wp_register_script( 'google-maps-api',               '//maps.googleapis.com/maps/api/js?key=' . wpcalendars_settings_value( 'api', 'google_maps_apikey' ) . '&libraries=places' );
        wp_register_script( 'magnific-popup',                WPCALENDARS_PLUGIN_URL . 'assets/js/jquery.magnific-popup.min.js', array( 'jquery' ) );
        wp_register_script( 'tooltipster',                   WPCALENDARS_PLUGIN_URL . 'assets/js/tooltipster.bundle.min.js', array( 'jquery' ) );
        wp_register_script( 'jquery-select2',                WPCALENDARS_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery' ) );
        wp_register_script( 'wpcalendars-admin-event',       WPCALENDARS_PLUGIN_URL . 'assets/js/admin-event.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-datepicker' ) );
        wp_register_script( 'wpcalendars-admin-venue',       WPCALENDARS_PLUGIN_URL . 'assets/js/admin-venue.js', array( 'jquery' ) );
        wp_register_script( 'wpcalendars-admin-calendar',    WPCALENDARS_PLUGIN_URL . 'assets/js/admin-calendar.js', array( 'jquery' ) );
        wp_register_script( 'wpcalendars-admin-builder',     WPCALENDARS_PLUGIN_URL . 'assets/js/admin-builder.js', array( 'jquery', 'jquery-select2', 'jquery-ui-datepicker', 'magnific-popup', 'tooltipster' ) );
        wp_register_script( 'wpcalendars-admin-settings',    WPCALENDARS_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'jquery-select2' ) );
        wp_register_script( 'wpcalendars-admin-category',    WPCALENDARS_PLUGIN_URL . 'assets/js/admin-category.js', array( 'jquery', 'wp-color-picker' ) );
        wp_register_script( 'wpcalendars-admin-google-maps', WPCALENDARS_PLUGIN_URL . 'assets/js/admin-google-maps.js', array( 'jquery', 'google-maps-api' ) );
        wp_register_script( 'wpcalendars-admin-preview',     WPCALENDARS_PLUGIN_URL . 'assets/js/frontend.js', array( 'wpcalendars-admin-builder' ) );
        
        wp_register_style( 'magnific-popup',                WPCALENDARS_PLUGIN_URL . 'assets/css/magnific-popup.css' );
        wp_register_style( 'jquery-ui',                     WPCALENDARS_PLUGIN_URL . 'assets/css/jquery-ui.css' );
        wp_register_style( 'datepicker',                    WPCALENDARS_PLUGIN_URL . 'assets/css/datepicker.css' );
        wp_register_style( 'tooltipster',                   WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster.bundle.min.css' );
        wp_register_style( 'tooltipster-borderless',        WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-borderless.min.css' );
        wp_register_style( 'tooltipster-light',             WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-light.min.css' );
        wp_register_style( 'tooltipster-noir',              WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-noir.min.css' );
        wp_register_style( 'tooltipster-punk',              WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-punk.min.css' );
        wp_register_style( 'tooltipster-shadow',            WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-shadow.min.css' );
        wp_register_style( 'jquery-select2',                WPCALENDARS_PLUGIN_URL . 'assets/css/select2.css' );
        wp_register_style( 'wpcalendars-admin-event',       WPCALENDARS_PLUGIN_URL . 'assets/css/admin-event.css', array( 'jquery-ui', 'datepicker' ) );
        wp_register_style( 'wpcalendars-admin-venue',       WPCALENDARS_PLUGIN_URL . 'assets/css/admin-venue.css', array( 'magnific-popup' ) );
        wp_register_style( 'wpcalendars-admin-welcome',     WPCALENDARS_PLUGIN_URL . 'assets/css/admin-welcome.css', array() );
        wp_register_style( 'wpcalendars-admin-builder',     WPCALENDARS_PLUGIN_URL . 'assets/css/admin-builder.css', array( 'jquery-select2', 'jquery-ui', 'datepicker', 'magnific-popup', 'tooltipster', 'tooltipster-punk' ) );
        wp_register_style( 'wpcalendars-admin-settings',    WPCALENDARS_PLUGIN_URL . 'assets/css/admin-settings.css', array( 'jquery-select2' ) );
        wp_register_style( 'wpcalendars-admin-category',    WPCALENDARS_PLUGIN_URL . 'assets/css/admin-category.css', array() );
        wp_register_style( 'wpcalendars-admin-preview',     WPCALENDARS_PLUGIN_URL . 'assets/css/frontend.css', array( 'wpcalendars-admin-builder' ) );
        wp_register_style( 'wpcalendars-admin-colors',      home_url( '/wpcalendars-colors.css' ), array( 'wpcalendars-admin-preview' ) );
        
        if ( isset( $screen->post_type ) && 'wpcalendars_event' === $screen->post_type && isset( $screen->base ) ) {
            if ( 'wpcalendars_event_page_wpcalendars-overview' === $screen->base ) {
                wp_enqueue_script( 'wpcalendars-admin-calendar' );
                wp_localize_script( 'wpcalendars-admin-calendar', 'WPCalendarsAdmin', self::admin_script_args() );
            } elseif ( 'wpcalendars_event_page_wpcalendars-builder' === $screen->base ) {
                wp_enqueue_script( 'jquery-select2' );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_script( 'magnific-popup' );
                wp_enqueue_script( 'tooltipster' );
                wp_enqueue_script( 'wpcalendars-admin-builder' );
                wp_enqueue_script( 'wpcalendars-admin-preview' );

                wp_enqueue_style( 'jquery-select2' );
                wp_enqueue_style( 'jquery-ui' );
                wp_enqueue_style( 'datepicker' );
                wp_enqueue_style( 'magnific-popup' );
                wp_enqueue_style( 'tooltipster' );
                wp_enqueue_style( 'tooltipster-borderless' );
                wp_enqueue_style( 'tooltipster-light' );
                wp_enqueue_style( 'tooltipster-noir' );
                wp_enqueue_style( 'tooltipster-punk' );
                wp_enqueue_style( 'tooltipster-shadow' );
                wp_enqueue_style( 'wpcalendars-admin-builder' );
                wp_enqueue_style( 'wpcalendars-admin-preview' );
                wp_enqueue_style( 'wpcalendars-admin-colors' );

                wp_localize_script( 'wpcalendars-admin-builder', 'WPCalendarsAdmin', self::admin_script_args() );
                wp_localize_script( 'wpcalendars-admin-preview', 'WPCalendars', self::frontend_script_args() );
            } elseif ( 'wpcalendars_event_page_wpcalendars-settings' === $screen->base ) {
                wp_enqueue_script( 'jquery-select2' );
                wp_enqueue_script( 'wpcalendars-admin-settings' );

                wp_enqueue_style( 'jquery-select2' );
                wp_enqueue_style( 'wpcalendars-admin-settings' );
            } elseif ( 'wpcalendars_event_page_wpcalendars-tools' === $screen->base ) { 
            } elseif ( 'wpcalendars_event_page_wpcalendars-category' === $screen->base ) {
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'wpcalendars-admin-category' );

                wp_enqueue_style( 'wpcalendars-admin-category' );
                
                wp_localize_script( 'wpcalendars-admin-category', 'WPCalendarsAdmin', self::admin_script_args() );
            } elseif ( 'post' === $screen->base ) {
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_script( 'wpcalendars-admin-event' );

                wp_enqueue_style( 'jquery-ui' );
                wp_enqueue_style( 'datepicker' );
                wp_enqueue_style( 'wpcalendars-admin-event' );

                wp_localize_script( 'wpcalendars-admin-event', 'WPCalendarsAdmin', self::admin_script_args() );
            }
        } elseif ( isset( $screen->post_type ) && 'wpcalendars_venue' === $screen->post_type ) {
            if ( 'google' === wpcalendars_settings_value( 'general', 'map_provider' ) ) {
                wp_enqueue_script( 'google-maps-api' );
                wp_enqueue_script( 'wpcalendars-admin-google-maps' );
            }
            
            wp_enqueue_script( 'magnific-popup' );
            wp_enqueue_script( 'wpcalendars-admin-venue' );
            
            wp_enqueue_style( 'magnific-popup' );
            wp_enqueue_style( 'wpcalendars-admin-venue' );
            
            wp_localize_script( 'wpcalendars-admin-venue', 'WPCalendarsAdmin', self::admin_script_args() );
        } elseif ( isset( $screen->base ) && 'dashboard_page_wpcalendars-welcome' === $screen->base ) {
            wp_enqueue_style( 'wpcalendars-admin-welcome' );
        }
    }
    
    /**
     * Load frontend stylesheet dan scripts
     * @global array $post object
     */
    public function enqueue_scripts() {
        global $post;
        
        wp_register_script( 'tooltipster',    WPCALENDARS_PLUGIN_URL . 'assets/js/tooltipster.bundle.min.js', array( 'jquery' ) );
        wp_register_script( 'magnific-popup', WPCALENDARS_PLUGIN_URL . 'assets/js/jquery.magnific-popup.min.js', array( 'jquery' ) );
        wp_register_script( 'wpcalendars',    WPCALENDARS_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery', 'magnific-popup', 'tooltipster' ) );
        
        wp_register_style( 'tooltipster',            WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster.bundle.min.css' );
        wp_register_style( 'tooltipster-borderless', WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-borderless.min.css' );
        wp_register_style( 'tooltipster-light',      WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-light.min.css' );
        wp_register_style( 'tooltipster-noir',       WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-noir.min.css' );
        wp_register_style( 'tooltipster-punk',       WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-punk.min.css' );
        wp_register_style( 'tooltipster-shadow',     WPCALENDARS_PLUGIN_URL . 'assets/css/tooltipster-sideTip-shadow.min.css' );
        wp_register_style( 'magnific-popup',         WPCALENDARS_PLUGIN_URL . 'assets/css/magnific-popup.css' );
        wp_register_style( 'wpcalendars',            WPCALENDARS_PLUGIN_URL . 'assets/css/frontend.css', array( 'magnific-popup', 'tooltipster', 'tooltipster-punk' ) );
        wp_register_style( 'wpcalendars-colors',     home_url( '/wpcalendars-colors.css' ), array( 'wpcalendars' ) );
        
        wp_enqueue_script( 'tooltipster' );
        wp_enqueue_script( 'magnific-popup' );
        wp_enqueue_script( 'wpcalendars' );
        
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'tooltipster' );
        wp_enqueue_style( 'tooltipster-borderless' );
        wp_enqueue_style( 'tooltipster-light' );
        wp_enqueue_style( 'tooltipster-noir' );
        wp_enqueue_style( 'tooltipster-punk' );
        wp_enqueue_style( 'tooltipster-shadow' );
        wp_enqueue_style( 'magnific-popup' );
        wp_enqueue_style( 'wpcalendars' );
        wp_enqueue_style( 'wpcalendars-colors' );

        wp_localize_script( 'wpcalendars', 'WPCalendars', self::frontend_script_args() );
    }
    
    /**
     * Add loading panel into the footer
     */
    public function loading_panel() {
        echo '<div id="wpcalendars-loading-panel" class="wpcalendars-loading-panel mfp-hide">' . __( 'Loading', 'wpcalendars' ) . '</div>';
    }
    
    /**
     * Add event popup panel into the footer
     */
    public function event_popup_panel() {
        echo '<div id="wpcalendars-popup-panel" class="wpcalendars-popup-panel mfp-hide"></div>';
    }
    
}

WPCalendars::instance();

endif;
