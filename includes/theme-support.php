<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Theme_Support {

    private static $_instance = NULL;
    
    public $template = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        $this->template = strtolower( get_template() );
        
        $supported_themes = array(
            'genesis', 
            'twentynineteen', 
            'twentyseventeen', 
            'twentysixteen', 
            'twentyfifteen', 
            'twentyfourteen', 
            'twentythirteen', 
            'twentyeleven', 
            'twentytwelve', 
            'twentyten'
        );
        
        if ( in_array( $this->template, $supported_themes ) ) {
            switch ( $this->template ) {
                case 'genesis':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/genesis.php';
                    break;
                case 'twentynineteen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentynineteen.php';
                    break;
                case 'twentyseventeen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentyseventeen.php';
                    break;
                case 'twentysixteen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentysixteen.php';
                    break;
                case 'twentyfifteen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentyfifteen.php';
                    break;
                case 'twentyfourteen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentyfourteen.php';
                    break;
                case 'twentythirteen':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentythirteen.php';
                    break;
                case 'twentytwelve':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentytwelve.php';
                    break;
                case 'twentyeleven':
                    include_once WPCALENDARS_PLUGIN_DIR . 'theme-support/twentyeleven.php';
                    break;
            }
        }
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
}

WPCalendars_Theme_Support::instance();
