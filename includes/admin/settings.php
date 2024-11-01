<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Settings {

    private static $_instance = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
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
        add_submenu_page( 'edit.php?post_type=wpcalendars_event', __( 'WPCalendars Settings', 'wpcalendars' ), __( 'Settings', 'wpcalendars' ), 'manage_options', 'wpcalendars-settings', array( $this, 'settings_page' ) );
    }
    
    /**
     * Template of settings page
     */
    public function settings_page() {
        $tabs = apply_filters( 'wpcalendars_settings_tabs', array(
            'general' => __( 'General', 'wpcalendars' ),
            'api'     => __( 'API', 'wpcalendars' )
        ) );
        
        $current_tab = isset( $_REQUEST['tab'] ) ? sanitize_key( $_REQUEST['tab'] ) : 'general';
        ?>
        <div class="wrap">
            <h2><?php _e( 'WPCalendars Settings', 'wpcalendars' );?></h2>
            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tabs as $id => $tab ): ?>
                <a href="<?php echo add_query_arg( 'tab', $id, admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-settings' ) ); ?>" class="nav-tab<?php if ( $current_tab === $id ) echo ' nav-tab-active'; ?>"><?php echo $tab; ?></a>
                <?php endforeach; ?>
            </h2>
            <form method="post" action="options.php">
                <input type="hidden" name="tab" value="<?php echo $current_tab ?>">
                <?php
				settings_fields( 'wpcalendars_options' );
                if ( 'general' === $current_tab ) {
                    $this->settings_general_page();
                } elseif ( 'api' === $current_tab ) {
                    $this->settings_api_page();
                }
                do_action( 'wpcalendars_settings_custom_page', $current_tab ); ?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo esc_attr__( 'Save Changes', 'wpcalendars' ); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render general settings page
     */
    public function settings_general_page() {
        $weekdays            = wpcalendars_get_weekday_options();
        $date_format_options = wpcalendars_get_date_format_options();
        $time_format_options = wpcalendars_get_time_format_options();
        $categories          = wpcalendars_get_event_categories();
        
        $maps_provider = array(
            'google' => __( 'Google Maps', 'wpcalendars' )
        );
        ?>
        <h3><?php echo esc_html__( 'Event Settings', 'wpcalendars' );?></h3>
        <hr>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Default Event Category', 'wpcalendars' );?></th>
                <td class="forminp">
                    <select name="wpcalendars_options[general][default_event_category]">
                        <?php foreach ( $categories as $category ): ?>
                        <option value="<?php echo esc_attr( $category['category_id'] ) ?>"<?php selected( $category['category_id'], wpcalendars_settings_value( 'general', 'default_event_category' ) ) ?>><?php echo esc_html( $category['name'] ) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Weekday', 'wpcalendars' );?></th>
                <td class="forminp">
                    <select name="wpcalendars_options[general][weekday][]" multiple="multiple" class="wpcalendars-select">
                        <?php foreach ( $weekdays as $key => $weekday ): ?>
                        <option value="<?php echo esc_attr( $key ) ?>"<?php selected( in_array( $key, wpcalendars_settings_value( 'general', 'weekday' ) ) ) ?>><?php echo esc_html( $weekday ) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Date Format', 'wpcalendars' );?></th>
                <td class="forminp">
                    <select name="wpcalendars_options[general][date_format]">
                        <?php foreach ( $date_format_options as $key => $value ): ?>
                        <option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, wpcalendars_settings_value( 'general', 'date_format' ) ) ?>><?php echo esc_html( $value ) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Time Format', 'wpcalendars' );?></th>
                <td class="forminp">
                    <select name="wpcalendars_options[general][time_format]">
                        <?php foreach ( $time_format_options as $key => $value ): ?>
                        <option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, wpcalendars_settings_value( 'general', 'time_format' ) ) ?>><?php echo esc_html( $value ) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
        </table>
        <h3><?php echo esc_html__( 'Venue Settings', 'wpcalendars' );?></h3>
        <hr>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Map Provider', 'wpcalendars' );?></th>
                <td class="forminp">
                    <select name="wpcalendars_options[general][map_provider]">
                        <?php foreach ( $maps_provider as $key => $name ): ?>
                        <option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, wpcalendars_settings_value( 'general', 'map_provider' ) ) ?>><?php echo esc_html( $name ) ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Zoom Level', 'wpcalendars' );?></th>
                <td class="forminp">
                    <input type="number" min="10" max="20" name="wpcalendars_options[general][map_zoom]" value="<?php echo esc_attr( wpcalendars_settings_value( 'general', 'map_zoom' ) ) ?>" class="small-text">
                </td>
            </tr>
        </table>
        
        <?php do_action( 'wpcalendars_settings_general_page' ); ?>
        
        <h3><?php echo esc_html__( 'Misc Settings', 'wpcalendars' );?></h3>
        <hr>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Uninstall WPCalendars', 'wpcalendars' );?></th>
                <td class="forminp">
                    <p><label for="uninstall-remove-data"><input id="uninstall-remove-data" type="checkbox" name="wpcalendars_options[general][uninstall_remove_data]" value="Y" <?php checked( 'Y', wpcalendars_settings_value( 'general', 'uninstall_remove_data' ) ) ?>> 
                        <?php echo esc_html__( 'Check this if you would like to remove ALL WPCalendars data upon plugin deletion. All calendars and events will be unrecoverable.', 'wpcalendars' ) ?></label></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render api settings page
     */
    public function settings_api_page() {
        ?>
        <h3><?php echo esc_html__( 'Google Maps Settings', 'wpcalendars' );?></h3>
        <p class="description"><?php printf( __( 'Read our <a href="%s" target="_blank">documentation</a> to learn how to configure Google Maps API.', 'wpcalendars' ), '//wpcalendars.com/docs/google-maps/' ) ?></p>
        <hr>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php echo esc_html__( 'Google API Key', 'wpcalendars' );?></th>
                <td class="forminp">
                    <input type="text" name="wpcalendars_options[api][google_maps_apikey]" value="<?php echo esc_attr( wpcalendars_settings_value( 'api', 'google_maps_apikey' ) ) ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php 
        do_action( 'wpcalendars_settings_api_page' );
    }
        
    /**
     * Setting initialization
     */
    public function settings_init() {
        register_setting( 'wpcalendars_options', 'wpcalendars_options', array( $this, 'settings_sanitize' ) );
    }
    
    /**
     * Setting sanitization
     * @param array $input
     * @return type
     */
    public function settings_sanitize( $input ) {
		$current_tab = isset( $_REQUEST['tab'] ) ? sanitize_key( $_REQUEST['tab'] ) : 'general';
		$options     = get_option( 'wpcalendars_options', wpcalendars_get_default_settings() );
        $settings    = wpcalendars_get_settings();
        
        foreach ( $settings[$current_tab] as $key => $setting ) {
            if ( 'text' === $setting['type'] ) {
                $options[$current_tab][$key] = esc_attr( $input[$current_tab][$key] );
            } elseif ( 'select' === $setting['type'] || 'radio' === $setting['type'] ) {
                if ( in_array( $input[$current_tab][$key], $setting['options'] ) ) {
                    $options[$current_tab][$key] = $input[$current_tab][$key];
                } else {
                    $options[$current_tab][$key] = $setting['default_value'];
                }
            } elseif ( 'multiple' === $setting['type'] ) {
                $valid_values = array();
                
                foreach ( $input[$current_tab][$key] as $val ) {
                    if ( in_array( $val, $setting['options'] ) ) {
                        $valid_values[] = $val;
                    }
                }
                
                $options[$current_tab][$key] = $valid_values;
            } elseif ( 'checkbox' === $setting['type'] ) {
                if ( isset( $input[$current_tab][$key] ) ) {
                    $options[$current_tab][$key] = 'Y';
                } else {
                    $options[$current_tab][$key] = 'N';
                }
            } else {
                $options[$current_tab][$key] = $input[$current_tab][$key];
            }
        }
        
        return $options;
    }

}

WPCalendars_Settings::instance();
