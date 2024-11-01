<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Install {
    
    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        register_activation_hook( WPCALENDARS_PLUGIN_FILE, array( $this, 'install' ) );
        add_action( 'admin_notices',             array( $this, 'upgrade_notices' ) );
        add_action( 'admin_init',                array( $this, 'upgrade_db' ) );
        add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );
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
     * Create event table
     * @global array $wpdb object
     */
    public function run_install() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpcalendars_events';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            event_parent bigint(20) DEFAULT 0 NOT NULL,
            event_type varchar(20) DEFAULT 'no-repeat' NOT NULL,
            start_date date DEFAULT '0000-00-00' NOT NULL,
            end_date date DEFAULT '0000-00-00' NOT NULL,
            start_time time DEFAULT '00:00:00' NOT NULL,
            end_time time DEFAULT '00:00:00' NOT NULL,
            all_day varchar(1),
            category_id bigint(20),
            venue_id bigint(20),
            organizer_id bigint(20),
            hide_event_listings varchar(1),
            disable_event_details varchar(1),
            website varchar(200),
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Run installation script
     * @param boolean $network_wide
     */
    public function install( $network_wide = false ) {
        if ( is_multisite() && $network_wide ) {
            $sites = get_sites();
            
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                $this->run_install();
                restore_current_blog();
            }
        } else {
            $this->run_install();
        }
    }
    
    /**
     * Display upgrade notice
     * @return type
     */
    public function upgrade_notices() {
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-welcome' === $_REQUEST['page'] ) {
            return;
        }
        
        $wpcalendars_version = get_option( 'wpcalendars_version', '1.0.0' );
        
        if ( version_compare( $wpcalendars_version, WPCALENDARS_PLUGIN_VERSION, '<' ) ) {
            echo '<div class="notice notice-info"><p>';
            printf( __( 'Thanks for updating WPCalendars. <a href="%s">Click here</a> to complete!', 'wpcalendars' ), wp_nonce_url( admin_url( 'admin.php?page=wpcalendars-overview&upgrade=1' ), 'wpcalendars_upgrade' ) );
            echo '</p></div>';
        }
    }
    
    /**
     * Process upgrade database
     */
    public function upgrade_db() {
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-overview' === $_REQUEST['page'] && isset( $_REQUEST['upgrade'] ) ) {
            $sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'upgrade' ), wp_get_referer() );
            check_admin_referer( 'wpcalendars_upgrade' );
            wpcalendars_upgrade105();
            wpcalendars_upgrade12();
            update_option( 'wpcalendars_version', WPCALENDARS_PLUGIN_VERSION );
            flush_rewrite_rules();
            wp_redirect( $sendback );
            exit;
        }
    }
    
    /**
     * Execute the script when the upgrade process complete
     * 
     * @param type $upgrader_object
     * @param type $options
     */
    public function upgrader_process_complete( $upgrader_object, $options ) {
        if ( $options['action'] === 'update' && $options['type'] === 'plugin' && isset( $options['plugins'] ) ) {
            foreach ( $options['plugins'] as $each_plugin ) {
                if ( $each_plugin === WPCALENDARS_PLUGIN_BASENAME ) {
                    wpcalendars_upgrade105();
                    wpcalendars_upgrade12();
                    update_option( 'wpcalendars_version', WPCALENDARS_PLUGIN_VERSION );
                    flush_rewrite_rules();
                }
            }
        }
    }
}

WPCalendars_Install::instance();