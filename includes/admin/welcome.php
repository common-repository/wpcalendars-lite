<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Welcome {

    private static $_instance = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'setup_fresh_install' ), 9999 );
        add_action( 'admin_head', array( $this, 'hide_menu' ) );
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
     * Add Welcome menu page
     */
    public function admin_menu() {
        add_dashboard_page( __( 'Welcome to WPCalendars', 'wpcalendars' ), __( 'Welcome to WPCalendars', 'wpcalendars' ), 'manage_options', 'wpcalendars-welcome', array( $this, 'admin_page' ) );
    }
    
    /**
     * Template of settings page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <div class="wpcalendars-welcome-container">
                <div class="wpcalendars-welcome-header">
                    <h1><?php echo esc_html__( 'Welcome to WPCalendars', 'wpcalendars' );?></h1>
                    <p><?php echo esc_html__( 'Thank you for using WPCalendars, a powerful and user-friendly events calendar plugin', 'wpcalendars' );?></p>
                </div>
                
                <div class="wpcalendars-welcome-banner">
                    <img src="<?php echo WPCALENDARS_PLUGIN_URL ?>/assets/images/builder.png">
                </div>
                
                <div class="wpcalendars-welcome-next">
                    <h2><?php echo esc_html__( "What's Next" , 'wpcalendars' ); ?></h2>
                    <div class="wpcalendars-welcome-next-sections">
                        <div class="wpcalendars-welcome-next-section">
                            <div class="wpcalendars-welcome-next-section-inner">
                                <h3><?php echo esc_html__( 'Sample Calendar', 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Try to install the sample calendar and see the result on your own WordPress site.', 'wpcalendars' ) ?></p>
                                <p class="wpcalendars-welcome-button"><a href="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-tools' ) ?>"><?php echo esc_html__( 'Install Now', 'wpcalendars' ) ?></a></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-next-section">
                            <div class="wpcalendars-welcome-next-section-inner">
                                <h3><?php echo esc_html__( 'Articles & Tutorials', 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Check out the WPCalendars site to find articles and tutorials about WPCalendars.', 'wpcalendars' ) ?></p>
                                <p class="wpcalendars-welcome-button"><a href="//wpcalendars.com" target="_blank"><?php echo esc_html__( 'Read Full Guide', 'wpcalendars' ) ?></a></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-next-section">
                            <div class="wpcalendars-welcome-next-section-inner">
                                <h3><?php echo esc_html__( 'Live Demo', 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Check out the WPCalendars live demo to see the powerful features of WPCalendars.', 'wpcalendars' ) ?></p>
                                <p class="wpcalendars-welcome-button"><a href="//demo.wpcalendars.com" target="_blank"><?php echo esc_html__( 'See Live Demo', 'wpcalendars' ) ?></a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wpcalendars-welcome-container">
                <div class="wpcalendars-welcome-features">
                    <h2><?php echo esc_html__( "WPCalendars Features" , 'wpcalendars' ); ?></h2>

                    <div class="wpcalendars-welcome-feature-sections">
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Calendar Builder' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'WPCalendars comes with a unique builder that helps you create calendars at ease', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Event Colors' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Assign events to specific category and then display the events using different colors', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Repeat Events' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Create events that repeat automatically on a daily, weekly, monthly, and yearly basis', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Date / Time Format' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'You can easily change date and time format of events to desired format universally', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Multi-Day Events' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'You can create events that last more than one day', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Filter Navigation' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Users can easily filter the events to find exactly what they are looking for', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Tooltip / Popup' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'You can easily add tooltip or popup window for each event', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Month Picker' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'You can add month picker navigation to help visitors to find easily what they are looking for', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'iCal Subscription' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Allow visitors to subscribe events using iCal (.ics) format', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Location Maps' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'WPCalendars use Google Maps to display event locations and get directions', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Event Organizer' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'You can easily add organizer for events along with their information', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Google Calendar' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'WPCalendars allow add the events to Google Calendar using direct link or Google API', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Download iCal' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Allow visitors to download all the events as ICS file that is importable to iCal', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'AJAX Pagination' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'Events can be viewed in increments using AJAX pagination on the calendar', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                        <div class="wpcalendars-welcome-feature-section">
                            <div class="wpcalendars-welcome-feature-section-inner">
                                <h3><?php echo esc_html__( 'Translation Ready' , 'wpcalendars' ); ?></h3>
                                <p><?php echo esc_html__( 'WPCalendars is fully localized and ready for your language', 'wpcalendars' ) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Setting initialization
     */
    public function setup_fresh_install() {
        // Check option to disable welcome redirect.
        if ( get_option( 'wpcalendars_activation_redirect', false ) ) {
            return;
        }
        
        if ( get_transient( 'wpcalendars_activation_redirect' ) ) {
            return;
        }
        
        delete_transient( 'wpcalendars_activation_redirect' );

        // Only do this for single site installs.
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { // WPCS: CSRF ok.
            return;
        }
        
        wpcalendars_setup_fresh_install();

        set_transient( 'wpcalendars_activation_redirect', true );
        
        wp_safe_redirect( admin_url( 'index.php?page=wpcalendars-welcome' ) );
        exit;
    }

    /**
     * Hide welcome menu
     */
    public function hide_menu() {
        remove_submenu_page( 'index.php', 'wpcalendars-welcome' );
	}

}

WPCalendars_Welcome::instance();
