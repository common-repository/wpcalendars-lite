<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Weekly_View_Calendar {
    
    private static $_instance = NULL;
   
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'wpcalendars_builder_print_sidebar_settings_general',    array( $this, 'builder_settings_general' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_navigation', array( $this, 'builder_settings_navigation' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_events',     array( $this, 'builder_settings_events' ), 10, 2 );
        add_action( 'wpcalendars_weekly_calendar_navigation',                array( $this, 'show_prevnext_navigation' ), 10, 3 );
        add_action( 'wpcalendars_weekly_calendar_navigation',                array( $this, 'show_calendar_heading' ), 10, 3 );
        add_action( 'wpcalendars_weekly_calendar_after',                     array( $this, 'show_category_listings' ), 10, 4 );
        add_action( 'wpcalendars_weekly_calendar_after',                     array( $this, 'show_event_listings' ), 10, 4 );
        
        add_filter( 'wpcalendars_weekly_calendar_date_class', array( $this, 'tooltip_class' ), 10, 3 );
        add_filter( 'wpcalendars_weekly_calendar_date_class', array( $this, 'popup_class' ), 10, 3 );
        add_filter( 'wpcalendars_weekly_calendar_date_attr',  array( $this, 'tooltip_attr' ), 10, 3 );
        add_filter( 'wpcalendars_weekly_calendar_date_attr',  array( $this, 'popup_attr' ), 10, 3 );
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
        if ( 'weekly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        ?>
        <h3><?php echo esc_html__( 'General Settings', 'wpcalendars' ) ?></h3>
        
        <?php
        wpcalendars_builder_month_format_settings( $settings );
        wpcalendars_builder_weekday_format_settings( $settings );
        wpcalendars_builder_date_format_settings( $settings );
        wpcalendars_builder_time_format_settings( $settings );
        wpcalendars_builder_heading_settings( $settings );
        wpcalendars_builder_category_listings_settings( $settings );
        wpcalendars_builder_event_listings_settings( $settings );
        
        do_action( 'wpcalendars_builder_weekly_calendar_settings_general', $settings );
    }
    
    /**
     * Render builder navigation settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_navigation( $calendar_type, $settings ) {
        if ( 'weekly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_prevnext_navigation_settings( $settings );
        wpcalendars_builder_daily_event_navigation_settings( $settings );
        
        do_action( 'wpcalendars_builder_weekly_calendar_settings_navigation', $settings );
    }
    
    /**
     * Render builder events settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_events( $calendar_type, $settings ) {
        if ( 'weekly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_general_events_settings( $settings );
        
        do_action( 'wpcalendars_builder_weekly_calendar_settings_events', $settings );
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
        $default_settings = $default_settings['weekly'];

        if ( $settings === array() ) {
            $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( $calendar_id ), $calendar_id );
            $settings = $calendar['settings'];
        }

        $settings = wp_parse_args( $settings, $default_settings );

        if ( '' === $settings['start_date'] && '' === $settings['end_date'] ) {
            $settings['start_date'] = date( 'Y-m-d', strtotime( 'this week' ) );
            $settings['end_date']   = date( 'Y-m-d', strtotime( $settings['start_date'] . ' +6 days' ) );
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

        $start_date = explode( '-', $settings['start_date'] );
        $end_date   = explode( '-', $settings['end_date'] );

        $start_year = intval( $start_date[0] );
        $start_month = intval( $start_date[1] );
        $start_day = intval( $start_date[2] );

        $end_year = intval( $end_date[0] );
        $end_month = intval( $end_date[1] );
        $end_day = intval( $end_date[2] );

        $current_week_events = wpcalendars_get_events( $event_args );

        $daily_events = array();

        if ( $start_year < $end_year ) {
            for ( $current_day = $start_day; $current_day <= 31; $current_day++ ) {
                $current_date = sprintf( '%s-12-%s', $start_year, zeroise( $current_day, 2 ) );

                $current_date_events = array();

                foreach ( $current_week_events as $event ) {
                    if ( $current_date >= $event['start_date'] && $current_date <= $event['end_date'] ) {
                        $current_date_events[] = $event;
                    }
                }

                $daily_events[$current_day] = $current_date_events;
            }

            for ( $current_day = 1; $current_day <= $end_day; $current_day++ ) {
                $current_date = sprintf( '%s-01-%s', $end_year, zeroise( $current_day, 2 ) );

                $current_date_events = array();

                foreach ( $current_week_events as $event ) {
                    if ( $current_date >= $event['start_date'] && $current_date <= $event['end_date'] ) {
                        $current_date_events[] = $event;
                    }
                }

                $daily_events[$current_day] = $current_date_events;
            }
        } else {
            if ( $start_month === $end_month ) {
                for ( $current_day = $start_day; $current_day <= $end_day; $current_day++ ) {
                    $current_date = sprintf( '%s-%s-%s', $start_year, zeroise( $start_month, 2 ), zeroise( $current_day, 2 ) );

                    $current_date_events = array();

                    foreach ( $current_week_events as $event ) {
                        if ( $current_date >= $event['start_date'] && $current_date <= $event['end_date'] ) {
                            $current_date_events[] = $event;
                        }
                    }

                    $daily_events[$current_day] = $current_date_events;
                }
            } else {
                $days_in_month = date( 't', mktime( 0, 0, 0, $start_month, 1, $start_year ) );

                for ( $current_day = $start_day; $current_day <= $days_in_month; $current_day++ ) {
                    $current_date = sprintf( '%s-%s-%s', $start_year, zeroise( $start_month, 2 ), zeroise( $current_day, 2 ) );

                    $current_date_events = array();

                    foreach ( $current_week_events as $event ) {
                        if ( $current_date >= $event['start_date'] && $current_date <= $event['end_date'] ) {
                            $current_date_events[] = $event;
                        }
                    }

                    $daily_events[$current_day] = $current_date_events;
                }

                for ( $current_day = 1; $current_day <= $end_day; $current_day++ ) {
                    $current_date = sprintf( '%s-%s-%s', $start_year, zeroise( $end_month, 2 ), zeroise( $current_day, 2 ) );

                    $current_date_events = array();

                    foreach ( $current_week_events as $event ) {
                        if ( $current_date >= $event['start_date'] && $current_date <= $event['end_date'] ) {
                            $current_date_events[] = $event;
                        }
                    }

                    $daily_events[$current_day] = $current_date_events;
                }
            }
        }

        $weekday_ids = array( 1, 2, 3, 4, 5, 6, 0 );

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

        do_action( 'wpcalendars_weekly_calendar_before_navigation', $calendar_id, $settings );
        
        echo '<div class="wpcalendars-nav-container">';
        do_action( 'wpcalendars_weekly_calendar_navigation', $calendar_id, $settings );
        echo '</div>';
        
        do_action( 'wpcalendars_weekly_calendar_after_navigation', $calendar_id, $settings );

        echo '<div class="wpcalendars-weekly-calendar">';
        echo '<table><tbody>';

        // Heading Day Name

        echo '<tr><td><div class="wpcalendars-weekly-calendar-weekday">&nbsp;</div></td>';

        foreach ( $weekday_ids as $weekday_id ) {
            $weekday_name = $wp_locale->get_weekday( $weekday_id );

            if ( 'one-letter' === $settings['weekday_format'] ) {
                $weekday_name = $wp_locale->get_weekday_initial( $weekday_name );
            } elseif ( 'three-letter' === $settings['weekday_format'] ) {
                $weekday_name = $wp_locale->get_weekday_abbrev( $weekday_name );
            }

            echo '<td>';
            printf( '<div class="wpcalendars-weekly-calendar-weekday">%s</div>', $weekday_name );
            echo '</td>';
        }

        echo '</tr>';

        // All Day Events

        echo '<tr>';
        printf( '<td class="wpcalendars-weekly-calendar-allday"><div class="wpcalendars-weekly-calendar-hour">%s</div></td>', __( 'All Day', 'wpcalendars' ) );

        foreach ( $daily_events as $events ) {
            echo '<td>';

            if ( empty( $events ) ) {
                echo '<div class="wpcalendars-weekly-calendar-no-events"></div>';
            } else {
                echo '<div class="wpcalendars-weekly-calendar-events wpcalendars-weekly-calendar-events-allday">';

                foreach ( $events as $event ) {
                    if ( 'Y' === $event['all_day'] ) {
                        $date_class = array( 
                            'wpcalendars-weekly-calendar-event',
                            sprintf( 'wpcalendars-event-category-%s', $event['category_id'] )
                        );

                        $settings['group'] = 'general-date';
                        $settings['current_events'] = array( $event );

                        $date_class = apply_filters( 'wpcalendars_weekly_calendar_date_class', $date_class, $calendar_id, $settings );
                        $date_attr = apply_filters( 'wpcalendars_weekly_calendar_date_attr', array(), $calendar_id, $settings );

                        if ( 'Y' === $event['disable_event_details'] ) {
                            printf( '<div class="%s" %s><span>%s</span></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), $event['event_title'] );
                        } else {
                            printf( '<div class="%s" %s><a href="%s">%s</a></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), wpcalendars_get_permalink( $event ), $event['event_title'] );
                        }
                    }
                }

                echo '</div>';
            }

            echo '</td>';
        }

        echo '</tr>';

        // Hourly Events

        echo '<tr>';
        echo '<td>';

        for ( $i = 1; $i <= 24; $i++ ) {
            printf( '<div class="wpcalendars-weekly-calendar-hour">%s:00</div>', zeroise( $i, 2 ) );
        }

        echo '</td>';

        foreach ( $daily_events as $events ) {
            $event_times = array();

            foreach ( $events as $i => $event ) {
                if ( 'N' === $event['all_day'] ) {
                    $event_times[$i] = strtotime( $event['start_time'] );
                }
            }

            asort( $event_times );

            echo '<td>';

            if ( empty( $event_times ) ) {
                echo '<div class="wpcalendars-weekly-calendar-no-events"></div>';
            } else {
                echo '<div class="wpcalendars-weekly-calendar-events wpcalendars-weekly-calendar-events-hour">';

                $counter = 0;
                $end_hour_check = 0;

                foreach ( $event_times as $id => $event_time ) {
                    $date_class = array( 
                        'wpcalendars-weekly-calendar-event',
                        'wpcalendars-weekly-calendar-event-hour',
                        sprintf( 'wpcalendars-event-category-%s', $events[$id]['category_id'] )
                    );

                    $start_hour = ( strtotime( $events[$id]['start_time'] ) - strtotime( date( 'Y-m-d' ) ) ) / ( 60 * 60 );
                    $end_hour = ( strtotime( $events[$id]['end_time'] ) - strtotime( date( 'Y-m-d' ) ) ) / ( 60 * 60 );

                    if ( $start_hour > $end_hour_check ) {
                        $counter = 0;
                    }

                    $style = sprintf( 'top:%spx;left:%spx;height:%spx;z-index:1000%s', ( $start_hour * 40 ) - 15, $counter * 15, ( $end_hour - $start_hour ) * 40, $counter );

                    $end_hour_check = $end_hour;
                    $counter++;

                    $settings['group'] = 'general-date';
                    $settings['current_events'] = array( $events[$id] );

                    $date_class = apply_filters( 'wpcalendars_weekly_calendar_date_class', $date_class, $calendar_id, $settings );
                    $date_attr = apply_filters( 'wpcalendars_weekly_calendar_date_attr', array(), $calendar_id, $settings );

                    if ( 'Y' === $events[$id]['disable_event_details'] ) {
                        printf( '<div style="%s" class="%s" %s><span>%s</span></div>', $style, implode( ' ', $date_class ), implode( ' ', $date_attr ), $events[$id]['event_title'] );
                    } else {
                        printf( '<div style="%s" class="%s" %s><a href="%s">%s</a></div>', $style, implode( ' ', $date_class ), implode( ' ', $date_attr ), wpcalendars_get_permalink( $events[$id] ), $events[$id]['event_title'] );
                    }
                }

                echo '</div>';
            }

            echo '</td>';
        }

        echo '</tr>';
        echo '</tbody></table>';
        echo '</div>';

        if ( count( $current_week_events ) > 0 ) {
            do_action( 'wpcalendars_weekly_calendar_after', $calendar_id, $settings, $current_week_events );
        } else {
            printf( '<div class="wpcalendars-no-events">%s</div>', esc_html__( 'No Events', 'wpcalendars' ) );
        }

        echo '</div>';
    }
    
    /**
     * Show category listings
     * @param type $calendar_id
     * @param type $settings
     * @param type $events
     */
    public function show_category_listings( $calendar_id, $settings, $events ) {
        wpcalendars_show_category_listings( $settings, $events );
    }
    
    /**
     * Show event listings
     * @param type $calendar_id
     * @param type $settings
     * @param type $events
     */
    public function show_event_listings( $calendar_id, $settings, $events ) {
        wpcalendars_show_event_listings( $settings, $events );
    }
    
    /**
     * Add tooltip class
     * @param string $classes
     * @param array $args
     * @return array
     */
    public function tooltip_class( $classes, $calendar_id, $args ) {
        if ( 'N' === $args['enable_daily_event_nav'] ) {
            return $classes;
        }
        
        if ( 'general-date' === $args['group'] && ! empty( $args['current_events'] ) && 'tooltip' === $args['daily_event_nav'] ) {
            $classes[] = 'wpcalendars-tooltip';
        }
        
        return $classes;
    }
    
    /**
     * Add popup class
     * @param string $classes
     * @param array $args
     * @return array
     */
    public function popup_class( $classes, $calendar_id, $args ) {
        if ( 'N' === $args['enable_daily_event_nav'] ) {
            return $classes;
        }
        
        if ( 'general-date' === $args['group'] && ! empty( $args['current_events'] ) && 'popup' === $args['daily_event_nav'] ) {
            $classes[] = 'wpcalendars-popup';
        }
        
        return $classes;
    }
    
    /**
     * Add tooltip attributes
     * @param array $attr
     * @param array $args
     * @return array
     */
    public function tooltip_attr( $attr, $calendar_id, $args ) {
        if ( 'N' === $args['enable_daily_event_nav'] ) {
            return $attr;
        }
        
        if ( 'general-date' === $args['group'] && ! empty( $args['current_events'] ) && 'tooltip' === $args['daily_event_nav'] ) {
            $events = array();
            
            foreach ( $args['current_events'] as $event ) {
                $events[] = array( 
                    'event_id'  => $event['event_id'], 
                    'detail_id' => $event['detail_id'] 
                );
            }
            
            $attr[] = sprintf( 'data-page="%s"', get_the_ID() );
            $attr[] = sprintf( 'data-calendar="%s"', $calendar_id );
            $attr[] = sprintf( 'data-events="%s"', base64_encode( json_encode( $events ) ) );
            $attr[] = sprintf( 'data-theme="%s"', $args['tooltip_theme'] );
            $attr[] = sprintf( 'data-trigger="%s"', $args['tooltip_trigger'] );
        }
        
        return $attr;
    }
    
    /**
     * Add popup attributes
     * @param array $attr
     * @param array $args
     * @return array
     */
    public function popup_attr( $attr, $calendar_id, $args ) {
        if ( 'N' === $args['enable_daily_event_nav'] ) {
            return $attr;
        }
        
        if ( 'general-date' === $args['group'] && ! empty( $args['current_events'] ) && 'popup' === $args['daily_event_nav'] ) {
            $events = array();
            
            foreach ( $args['current_events'] as $event ) {
                $events[] = array( 
                    'event_id'  => $event['event_id'], 
                    'detail_id' => $event['detail_id'] 
                );
            }
            
            $attr[] = sprintf( 'data-page="%s"', get_the_ID() );
            $attr[] = sprintf( 'data-calendar="%s"', $calendar_id );
            $attr[] = sprintf( 'data-events="%s"', base64_encode( json_encode( $events ) ) );
        }
        
        return $attr;
    }
    
    /**
     * Show prev/next navigation
     * @param type $calendar_id
     * @param type $settings
     */
    public function show_prevnext_navigation( $calendar_id, $settings ) {
        if ( 'Y' === $settings['prevnext_nav'] ) {
            $prev_start_date = date( 'Y-m-d', strtotime( $settings['start_date'] . ' -7 days' ) );
            $prev_end_date   = date( 'Y-m-d', strtotime( $prev_start_date . ' +6 days' ) );

            $next_start_date = date( 'Y-m-d', strtotime( $settings['end_date'] . ' +1 days' ) );
            $next_end_date   = date( 'Y-m-d', strtotime( $next_start_date . ' +6 days' ) );

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

WPCalendars_Weekly_View_Calendar::instance();
