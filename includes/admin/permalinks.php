<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Permalinks {

    private static $_instance = NULL;
    
    private $permalinks = array();

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
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
     * Admin initialization
     */
    public function admin_init() {
        if ( isset( $_POST['event_slug'] ) ) {
            $permalinks = (array) get_option( 'wpcalendars_permalinks', array() );
            $permalinks['event_base'] = sanitize_title( trim( $_POST['event_slug'] ) );
            update_option( 'wpcalendars_permalinks', $permalinks );
        }

        $defaults = array(
            'event_base' => ''
        );
        
		$this->permalinks = get_option( 'wpcalendars_permalinks', $defaults );
        
        add_settings_field(
			'event_slug',                       // id
			__( 'Event base', 'wpcalendars' ),  // setting title
			array( $this, 'event_slug_input' ), // display callback
			'permalink',                        // settings page
			'optional'                          // settings section
		);
    }
    
    /**
     * Event slug input settings
     */
    public function event_slug_input() {
        ?>
        <input name="event_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['event_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'event', 'slug', 'wpcalendars' ) ?>" />
        <?php
    }
    
}

WPCalendars_Permalinks::instance();