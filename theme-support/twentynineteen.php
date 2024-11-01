<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Theme_Support_Twenty_Nineteen {

    private static $_instance = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_theme_support( 'wpcalendars' );
        
        remove_action( 'wpcalendars_before_main_content', array( WPCalendars_Templates::instance(), 'output_content_wrapper' ) );
        remove_action( 'wpcalendars_after_main_content',  array( WPCalendars_Templates::instance(), 'output_content_wrapper_end' ) );
        remove_action( 'wpcalendars_sidebar',             array( WPCalendars_Templates::instance(), 'sidebar' ) );

        add_action( 'wpcalendars_before_main_content', array( $this, 'output_content_wrapper' ) );
        add_action( 'wpcalendars_after_main_content',  array( $this, 'output_content_wrapper_end' ) );
        add_action( 'wpcalendars_single_event',        array( $this, 'output_entry_header_wrapper' ), 5 );
        add_action( 'wpcalendars_single_event',        array( $this, 'output_entry_header_wrapper_end' ), 15 );
        add_action( 'wpcalendars_single_event',        array( $this, 'output_entry_content_wrapper' ), 15 );
        add_action( 'wpcalendars_single_event',        array( $this, 'output_entry_content_wrapper_end' ), 999 );
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
     * Display content wrapper
     */
    public function output_content_wrapper() {
        echo '<section id="primary" class="content-area">';
        echo '<main id="main" class="site-main">';
    }
    
    /**
     * Display content wrapper end
     */
    public function output_content_wrapper_end() {
        echo '</main></section';
    }
    
    /**
     * Display entry header wrapper
     */
    public function output_entry_header_wrapper() {
        echo '<header class="entry-header">';
    }
    
    /**
     * Display entry header wrapper end
     */
    public function output_entry_header_wrapper_end() {
        echo '</header>';
    }
    
    /**
     * Display entry content wrapper
     */
    public function output_entry_content_wrapper() {
        echo '<div class="entry-content" itemprop="text">';
    }
    
    /**
     * Display entry content wrapper end
     */
    public function output_entry_content_wrapper_end() {
        echo '</div>';
    }
}

WPCalendars_Theme_Support_Twenty_Nineteen::instance();
