<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Templates {

    private static $_instance = NULL;
    
    private $theme_support = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'init',                            array( $this, 'init' ) );
        add_action( 'wpcalendars_before_main_content', array( $this, 'output_content_wrapper' ) );
        add_action( 'wpcalendars_after_main_content',  array( $this, 'output_content_wrapper_end' ) );
        add_action( 'wpcalendars_sidebar',             array( $this, 'sidebar' ) );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_title' ), 10 );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_date' ), 20 );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_image' ), 25 );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_content' ), 30 );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_venue' ), 35 );
        add_action( 'wpcalendars_single_event',        array( $this, 'single_event_organizer' ), 40 );
        add_action( 'wpcalendars_single_event',        array( $this, 'clearfix' ), 998 );
    }
    
    /**
     * Template initialization
     */
    public function init() {
        $this->theme_support = current_theme_supports( 'wpcalendars' );
        
        if ( $this->theme_support ) {
            add_filter( 'template_include', array( $this, 'template_include' ) );
        } else {
            add_action( 'template_redirect', array( $this, 'unsupported_theme_init' ) );
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
    
    /**
     * Load template file for single event
     * @param string $template
     * @return string
     */
    public function template_include( $template ) {
        if ( $default_file = $this->get_template_loader_default_file() ) {
            $template = locate_template( $default_file );
            
            if ( ! $template ) {
                $template = apply_filters( 'wpcalendars_template_include', WPCALENDARS_PLUGIN_DIR . 'templates/' . $default_file, $default_file );
            }
        }
        
        return $template;
    }
    
    /**
     * Get template default file
     * @return type
     */
    public function get_template_loader_default_file() {
        if ( is_singular( 'wpcalendars_event' ) ) {
            $default_file = 'single-event.php';
        } else {
            $default_file = '';
        }
        
        return apply_filters( 'wpcalendars_template_loader_default_file', $default_file );
    }
    
    /**
     * Display content wrapper
     */
    public function output_content_wrapper() {
        echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';
    }
    
    /**
     * Display content wrapper end
     */
    public function output_content_wrapper_end() {
        echo '</main></div>';
    }
    
    /**
     * Display sidebar
     */
    public function sidebar() {
        get_sidebar();
    }
    
    /**
     * Display single event title
     */
    public function single_event_title() {
        the_title( '<h1 class="entry-title">', '</h1>' );
    }
    
    /**
     * Display single event date time
     */
    public function single_event_date( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }
        
        echo '<div class="wpcalendars-event-date-section">';
        printf( '<span class="wpcalendars-event-date-heading">%s</span>', esc_html__( 'Date / Time:', 'wpcalendars' ) );
        printf( '<span class="wpcalendars-event-date-content">%s</span>', apply_filters( 'wpcalendars_event_single_date_time', wpcalendars_get_event_date( $event_id ), $event_id ) );
        echo '</div>';
    }
    
    /**
     * Display venue on single event
     * @global type $states
     * @param type $event_id
     * @return type
     */
    public function single_event_venue( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }
        
        $event = wpcalendars_get_event( $event_id );
            
        if ( empty( $event['venue_id'] ) ) {
            return;
        }

        $venue = wpcalendars_get_venue( $event['venue_id'] );

        $countries = include WPCALENDARS_PLUGIN_DIR . 'includes/countries.php';

        $location = array();

        if ( isset( $venue['address'] ) && '' !== $venue['address'] ) {
            $location[] = $venue['address'];
        }

        if ( isset( $venue['city'] ) && '' !== $venue['city'] ) {
            $location[] = $venue['city'];
        }

        if ( isset( $venue['state'] ) && '' !== $venue['state'] ) {
            global $states;

            if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $venue['country'] . '.php' ) ) {
                include WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $venue['country'] . '.php';
                $location[] = $states[$venue['country']][$venue['state']];
            } else {
                $location[] = $venue['state'];
            }
        }

        if ( isset( $venue['country'] ) && '' !== $venue['country'] ) {
            $location[] = $countries[$venue['country']];
        }

        if ( isset( $venue['postal_code'] ) && '' !== $venue['postal_code'] ) {
            $location[] = $venue['postal_code'];
        }
        
        echo '<div class="wpcalendars-event-venue-section">';
        
        printf( '<div class="wpcalendars-event-venue-heading">%s</div>', esc_html__( 'Venue', 'wpcalendars' ) );
        
        echo '<div class="wpcalendars-event-venue-content">';
        
        echo '<div class="wpcalendars-event-venue-name wpcalendars-detail-tooltip">';
        echo $venue['name'];
        if ( '' !== $venue['detail'] ) {
            printf( '<span><a class="wpcalendars-detail-tooltip-link" href="#">+ %s</a></span>', esc_html__( 'Show Details', 'wpcalendars' ) );
            printf( '<div class="wpcalendars-detail-tooltip-content" style="display:none"><div class="wpcalendars-detail-tooltip-content-inner">%s</div></div>', wpautop( $venue['detail'] ) );
        }
        echo '</div>'; // name
        
        echo '<div class="wpcalendars-event-venue-location">';
        echo implode( ', ', $location );
        if ( '' !== $venue['latitude'] && '' !== $venue['longitude'] ) {
            $map_provider = wpcalendars_settings_value( 'general', 'map_provider' );
            if ( 'google' === $map_provider ) {
                echo '<span><a href="//www.google.com/maps/dir/?api=1&destination=' . urlencode( $venue['name'] . ',' . implode( ', ', $location ) ) . '&destination_place_id=' . $venue['place_id'] . '" target="_blank" rel="nofollow">+ ' . esc_html__( 'Google Maps Direction', 'wpcalendars' ) . '</a></span>';
            }
        }
        echo '</div>';
        
        echo '<div class="wpcalendars-event-venue-metadata">';
                
        if ( '' !== $venue['phone'] ) {
            printf( '<div class="wpcalendars-event-venue-phone"><div class="wpcalendars-event-venue-phone-icon"></div>%s</div>', $venue['phone'] );
        }

        if ( '' !== $venue['email'] ) {
            printf( '<div class="wpcalendars-event-venue-email"><div class="wpcalendars-event-venue-email-icon"></div><a href="mailto:%s" rel="nofollow">%s</a></div>', $venue['email'], $venue['email'] );
        }

        if ( '' !== $venue['website'] ) {
            printf( '<div class="wpcalendars-event-venue-website"><div class="wpcalendars-event-venue-website-icon"></div><a href="%s" target="_blank" rel="nofollow">%s</a></div>', $venue['website'], $venue['website'] );
        }

        echo '</div>'; // metadata
        
        if ( '' !== $venue['latitude'] && '' !== $venue['longitude'] ) {
            $map_provider       = wpcalendars_settings_value( 'general', 'map_provider' );
            $map_zoom           = wpcalendars_settings_value( 'general', 'map_zoom' );
            $google_maps_apikey = wpcalendars_settings_value( 'api', 'google_maps_apikey' );
            
            echo '<div class="wpcalendars-event-venue-map">';
            if ( 'google' === $map_provider ) {
                echo apply_filters( 'wpcalendars_event_venue_google_maps', '<img src="//maps.googleapis.com/maps/api/staticmap?center=' . $venue['latitude'] . ',' . $venue['longitude'] . '&markers=color:red%7Clabel:S%7C' . $venue['latitude'] . ',' . $venue['longitude'] . '&zoom=' . $map_zoom . '&size=640x400&key=' . $google_maps_apikey . '" />', $venue );
            } else {
                
            }
            echo '</div>'; // map
        }
        echo '</div>'; // content
        echo '</div>'; // section
    }
    
    /**
     * Display organizer on single event
     * @param type $event_id
     * @return type
     */
    public function single_event_organizer( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }
        
        $event = wpcalendars_get_event( $event_id );
        
        if ( empty( $event['organizer_id'] ) ) {
            return;
        }
        
        $organizer = wpcalendars_get_organizer( $event['organizer_id'] );
        
        echo '<div class="wpcalendars-event-organizer-section">';
        
        printf( '<div class="wpcalendars-event-organizer-heading">%s</div>', esc_html__( 'Organizer', 'wpcalendars' ) );
                
        echo '<div class="wpcalendars-event-organizer-content">';

        echo '<div class="wpcalendars-event-organizer-name wpcalendars-detail-tooltip">';
        echo $organizer['name'];
        if ( '' !== $organizer['detail'] ) {
            printf( '<span><a class="wpcalendars-detail-tooltip-link" href="#">+ %s</a></span>', esc_html__( 'Show Details', 'wpcalendars' ) );
            printf( '<div class="wpcalendars-detail-tooltip-content" style="display:none"><div class="wpcalendars-detail-tooltip-content-inner">%s</div></div>', wpautop( $organizer['detail'] ) );
        }
        echo '</div>';

        echo '<div class="wpcalendars-event-organizer-metadata">';

        if ( '' !== $organizer['phone'] ) {
            printf( '<div class="wpcalendars-event-organizer-phone"><div class="wpcalendars-event-organizer-phone-icon"></div>%s</div>', $organizer['phone'] );
        }

        if ( '' !== $organizer['email'] ) {
            printf( '<div class="wpcalendars-event-organizer-email"><div class="wpcalendars-event-organizer-email-icon"></div><a href="mailto:%s" rel="nofollow">%s</a></div>', $organizer['email'], $organizer['email'] );
        }

        if ( '' !== $organizer['website'] ) {
            printf( '<div class="wpcalendars-event-organizer-website"><div class="wpcalendars-event-organizer-website-icon"></div><a href="%s" target="_blank" rel="nofollow">%s</a></div>', $organizer['website'], $organizer['website'] );
        }

        echo '</div>'; // metadata

        echo '</div>'; // content

        echo '</div>'; // section
    }
    
    /**
     * Display single event thumbnail
     */
    public function single_event_image() {
        if ( has_post_thumbnail() ) {
            echo '<div class="wpcalendars-event-image-section">';
            echo get_the_post_thumbnail( get_the_ID(), 'wpcalendars-featured-image' );
            echo '</div>';
        }
    }
    
    /**
     * Display single event content
     */
    public function single_event_content() {
        echo '<div class="wpcalendars-event-detail-section">';
            
        printf( '<div class="wpcalendars-event-detail-heading">%s</div>', esc_html__( 'Event Details', 'wpcalendars' ) );

        echo '<div class="wpcalendars-event-detail-content">';
        printf( '<div id="wpcalendars-event-detail-content-short" class="wpcalendars-detail-content-wrapper">%s</div>', wp_trim_words( get_the_content(), 30, '...<span class="wpcalendars-more-less-detail"><a href="#wpcalendars-event-detail-content-full">' . __( 'More...', 'wpcalendars' ) . '</a></span>' ) );
        printf( '<div id="wpcalendars-event-detail-content-full" class="wpcalendars-detail-content-wrapper" style="display:none">%s<span class="wpcalendars-more-less-detail"><a href="#wpcalendars-event-detail-content-short">%s</a></span></div>', wpautop( get_the_content() ), __( 'Less...', 'wpcalendars' ) );

        echo '</div>'; // content
        
        echo '</div>'; // section
    }
    
    /**
     * Display content on unsupported theme
     */
    public function unsupported_theme_init() {
        add_filter( 'the_content', array( $this, 'unsupported_theme_event_content_filter' ), 10 );
    }
    
    /**
     * Modify content on unsupported theme
     * @global type $wp_query
     * @param type $content
     * @return type
     */
    public function unsupported_theme_event_content_filter( $content ) {
        global $wp_query;
        
        if ( $this->theme_support || ! is_main_query() || ! in_the_loop() ) {
            return $content;
        }
        
        remove_filter( 'the_content', array( $this, 'unsupported_theme_event_content_filter' ) );
        
        if ( is_singular( 'wpcalendars_event' ) ) {
            ob_start();
            
            remove_action( 'wpcalendars_single_event', array( $this, 'single_event_title' ), 10 );
            do_action( 'wpcalendars_single_event' );
            
            $content = ob_get_clean();
        }
        
        return $content;
    }
    
    /**
     * Add clearfix tag
     */
    public function clearfix() {
        echo '<div class="wpcalendars-clear"></div>';
    }

}

WPCalendars_Templates::instance();
