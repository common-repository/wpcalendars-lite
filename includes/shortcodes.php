<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Shortcodes {

    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_shortcode( 'wpcalendars', array( $this, 'add_shortcode' ) );
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
     * Shortcode wrapper for the outputting a calendar.
     * @param array $attributes
     * @return string
     */
    public function add_shortcode( $attributes ) {
        $defaults = array(
            'id' => ''
        );
        
        $attributes = shortcode_atts( $defaults, $attributes );
        
        return wpcalendars_get_calendar_output( $attributes['id'] );
    }
    
}

WPCalendars_Shortcodes::instance();