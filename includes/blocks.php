<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Blocks {
    
    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'init',                        array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
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
     * Load Gutenberg editor stylesheets and scripts
     */
    public function editor_assets() {
        wp_enqueue_script( 'wpcalendars-block', WPCALENDARS_PLUGIN_URL . 'assets/js/block.js', array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components' ) );
        
        wp_localize_script( 'wpcalendars-block', 'wpcalendars', array(
            'calendars' => wpcalendars_options_for_gutenberg_block()
        ) );
        
        wp_register_style( 'magnific-popup',     WPCALENDARS_PLUGIN_URL . 'assets/css/magnific-popup.css' );
        wp_register_style( 'wpcalendars',        WPCALENDARS_PLUGIN_URL . 'assets/css/frontend.css', array( 'magnific-popup', 'tooltipster', 'tooltipster-punk' ) );
        wp_register_style( 'wpcalendars-colors', home_url( '/wpcalendars-colors.css' ), array( 'wpcalendars' ) );
        
        wp_enqueue_style( 'magnific-popup' );
        wp_enqueue_style( 'wpcalendars' );
        wp_enqueue_style( 'wpcalendars-colors' );
    }
    
    /**
     * Register Gutenberg block
     */
    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) { // Gutenberg is not active
            return;
        }
        
        register_block_type( 'wpcalendars/wpcalendars', array(
            'attributes' => array(
                'id' => array(
                    'type' => 'string'
                )
            ),
            'render_callback' => array( $this, 'calendar_output' ),
        ) );
        
    }
    
    /**
     * Display Gutenberg block output
     * @param array $attributes
     * @return string
     */
    public function calendar_output( $attributes ) {
        $calendars = wpcalendars_get_calendars();
        
        if ( count( $calendars ) > 0 ) {
            if ( empty( $attributes['id'] ) ) {
                return sprintf( '<p class="">%s</p>', esc_html__( 'Please select the calendar from the list.', 'wpcalendars' ) );
            }
        } else {
            return sprintf( '<p class="">%s</p>', esc_html__( 'There is no event calendar on your WordPress.', 'wpcalendars' ) );
        }
        
        return wpcalendars_get_calendar_output( $attributes['id'] );
    }
    
}

WPCalendars_Blocks::instance();