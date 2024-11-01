<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Tools {

    private static $_instance = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
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
     * Add Settings menu page
     */
    public function admin_menu() {
        add_submenu_page( 'edit.php?post_type=wpcalendars_event', __( 'WPCalendars Tools', 'wpcalendars' ), __( 'Tools', 'wpcalendars' ), 'manage_options', 'wpcalendars-tools', array( $this, 'tools_page' ) );
    }
    
    /**
     * Template of tools page
     */
    public function tools_page() {
        $install_sample_status = get_option( 'wpcalendars_install_sample_calendar', false );
        $calendar_types = wpcalendars_get_calendar_type_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'WPCalendars Tools', 'wpcalendars' );?></h1>
            <h2 class="title"><?php echo esc_html__( 'Install Sample Calendars', 'wpcalendars' );?></h2>
            
            <?php if ( $install_sample_status ): ?>
            <p><?php echo esc_html__( 'You already have installed sample calendars.', 'wpcalendars' ) ?></p>
            <form method="POST">
                <?php wp_nonce_field( 'remove_sample_calendar_action' ); ?>
                <input type="hidden" name="action" value="remove-sample-calendar">
                <a href="<?php echo get_permalink( get_option( 'wpcalendars_sample_calendar_page' ) ) ?>" class="button button-primary"><?php echo esc_html__( 'Show Calendars', 'wpcalendars' ); ?></a>
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Remove Calendars', 'wpcalendars' ); ?></button>
            </form>
            
            <?php else: ?>
            
            <p><?php echo esc_html__( 'The following wizard will help you configure your sample calendar and get you started quickly.', 'wpcalendars' ) ?></p>
            <form method="POST">
                <?php wp_nonce_field( 'install_sample_calendar_action' ); ?>
                <input type="hidden" name="action" value="install-sample-calendar">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__( 'Sample Page', 'wpcalendars' );?></th>
                        <td class="forminp">
                            <?php wp_dropdown_pages( array(
                                'name'             => 'sample_page',
                                'show_option_none' => sprintf( '&mdash; %s &mdash;', __( 'Select Page', 'wpcalendars' ) ),
                            ) ) ?>
                            <p class="description"><?php echo esc_html__( 'This is the page where the sample calendar will be shown. Note, the whole content on the existing page will be removed. If you do not select any page, the new page will be created for you.', 'wpcalendars' ) ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__( 'Calendar Type', 'wpcalendars' );?></th>
                        <td class="forminp">
                            <select name="calendar_type">
                                <option value="">&mdash; <?php echo esc_html__( 'Select Type', 'wpcalendars' ) ?> &mdash;</option>
                                <?php foreach ( $calendar_types as $type => $detail ): ?>
                                <option value="<?php echo $type ?>"><?php echo $detail['name'] ?></option>
                                <?php endforeach ?>
                            </select>
                            <p class="description"><?php echo esc_html__( 'Choose the calendar type that you want to install. If you do not select any type, the sample calendar with all calendar types will be created for you.', 'wpcalendars' ) ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary"><?php echo esc_html__( 'Continue', 'wpcalendars' ); ?></button></p>
            </form>
            
            <?php endif ?>
        </div>
        <?php
    }
    
    /**
     * Admin action initialization
     */
    public function admin_init() {
        $sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action' ), wp_get_referer() );
        
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-tools' === $_REQUEST['page'] ) {
            if ( ! empty( $_POST['action'] ) ) {
                if ( 'install-sample-calendar' === $_POST['action'] ) {
                    $result = $this->process_install_sample_calendar();
                } elseif ( 'remove-sample-calendar' === $_POST['action'] ) {
                    $result = $this->process_remove_sample_calendar();
                }
                
                wp_redirect( $sendback );
                exit;
            }
        }
    }
    
    /**
     * Process install sample calendar
     */
    private function process_install_sample_calendar() {
        check_admin_referer( 'install_sample_calendar_action' );
        
        $page_id       = empty( $_POST['sample_page'] ) ? 0 : intval( $_POST['sample_page'] );
        $calendar_type = empty( $_POST['calendar_type'] ) ? '' : sanitize_text_field( $_POST['calendar_type'] );
        
        $default_settings      = wpcalendars_get_default_calendar_settings();
        $calendar_type_options = wpcalendars_get_calendar_type_options();
        
        $page_content = '';
        $tab_menus    = '';
        $calendar_ids = array();
        
        $counter = 0;
        
        if ( '' === $calendar_type ) {
            foreach ( $calendar_type_options as $type => $option ) {
                $calendar_name = sprintf( __( 'Sample %s', 'wpcalendars' ), $option['name'] );
                
                $args = array(
                    'name'     => $calendar_name,
                    'type'     => $type,
                    'settings' => $default_settings[$type]
                );

                $calendar_ids[] = $calendar_id = wpcalendars_add_new_calendar( $args );
                
                $style = $counter > 0 ? 'display:none;' : '';
                $current_menu = $counter > 0 ? '' : 'wpcalendars-sample-menu-current';
                
                $tab_menus    .= sprintf( '<a class="wpcalendars-sample-menu %s" data-type="wpcalendars-sample-calendar-%s" href="#">%s</a>', $current_menu, $type, $option['name'] );
                $page_content .= sprintf( '<div style="%s" class="wpcalendars-sample-calendar wpcalendars-sample-calendar-%s">[wpcalendars id="%s"]</div>', $style, $type, $calendar_id );
                
                $counter++;
            }
        } else {
            $calendar_name = sprintf( __( 'Sample %s', 'wpcalendars' ), $calendar_type_options[$calendar_type]['name'] );
            
            $args = array(
                'name'     => $calendar_name,
                'type'     => $calendar_type,
                'settings' => $default_settings[$calendar_type]
            );

            $calendar_ids[] = $calendar_id = wpcalendars_add_new_calendar( $args );
            $page_content .= sprintf( '<div class="wpcalendars-sample-calendar wpcalendars-sample-calendar-%s">[wpcalendars id="%s"]</div>', $type, $calendar_id );
        }
        
        update_option( 'wpcalendars_sample_calendars', $calendar_ids );
        
        $page_content = empty( $tab_menus ) ? $page_content : sprintf( '<p>%s</p>%s', $tab_menus, $page_content );
                
        if ( $page_id === 0 ) {
            $page_id = wp_insert_post( array( 
                'post_type'      => 'page',
                'post_title'     => __( 'Sample Calendar', 'wpcalendars' ),
                'post_content'   => $page_content,
                'post_status'    => 'publish',
                'comment_status' => 'closed',
                'ping_status'    => 'closed'
            ) );
        } else {
            wp_update_post( array(
                'ID'           => $page_id,
                'post_content' => $page_content,
            ) );
        }
        
        update_option( 'wpcalendars_sample_calendar_page', $page_id );
        
        $sample_categories = require WPCALENDARS_PLUGIN_DIR . 'sample-data/categories.php';
        
        $category_ids = array();
        
        foreach ( $sample_categories as $id => $category ) {
            $args = array(
                'name'    => $category['name'],
                'bgcolor' => $category['bgcolor'],
            );
            
            $category_ids[$id] = wpcalendars_setup_sample_category( $args );
        }
        
        update_option( 'wpcalendars_sample_categories', $category_ids );
        
        $sample_venues = require WPCALENDARS_PLUGIN_DIR . 'sample-data/venues.php';
        
        $venue_ids = array();
        
        foreach ( $sample_venues as $id => $venue ) {
            $args = array(
                'name'        => $venue['name'],
                'address'     => $venue['address'],
                'city'        => $venue['city'],
                'country'     => $venue['country'],
                'state'       => $venue['state'],
                'postal_code' => $venue['postal_code'],
                'phone'       => $venue['phone'],
                'email'       => $venue['email'],
                'website'     => $venue['website'],
                'latitude'    => $venue['latitude'],
                'longitude'   => $venue['longitude'],
                'place_id'    => $venue['place_id']
            );
            
            $venue_ids[$id] = wpcalendars_setup_sample_venue( $args );
        }
        
        update_option( 'wpcalendars_sample_venues', $venue_ids );
        
        $sample_organizers = require WPCALENDARS_PLUGIN_DIR . 'sample-data/organizers.php';
        
        $organizer_ids = array();
        
        foreach ( $sample_organizers as $id => $organizer ) {
            $args = array(
                'name'    => $organizer['name'],
                'phone'   => $organizer['phone'],
                'email'   => $organizer['email'],
                'website' => $organizer['website'],
            );
            
            $organizer_ids[$id] = wpcalendars_setup_sample_organizer( $args );
        }
        
        update_option( 'wpcalendars_sample_organizers', $organizer_ids );
        
        $sample_events = require WPCALENDARS_PLUGIN_DIR . 'sample-data/events.php';
        
        $sample_categories = get_option( 'wpcalendars_sample_categories' );
        $sample_venues     = get_option( 'wpcalendars_sample_venues' );
        $sample_organizers = get_option( 'wpcalendars_sample_organizers' );
        
        $event_ids = array();
        
        foreach ( $sample_events as $id => $event ) {
            $args = array(
                'name'                  => $event['name'],
                'start_date'            => $event['start_date'],
                'start_time'            => $event['start_time'],
                'end_date'              => $event['end_date'],
                'end_time'              => $event['end_time'],
                'all_day'               => $event['all_day'],
                'disable_event_details' => $event['disable_event_details'],
                'hide_event_listings'   => $event['hide_event_listings'],
                'website'               => $event['website'],
                'category_id'           => isset( $sample_categories[$event['category_id']] ) ? $sample_categories[$event['category_id']] : '',
                'venue_id'              => isset( $sample_venues[$event['venue_id']] ) ? $sample_venues[$event['venue_id']] : '',
                'organizer_id'          => isset( $sample_organizers[$event['organizer_id']] ) ? $sample_organizers[$event['organizer_id']] : '',
            );

            $event_ids[] = wpcalendars_setup_sample_event( $args );
        }
        
        update_option( 'wpcalendars_sample_events', $event_ids );
        update_option( 'wpcalendars_install_sample_calendar', true );
    }
    
    /**
     * Process remove sample calendar
     * @global type $wpdb
     */
    private function process_remove_sample_calendar() {
        global $wpdb;
        
        check_admin_referer( 'remove_sample_calendar_action' );
        
        $sample_calendar_page = get_option( 'wpcalendars_sample_calendar_page' );
        $sample_calendars     = get_option( 'wpcalendars_sample_calendars' );
        $sample_categories    = get_option( 'wpcalendars_sample_categories' );
        $sample_venues        = get_option( 'wpcalendars_sample_venues' );
        $sample_organizers    = get_option( 'wpcalendars_sample_organizers' );
        $sample_events        = get_option( 'wpcalendars_sample_events' );
        
        // Delete calendar page
        
        wp_delete_post( intval( $sample_calendar_page ), true );
        
        // Delete sample calendars
        
        foreach ( $sample_calendars as $calendar_id ) {
            wp_delete_post( intval( $calendar_id ), true );
        }
        
        // Delete sample categories
        
        foreach ( $sample_categories as $category_id ) {
            wp_delete_post( intval( $category_id ), true );
        }
        
        // Delete sample venues
        
        foreach ( $sample_venues as $venue_id ) {
            wp_delete_post( intval( $venue_id ), true );
        }
        
        // Delete sample organizers
        
        foreach ( $sample_organizers as $organizer_id ) {
            wp_delete_post( intval( $organizer_id ), true );
        }
        
        // Delete sample events
        
        $table_name = $wpdb->prefix . 'wpcalendars_events';
        
        foreach ( $sample_events as $event_id ) {
            wp_delete_post( intval( $event_id ), true );
            $wpdb->delete( $table_name, array( 'event_id' => $event_id ), array( '%d' ) );
        }
        
        delete_option( 'wpcalendars_sample_calendar_page' );
        delete_option( 'wpcalendars_sample_calendars' );
        delete_option( 'wpcalendars_sample_categories' );
        delete_option( 'wpcalendars_sample_venues' );
        delete_option( 'wpcalendars_sample_organizers' );
        delete_option( 'wpcalendars_sample_events' );
        delete_option( 'wpcalendars_install_sample_calendar' );
    }
}

WPCalendars_Tools::instance();
