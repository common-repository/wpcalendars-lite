<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Builder {

    private static $_instance = NULL;
    
    private $section       = NULL;
    private $save_state    = 'Y';
    private $action        = 'add_new';
    private $calendar      = array();
    private $calendar_id   = '';
    private $calendar_name = '';
    private $calendar_type = '';

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        $this->section     = isset( $_REQUEST['section'] ) ? sanitize_key( $_REQUEST['section'] ) : 'setup';
        $this->calendar_id = isset( $_REQUEST['calendar_id'] ) ? intval( $_REQUEST['calendar_id'] ) : '';
        
        if ( !empty( $this->calendar_id ) ) {
            $this->calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( $this->calendar_id ), $this->calendar_id );
            
            if ( $this->calendar ) {
                $this->action        = 'save';
                $this->calendar_name = $this->calendar['name'];
                $this->calendar_type = $this->calendar['type'];
            } else {
                $this->calendar_id = '';
                $this->calendar    = array();
            }
        }
        
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_head', array( $this, 'hide_menu' ) );
        
        add_action( 'wpcalendars_builder_save_calendar', array( $this, 'save_settings' ) );
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
        add_submenu_page( 'edit.php?post_type=wpcalendars_event', __( 'Calendars Builder', 'wpcalendars' ), __( 'Add New Calendar', 'wpcalendars' ), 'manage_options', 'wpcalendars-builder', array( $this, 'admin_page' ) );
    }
    
    /**
     * Hide editor builder menu
     */
    public function hide_menu() {
        remove_submenu_page( 'edit.php?post_type=wpcalendars_event', 'wpcalendars-builder' );
	}
    
    /**
     * Template of settings page
     */
    public function admin_page() {
        $menus = apply_filters( 'wpcalendars_builder_navigation_menu_options', array(
            'setup' => array( 
                'name' => __( 'Setup', 'wpcalendars' ),
                'icon' => 'dashicons-admin-generic'
            ),
            'general' => array( 
                'name' => __( 'General', 'wpcalendars' ),
                'icon' => 'dashicons-admin-settings'
            ),
            'navigation' => array( 
                'name' => __( 'Navigation', 'wpcalendars' ),
                'icon' => 'dashicons-admin-links'
            ),
            'events' => array( 
                'name' => __( 'Events', 'wpcalendars' ),
                'icon' => 'dashicons-schedule'
            ),
        ) );
        ?>
        <div class="wrap">
            <?php do_action( 'wpcalendars_builder_before' ) ?>
            <div id="wpcalendars-loading-panel" class="wpcalendars-loading-panel mfp-hide"><?php echo esc_html__( 'Loading', 'wpcalendars' ) ?></div>
            <div id="wpcalendars-builder-change-type-panel" class="wpcalendars-builder-panel mfp-hide">
                <input id="wpcalendars-new-calendar-name" type="hidden" value="">
                <input id="wpcalendars-new-calendar-type" type="hidden" value="">
                <input id="wpcalendars-new-calendar-type-name" type="hidden" value="">
                <div class="wpcalendars-builder-panel-instruction"><?php echo esc_html__( 'Changing type on an existing calendar will DELETE existing settings. Are you sure you want apply the new calendar type?', 'wpcalendars' ) ?></div>
                <div class="wpcalendars-builder-panel-btn">
                    <button id="wpcalendars-builder-change-type-panel-btn-yes"><?php echo esc_html__( 'Yes', 'wpcalendars' ) ?></button>
                    <button id="wpcalendars-builder-change-type-panel-btn-no" class="active"><?php echo esc_html__( 'No', 'wpcalendars' ) ?></button>
                </div>
            </div>
            <div id="wpcalendars-builder-shortcode-panel" class="wpcalendars-builder-panel mfp-hide">
                <div class="wpcalendars-builder-panel-instruction"><?php echo esc_html__( 'To embed this calendar on your site, please paste the following shortcode inside a post or page.', 'wpcalendars' ) ?></div>
                <div class="wpcalendars-builder-panel-code"><?php printf( '[wpcalendars id="%s"]', $this->calendar_id ) ?></div>
                <div class="wpcalendars-builder-panel-btn"><button id="wpcalendars-builder-shortcode-panel-btn-close" class="active"><?php echo esc_html__( 'Close', 'wpcalendars' ) ?></button></div>
            </div>
            <div id="wpcalendars-builder-exit-panel" class="wpcalendars-builder-panel mfp-hide">
                <div class="wpcalendars-builder-panel-instruction"><?php echo esc_html__( 'If you exit without saving, your changes will be lost.', 'wpcalendars' ) ?></div>
                <div class="wpcalendars-builder-panel-btn">
                    <button id="wpcalendars-builder-exit-panel-btn-cancel"><?php echo esc_html__( 'Cancel', 'wpcalendars' ) ?></button>
                    <button id="wpcalendars-builder-exit-panel-btn-exit" class="active"><?php echo esc_html__( 'Exit', 'wpcalendars' ) ?></button>
                </div>
            </div>
            <div class="wpcalendars-builder">
                <div class="wpcalendars-toolbar-wrap">
                    <div class="wpcalendars-toolbar">
                        <div class="wpcalendars-toolbar-left"><img src="<?php echo WPCALENDARS_PLUGIN_URL . 'assets/images/wpcalendars.png' ?>" alt="WPCalendars"></div>
                        <?php $visible = ( 'setup' === $this->section && '' === $this->calendar_id ) ? '' : ' visible' ?>
                        <div class="wpcalendars-toolbar-center<?php echo $visible ?>"><?php echo esc_html__( 'You are customizing', 'wpcalendars' ) ?> <span class="wpcalendars-calendar-name"><?php echo $this->calendar_name ?></span></div>
                        <div class="wpcalendars-toolbar-right">
                            <a class="wpcalendars-toolbar-button<?php echo $visible ?>" href="#" id="wpcalendars-get-shortcode"><?php echo esc_html__( 'Get Shortcode', 'wpcalendars' ) ?></a>
                            <a class="wpcalendars-toolbar-button<?php echo $visible ?>" href="#" id="wpcalendars-save"><?php echo esc_html__( 'Save Changes', 'wpcalendars' ) ?></a>
                            <a href="#" id="wpcalendars-exit"><i class="dashicons dashicons-no"></i></a>
                        </div>
                    </div>
                </div>
                <div class="wpcalendars-menu-wrap">
                    <div class="wpcalendars-menu">
                        <?php $disabled = '' === $this->calendar_id ? ' disabled="disabled"' : '' ?>
                        <?php foreach ( $menus as $key => $menu ): ?>
                        <?php $active = $key === $this->section ? ' active' : '' ?>
                        <button<?php echo $disabled ?> class="<?php printf( 'wpcalendars-section-%s-button', $key ) ?><?php echo $active ?>" data-section="<?php echo $key ?>"><i class="dashicons <?php echo $menu['icon'] ?>"></i><span><?php echo esc_html( $menu['name'] ) ?></span></button>
                        <?php endforeach ?>
                    </div>
                </div>
                <form id="wpcalendars-form" method="post" action="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-builder' ); ?>">
                <?php wp_nonce_field( 'wpcalendars_builder' ); ?>
                <input id="wpcalendars-save-state" type="hidden" value="<?php echo $this->save_state ?>">
                <input id="wpcalendars-action" type="hidden" name="action" value="<?php echo $this->action ?>">
                <input id="wpcalendars-calendar-id" type="hidden" name="calendar_id" value="<?php echo $this->calendar_id ?>">
                <input id="wpcalendars-rev-calendar-id" type="hidden" name="rev_calendar_id" value="">
                <input id="wpcalendars-calendar-name" type="hidden" name="name" value="<?php echo $this->calendar_name ?>">
                <input id="wpcalendars-calendar-type" type="hidden" name="type" value="<?php echo $this->calendar_type ?>">
                <input id="wpcalendars-calendar-type-name" type="hidden" name="type_name" value="">
                <div class="wpcalendars-sidebars">
                <?php 
                if ( in_array( $this->section, array_keys( $menus ) ) ) {
                    $this->settings_general();
                    $this->settings_navigation();
                    $this->settings_events();
                    do_action( 'wpcalendars_builder_print_section', $this->section, $this->calendar_type );
                } 
                ?>
                </div>
                </form>
                <?php $this->preview() ?>
                <?php $this->setup() ?>
            
            </div>
        </div>
        <?php
    }
    
    /**
     * Display general settings
     */
    private function settings_general() {
        $sidebar_class = 'general' === $this->section ? ' active' : '';
        ?>
        <div id="wpcalendars-sidebar-general" class="wpcalendars-sidebar-wrap<?php echo $sidebar_class ?>">
            <div class="wpcalendars-sidebar">
                <?php do_action( 'wpcalendars_builder_print_sidebar_settings_general', $this->calendar_type, $this->calendar['settings'] ); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display navigation settings
     */
    private function settings_navigation() {
        $sidebar_class = 'navigation' === $this->section ? ' active' : '';
        ?>
        <div id="wpcalendars-sidebar-navigation" class="wpcalendars-sidebar-wrap<?php echo $sidebar_class ?>">
            <div class="wpcalendars-sidebar">
                <?php do_action( 'wpcalendars_builder_print_sidebar_settings_navigation', $this->calendar_type, $this->calendar['settings'] ); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display events settings
     */
    private function settings_events() {
        $sidebar_class = 'events' === $this->section ? ' active' : '';
        ?>
        <div id="wpcalendars-sidebar-events" class="wpcalendars-sidebar-wrap<?php echo $sidebar_class ?>">
            <div class="wpcalendars-sidebar">
                <?php do_action( 'wpcalendars_builder_print_sidebar_settings_events', $this->calendar_type, $this->calendar['settings'] ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display setup calendar page
     */
    private function setup() {
        $setup_class = 'setup' === $this->section ? ' active' : '';
        $calendar_types = wpcalendars_get_calendar_type_options();
        ?>
        <div class="wpcalendars-setup-wrap<?php echo $setup_class ?>">
            <div class="wpcalendars-setup">
                <div class="wpcalendars-setup-name-wrap">
                    <span><?php echo esc_html__( 'Calendar Name', 'wpcalendars' ) ?></span>
                    <input type="text" value="<?php echo $this->calendar_name ?>" id="wpcalendars-setup-name" placeholder="<?php echo esc_attr__( 'Enter your calendar name here...', 'wpcalendars' ) ?>">
                </div>
                <div class="wpcalendars-setup-select-calendar-type-heading"><?php echo esc_html__( 'Select a Calendar Type', 'wpcalendars' ) ?></div>
                <div class="wpcalendars-setup-select-calendar-type-description"><?php echo esc_html__( 'What type of calendar do you want to create?', 'wpcalendars' ) ?></div>
                <div class="wpcalendars-setup-calendar-types">
                    <?php foreach ( $calendar_types as $key => $type ): ?>
                    <?php $selected = $this->calendar_type === $key ? ' selected' : '' ?>
                    <?php $selected_label = $this->calendar_type === $key ? '<span class="selected">' . esc_html__( 'Selected', 'wpcalendars' ) . '</span>' : '' ?>
                    <div class="wpcalendars-setup-calendar-type<?php echo $selected ?>">
                        <div class="wpcalendars-setup-calendar-type-inner">
                            <div class="wpcalendars-setup-calendar-type-name"><?php echo $type['name'] ?><?php echo $selected_label ?></div>
                            <div class="wpcalendars-setup-calendar-type-description"><?php echo $type['description'] ?></div>
                            <div class="wpcalendars-setup-calendar-type-overlay">
                                <a href="#" class="wpcalendars-calendar-type-select" data-calendar-type="<?php echo $key ?>" data-calendar-type-name="<?php echo $type['name'] ?>"><?php printf( esc_html__( 'Create a %s', 'wpcalendars' ), $type['name'] ) ?></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display preview calendar builder
     */
    private function preview() {
        $preview_class = 'setup' !== $this->section ? ' active' : '';
        $calendar = wpcalendars_get_calendar_output( $this->calendar_id );
        ?>
        <div class="wpcalendars-preview-wrap<?php echo $preview_class ?>">
            <div class="wpcalendars-preview-inner">
                <div class="wpcalendars-preview wpcalendars-block">
                    <?php echo $calendar ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Setting initialization
     */
    public function admin_init() {
        $sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'section', 'calendar_id' ), wp_get_referer() );
        
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-builder' === $_REQUEST['page'] ) {
            if ( ! empty( $_POST['action'] ) && 'add_new' === $_POST['action'] ) {
                $result = $this->process_add_calendar();
                wp_redirect( add_query_arg( array( 'section' => 'general', 'calendar_id' => $result ), $sendback ) );
                exit;
            } elseif ( ! empty( $_POST['action'] ) && 'save' === $_POST['action'] ) {
                $result = $this->process_change_calendar_type();
                wp_redirect( add_query_arg( array( 'section' => 'general', 'calendar_id' => intval( $_POST['calendar_id'] ) ), $sendback ) );
                exit;
            }
        }
    }
    
    /**
     * Process add new calendar
     * @return integer Calendar ID
     */
    public function process_add_calendar() {
        $name = '' === $_POST['name'] ? sanitize_text_field( $_POST['type_name'] ) : sanitize_text_field( $_POST['name'] );
        $type = '' === $_POST['type'] ? 'monthly' : sanitize_key( $_POST['type'] );
        
        $calendar_settings = array();
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        
        $args = array(
            'name'     => $name,
            'type'     => $type,
            'settings' => $default_settings[$type]
        );
        
        check_admin_referer( 'wpcalendars_builder' );
        return wpcalendars_add_new_calendar( $args );
    }
    
    /**
     * Process change calendar type
     * @return boolean True or false
     */
    public function process_change_calendar_type() {
        $id   = intval( $_POST['calendar_id'] );
        
        $name = ( '' === $_POST['name'] ) ? sanitize_text_field( $_POST['type_name'] ) : sanitize_text_field( $_POST['name'] );
        $type = ( '' === $_POST['type'] ) ? 'monthly' : sanitize_key( $_POST['type'] );
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        
        $args = array(
            'calendar_id' => $id,
            'name'        => $name,
            'type'        => $type,
            'settings'    => $default_settings[$type]
        );
        
        check_admin_referer( 'wpcalendars_builder' );
        return wpcalendars_change_calendar_type( $args );
    }
    
    /**
     * Save calendar settings
     * @param array $args
     */
    public function save_settings( $args ) {
        update_post_meta( $args['calendar_id'], '_settings', $args['settings'] );
    }

}

WPCalendars_Builder::instance();
