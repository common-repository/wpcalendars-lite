<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_List_View_Calendar {
    
    private static $_instance = NULL;
   
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'wpcalendars_builder_print_sidebar_settings_general',    array( $this, 'builder_settings_general' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_navigation', array( $this, 'builder_settings_navigation' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_events',     array( $this, 'builder_settings_events' ), 10, 2 );
        add_action( 'wpcalendars_list_calendar_navigation',                  array( $this, 'show_prevnext_navigation' ), 10, 3 );
        add_action( 'wpcalendars_list_calendar_navigation',                  array( $this, 'show_calendar_heading' ), 10, 3 );
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
     * Render builder general settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_general( $calendar_type, $settings ) {
        if ( 'list' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        ?>
        <h3><?php echo esc_html__( 'General Settings', 'wpcalendars' ) ?></h3>
        
        <?php
        wpcalendars_builder_display_settings( $settings );
        wpcalendars_builder_month_format_settings( $settings );
        wpcalendars_builder_date_format_settings( $settings );
        wpcalendars_builder_time_format_settings( $settings );
        wpcalendars_builder_heading_settings( $settings, true );
        
        do_action( 'wpcalendars_builder_list_calendar_settings_general', $settings );
    }
    
    /**
     * Render builder navigation settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_navigation( $calendar_type, $settings ) {
        if ( 'list' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_prevnext_navigation_settings( $settings );
        
        do_action( 'wpcalendars_builder_list_calendar_settings_navigation', $settings );
    }
    
    /**
     * Render builder events settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_events( $calendar_type, $settings ) {
        if ( 'list' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_general_events_settings( $settings );
        
        do_action( 'wpcalendars_builder_list_calendar_settings_events', $settings );
    }
    
    /**
     * Display calendar output
     * @global type $wp_locale
     * @param type $calendar_id
     * @param type $settings
     */
    public static function output( $calendar_id, $settings = array() ) {
        global $wp_locale;

        $default_settings = wpcalendars_get_default_calendar_settings();
        $default_settings = $default_settings['list'];

        if ( $settings === array() ) {
            $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( $calendar_id ), $calendar_id );
            $settings = $calendar['settings'];
            
            if ( 'custom' === $settings['display'] ) {
                $settings['start_date'] = sprintf( '%s-%s-01', $settings['custom_default_start_year'], $settings['custom_start_month'] );
                
                $end_year = $settings['custom_default_start_year'];
                
                if ( intval( $settings['custom_start_month'] ) >= intval( $settings['custom_end_month'] ) ) {
                    $end_year++;
                }
                
                $days_in_month = date( 't', mktime( 0, 0, 0, $settings['custom_end_month'], 1, $end_year ) );
                $settings['end_date'] = sprintf( '%s-%s-%s', $end_year, $settings['custom_end_month'], $days_in_month );
            }
        }

        $settings = wp_parse_args( $settings, $default_settings );

        $current_year  = date( 'Y' );
        $current_month = date( 'm' );

        $intervals = array(
            'two-months'   => 2,
            'three-months' => 3,
            'four-months'  => 4,
            'six-months'   => 6,
            'one-year'     => 12
        );

        $diff = strtotime( $settings['end_date'] ) - strtotime( $settings['start_date'] );
        $intervals['custom'] = floor( $diff / ( 30 * 60 * 60 * 24 ) );

        if ( '' === $settings['start_date'] && '' === $settings['end_date'] ) {
            if ( 'current' === $settings['first_month'] ) {
                $start_month = $current_month;
                $start_year  = $current_year;
                $end_month   = $start_month + $intervals[$settings['display']] - 1;
                $end_year    = $current_year;

                if ( $end_month > 12 ) {
                    $end_month = $end_month - 12;
                    $end_year  = $current_year + 1;
                }
            } else {
                switch ( $settings['display'] ) {
                    case 'two-months':
                        $start_month = $current_month % 2 === 1 ? $current_month : $current_month - 1;
                        $start_year  = $current_year;
                        $end_month   = $start_month + 1;
                        $end_year    = $current_year;
                        break;

                    case 'three-months':
                        if ( $current_month % 3 === 1 ) {
                            $start_month = $current_month;
                        } elseif ( $current_month % 3 === 2 ) {
                            $start_month = $current_month - 1;
                        } else {
                            $start_month = $current_month - 2;
                        }

                        $start_year = $current_year;
                        $end_month  = $start_month + 2;
                        $end_year   = $current_year;
                        break;

                    case 'four-months':
                        if ( $current_month <= 4 ) {
                            $start_month = 1;
                        } elseif ( $current_month <= 8 ) {
                            $start_month = 5;
                        } else {
                            $start_month = 9;
                        }

                        $start_year = $current_year;
                        $end_month  = $start_month + 3;
                        $end_year   = $current_year;
                        break;

                    case 'six-months':
                        if ( $current_month <= 6 ) {
                            $start_month = 1;
                        } else {
                            $start_month = 7;
                        }

                        $start_year = $current_year;
                        $end_month  = $start_month + 5;
                        $end_year   = $current_year;
                        break;

                    case 'one-year':
                        $start_month = 1;
                        $start_year  = $current_year;
                        $end_month   = 12;
                        $end_year    = $current_year;
                        break;
                }
            }

            $days_in_month = date( 't', mktime( 0, 0, 0, $end_month, 1, $end_year ) );

            $settings['start_date'] = sprintf( '%s-%s-01', $start_year, zeroise( $start_month, 2 ) );
            $settings['end_date']   = sprintf( '%s-%s-%s', $end_year, zeroise( $end_month, 2 ), $days_in_month );
        }

        $event_args = apply_filters( 'wpcalendars_events_args', array(
            'start_date'         => $settings['start_date'],
            'end_date'           => $settings['end_date'],
            'categories'         => $settings['categories'],
            'tags'               => $settings['tags'],
            'venues'             => $settings['venues'],
            'organizers'         => $settings['organizers'],
            'exclude_events'     => $settings['exclude_events'],
            'show_hidden_events' => $settings['show_hidden_events']
        ), $settings );

        $event_listings = wpcalendars_get_events( $event_args );

        if ( 'DESC' === $settings['sort_order'] ) {
            $event_listings = array_reverse( $event_listings );
        }

        echo '<div class="wpcalendars-container">';
        
        $categories = isset( $settings['_categories'] ) ? $settings['_categories'] : '';
        $tags       = isset( $settings['_tags'] ) ? $settings['_tags'] : '';
        $venues     = isset( $settings['_venues'] ) ? $settings['_venues'] : '';
        $organizers = isset( $settings['_organizers'] ) ? $settings['_organizers'] : '';
        
        printf( '<input type="hidden" class="wpcalendars-calendarid" value="%s">', $calendar_id );
        printf( '<input type="hidden" class="wpcalendars-startdate" value="%s">', $settings['start_date'] );
        printf( '<input type="hidden" class="wpcalendars-enddate" value="%s">', $settings['end_date'] );
        printf( '<input type="hidden" class="wpcalendars-categories" value="%s">', $categories );
        printf( '<input type="hidden" class="wpcalendars-tags" value="%s">', $tags );
        printf( '<input type="hidden" class="wpcalendars-venues" value="%s">', $venues );
        printf( '<input type="hidden" class="wpcalendars-organizers" value="%s">', $organizers );

        do_action( 'wpcalendars_list_calendar_before_navigation', $calendar_id, $settings );
        
        echo '<div class="wpcalendars-nav-container">';
        do_action( 'wpcalendars_list_calendar_navigation', $calendar_id, $settings );
        echo '</div>';
        
        do_action( 'wpcalendars_list_calendar_after_navigation', $calendar_id, $settings );

        echo '<div class="wpcalendars-list-calendar">';

        if ( ! empty( $event_listings ) ) {
            echo '<div class="wpcalendars-event-items">';
            
            foreach ( $event_listings as $event ) {
                echo '<div class="wpcalendars-event-item">';
                    
                // Event Date
                $date_time = wpcalendars_format_date( $event['start_date'], $event['end_date'], $settings['date_format'], $settings['show_year'] );

                if ( 'N' === $event['all_day'] ) {
                    $date_time .= ' @ ' . wpcalendars_format_time( $event['start_time'], $event['end_time'], $settings['time_format'] );
                }

                printf( '<div class="wpcalendars-event-date">%s</div>', $date_time );

                // Event Title
                echo '<div class="wpcalendars-event-title">';

                if ( 'Y' === $event['disable_event_details'] ) {
                    echo $event['event_title'];
                } else {
                    printf( '<a href="%s">%s</a>', wpcalendars_get_permalink( $event ), $event['event_title'] );
                }

                echo '</div>';

                // Event Summary

                echo '<div class="wpcalendars-event-summary">';

                if ( has_post_thumbnail( $event['event_id'] ) ) {
                    echo '<div class="wpcalendars-event-thumbnail">';
                    echo get_the_post_thumbnail( $event['event_id'], 'wpcalendars-featured-image' );
                    echo '</div>';
                }

                printf( '<div class="wpcalendars-event-excerpt">%s</div>', $event['event_excerpt'] );
                
                if ( 'N' === $event['disable_event_details'] ) {
                    printf( '<div class="wpcalendars-event-more"><a href="%s">%s</a></div>', wpcalendars_get_permalink( $event ), __( 'Read More', 'wpcalendars' ) );
                }

                echo '</div>';

                do_action( 'wpcalendars_list_calendar_event_item', $event, $settings );

                echo '</div>';
            }
            
            echo '</div>';
            do_action( 'wpcalendars_list_calendar_after', $calendar_id, $settings );
        } else {
            printf( '<div class="wpcalendars-no-events">%s</div>', esc_html__( 'No Events', 'wpcalendars' ) );
        }

        echo '</div>';

        echo '</div>';
    }
    
    /**
     * Show prev/next navigation
     * @param type $calendar_id
     * @param type $settings
     */
    public function show_prevnext_navigation( $calendar_id, $settings ) {
        if ( 'Y' === $settings['prevnext_nav'] ) {
            $intervals = array(
                'two-months'   => 2,
                'three-months' => 3,
                'four-months'  => 4,
                'six-months'   => 6,
                'one-year'     => 12
            );

            $diff = strtotime( $settings['end_date'] ) - strtotime( $settings['start_date'] );
            $intervals['custom'] = floor( $diff / ( 30 * 60 * 60 * 24 ) );

            $start_date = explode( '-', $settings['start_date'] );
            $end_date   = explode( '-', $settings['end_date'] );

            $start_month = intval( $start_date[1] );
            $start_year  = intval( $start_date[0] );
            $end_month   = intval( $end_date[1] );
            $end_year    = intval( $end_date[0] );

            if ( 'custom' === $settings['display'] ) {
                $prev_start_month = $start_month;
                $prev_start_year  = $start_year - 1;

                $prev_end_month = $end_month;
                $prev_end_year  = $end_year - 1;

                $next_start_month = $start_month;
                $next_start_year  = $start_year + 1;

                $next_end_month = $end_month;
                $next_end_year  = $end_year + 1;
            } else {
                $prev_start_month = $start_month - $intervals[$settings['display']];
                $prev_start_year  = $start_year;

                if ( $prev_start_month <= 0 ) {
                    $prev_start_month = $prev_start_month + 12;
                    $prev_start_year  = $prev_start_year - 1;
                }

                $prev_end_month = $prev_start_month + $intervals[$settings['display']] - 1;
                $prev_end_year  = $prev_start_year;

                if ( $prev_end_month > 12 ) {
                    $prev_end_month = $prev_end_month - 12;
                    $prev_end_year  = $prev_end_year + 1;
                }

                $next_start_month = $end_month + 1;
                $next_start_year  = $end_year;

                if ( $next_start_month > 12 ) {
                    $next_start_month = $next_start_month - 12;
                    $next_start_year  = $next_start_year + 1;
                }

                $next_end_month = $next_start_month + $intervals[$settings['display']] - 1;
                $next_end_year  = $next_start_year;

                if ( $next_end_month > 12 ) {
                    $next_end_month = $next_end_month - 12;
                    $next_end_year  = $next_end_year + 1;
                }
            }

            $days_in_month = date( 't', mktime( 0, 0, 0, $prev_end_month, 1, $prev_end_year ) );

            $prev_start_date = sprintf( '%s-%s-01', $prev_start_year, zeroise( $prev_start_month, 2 ) );
            $prev_end_date   = sprintf( '%s-%s-%s', $prev_end_year, zeroise( $prev_end_month, 2 ), $days_in_month );

            $days_in_month = date( 't', mktime( 0, 0, 0, $next_end_month, 1, $next_end_year ) );

            $next_start_date = sprintf( '%s-%s-01', $next_start_year, zeroise( $next_start_month, 2 ) );
            $next_end_date   = sprintf( '%s-%s-%s', $next_end_year, zeroise( $next_end_month, 2 ), $days_in_month );

            wpcalendars_show_prevnext_navigation( array(
                'calendar_id'     => $calendar_id,
                'prev_start_date' => $prev_start_date,
                'prev_end_date'   => $prev_end_date,
                'next_start_date' => $next_start_date,
                'next_end_date'   => $next_end_date
            ) );
        }
    }
    
    /**
     * Show calendar heading
     * @param type $calendar_id
     * @param type $settings
     */
    public function show_calendar_heading( $calendar_id, $settings ) {
        wpcalendars_show_heading( $calendar_id, $settings );
    }
    
}

WPCalendars_List_View_Calendar::instance();