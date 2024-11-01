<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Lite {
    
    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        require_once WPCALENDARS_PLUGIN_DIR . 'lite/includes/builder.php';
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_filter( 'posts_clauses',         array( $this, 'posts_clauses' ), 20, 2 );
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
     * Load admin stylesheet
     * @param type $hook
     */
    public function admin_enqueue_scripts( $hook ) {
        $screen = get_current_screen();
        
        if ( isset( $screen->post_type ) && 'wpcalendars_event' === $screen->post_type ) {
            wp_enqueue_style( 'wpcalendars-admin-lite', WPCALENDARS_PLUGIN_URL . 'lite/assets/css/admin.css' );
        }
    }

    /**
     * Modify posts SQL clause
     * @global type $wpdb
     * @global type $typenow
     * @global type $pagenow
     * @param string $clauses
     * @param type $query
     * @return string
     */
    public function posts_clauses( $clauses, $query ) {
        global $wpdb, $typenow, $pagenow;

        if ( 'wpcalendars_event' === $typenow ) {
            if ( $pagenow == 'edit.php' ) {
                $clauses['where'] .= " AND b.event_type = 'no-repeat' ";
            }
        }
        
        return $clauses;
    }
    
}

WPCalendars_Lite::instance();
