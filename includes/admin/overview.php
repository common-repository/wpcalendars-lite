<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Overview {

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
        add_submenu_page( 'edit.php?post_type=wpcalendars_event', __( 'Event Calendars', 'wpcalendars' ), __( 'Calendars', 'wpcalendars' ), 'manage_options', 'wpcalendars-overview', array( $this, 'admin_page' ) );
    }
    
    /**
     * Template of settings page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__( 'Calendar Overview', 'wpcalendars' );?>
            <a class="page-title-action wpcalendars-add-calendar" href="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-builder&section=setup' ) ?>"><?php echo esc_html__( 'Add New', 'wpcalendars' ); ?></a>
            </h1>
            <hr class="wp-header-end">
            <?php $this->table(); ?>
        </div>
        <?php
    }
    
    /**
     * Setting initialization
     */
    public function admin_init() {
        $sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action' ), wp_get_referer() );
        
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-overview' === $_REQUEST['page'] ) {
            if ( ! empty( $_GET['action'] ) && '-1' !== $_GET['action'] ) {
                if ( 'delete' === $_GET['action'] ) {
                    $result = $this->process_delete_calendar();
                } elseif ( 'delete-selected' === $_GET['action'] ) {
                    $result = $this->process_bulk_delete_calendar();
                } elseif ( 'duplicate' === $_GET['action'] ) {
                    $result = $this->process_duplicate_calendar();
                }
                
                wp_redirect( $sendback );
                exit;
            }
        }
    }
    
    /**
     * Delete calendar
     */
    public function process_delete_calendar() {
        $calendar_id = intval( $_GET['calendar_id'] );
        check_admin_referer( 'calendar_delete-' . $calendar_id );
        wpcalendars_delete_calendar( $calendar_id );
    }
    
    /**
     * Bulk delete calendar
     */
    public function process_bulk_delete_calendar() {
        $calendars = $_GET['calendar'];
        check_admin_referer( 'calendar_action' );
        
        foreach ( $calendars as $calendar_id ) {
            wpcalendars_delete_calendar( intval( $calendar_id ) );
        }
    }
    
    /**
     * Duplicate calendar
     */
    public function process_duplicate_calendar() {
        $calendar_id = intval( $_GET['calendar_id'] );
        check_admin_referer( 'calendar_duplicate-' . $calendar_id );
        wpcalendars_duplicate_calendar( $calendar_id );
    }
    
    /**
     * Display table list of calendar
     */
    public function table() {
        $calendars = wpcalendars_get_calendars();
        $calendar_types = wpcalendars_get_calendar_type_options();
		?>
        <form method="get">
            <?php wp_nonce_field( 'calendar_action' ); ?>
            <input type="hidden" name="page" value="wpcalendars-overview">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php echo esc_html__( 'Bulk Action', 'wpcalendars' ) ?></option>
                        <option value="delete-selected"><?php echo esc_html__( 'Delete', 'wpcalendars' ) ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php echo esc_html__( 'Apply', 'wpcalendars' ) ?>">
                </div>
            </div>
            <table class="wp-list-table widefat pages">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column"><input id="cb-select-all" type="checkbox"></td>
                        <th scope="col" class="manage-column column-name column-primary"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></th>
                        <th scope="col" class="manage-column column-name column-type"><?php echo esc_html__( 'Type', 'wpcalendars' ); ?></th>
                        <th scope="col" class="manage-column column-shortcode"><?php echo esc_html__( 'Shortcode', 'wpcalendars' ); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column"><input id="cb-select-all" type="checkbox"></td>
                        <th scope="col" class="manage-column column-name column-primary"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></th>
                        <th scope="col" class="manage-column column-name column-type"><?php echo esc_html__( 'Type', 'wpcalendars' ); ?></th>
                        <th scope="col" class="manage-column column-shortcode"><?php echo esc_html__( 'Shortcode', 'wpcalendars' ); ?></th>
                    </tr>
                </tfoot>
                <tbody id="the-list" class="wpcalendars-list-table">	
                    <?php if ( empty( $calendars ) ): ?>
                    <tr><td colspan="4"><?php echo esc_html__( 'No calendars found', 'wpcalendars' ) ?></td></tr>
                    <?php else: ?>
                    <?php foreach ( $calendars as $calendar ): ?>
                    <tr class="inactive">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="calendar[]" value="<?php echo $calendar['calendar_id'] ?>">
                        </th>
                        <td class="plugin-title column-primary">
                            <a href="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-builder&section=general&calendar_id=' . esc_html( $calendar['calendar_id'] ) ); ?>">
                                <strong><?php echo esc_html( $calendar['name'] ) ?></strong>
                            </a>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-builder&section=general&calendar_id=' . esc_html( $calendar['calendar_id'] ) ); ?>"><?php echo esc_html__( 'Edit', 'wpcalendars' ); ?></a></span>
                                <span class="duplicate">| <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-overview&action=duplicate&calendar_id=' . esc_html( $calendar['calendar_id'] ) ), 'calendar_duplicate-' . $calendar['calendar_id'] ); ?>"><?php echo esc_html__( 'Duplicate', 'wpcalendars' ); ?></a></span>
                                <span class="delete"> | <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-overview&action=delete&calendar_id=' . esc_html( $calendar['calendar_id'] ) ), 'calendar_delete-' . $calendar['calendar_id'] ); ?>"><?php echo esc_html__( 'Delete', 'wpcalendars' ); ?></a></span> 
                            </div>
                        </td>
                        <td class="column-type"><?php echo $calendar_types[$calendar['type']]['name'] ?></td>
                        <td class="column-shortcode"><?php printf( '[wpcalendars id="%s"]', $calendar['calendar_id'] ); ?></td>
                    </tr>
                    <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
        </form>
        <?php
    }
}

WPCalendars_Overview::instance();
