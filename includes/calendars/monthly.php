<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Monthly_View_Calendar {
    
    private static $_instance = NULL;
   
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'wpcalendars_builder_print_sidebar_settings_general',    array( $this, 'builder_settings_general' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_navigation', array( $this, 'builder_settings_navigation' ), 10, 2 );
        add_action( 'wpcalendars_builder_print_sidebar_settings_events',     array( $this, 'builder_settings_events' ), 10, 2 );
        add_action( 'wpcalendars_monthly_calendar_navigation',               array( $this, 'show_prevnext_navigation' ), 10, 3 );
        add_action( 'wpcalendars_monthly_calendar_navigation',               array( $this, 'show_calendar_heading' ), 10, 3 );
        add_action( 'wpcalendars_monthly_calendar_after',                    array( $this, 'show_category_listings' ), 10, 4 );
        add_action( 'wpcalendars_monthly_calendar_after',                    array( $this, 'show_event_listings' ), 10, 4 );
        
        add_filter( 'wpcalendars_monthly_calendar_date_class', array( $this, 'tooltip_class' ), 10, 3 );
        add_filter( 'wpcalendars_monthly_calendar_date_class', array( $this, 'popup_class' ), 10, 3 );
        add_filter( 'wpcalendars_monthly_calendar_date_attr',  array( $this, 'tooltip_attr' ), 10, 3 );
        add_filter( 'wpcalendars_monthly_calendar_date_attr',  array( $this, 'popup_attr' ), 10, 3 );
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
        if ( 'monthly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        ?>
        <h3><?php echo esc_html__( 'General Settings', 'wpcalendars' ) ?></h3>
        
        <?php
        wpcalendars_builder_month_format_settings( $settings );
        wpcalendars_builder_weekday_format_settings( $settings );
        wpcalendars_builder_weekday_position_settings( $settings );
        wpcalendars_builder_weekday_start_settings( $settings );
        wpcalendars_builder_date_format_settings( $settings );
        wpcalendars_builder_time_format_settings( $settings );
        wpcalendars_builder_heading_settings( $settings );
        wpcalendars_builder_category_listings_settings( $settings );
        wpcalendars_builder_event_listings_settings( $settings );
        
        do_action( 'wpcalendars_builder_monthly_calendar_settings_general', $settings );
    }
    
    /**
     * Render builder navigation settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_navigation( $calendar_type, $settings ) {
        if ( 'monthly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_prevnext_navigation_settings( $settings );
        wpcalendars_builder_daily_event_navigation_settings( $settings );
        
        do_action( 'wpcalendars_builder_monthly_calendar_settings_navigation', $settings );
    }
    
    /**
     * Render builder events settings
     * @param type $calendar_type
     * @param type $settings
     * @return type
     */
    public function builder_settings_events( $calendar_type, $settings ) {
        if ( 'monthly' !== $calendar_type ) {
            return;
        }
        
        $default_settings = wpcalendars_get_default_calendar_settings();
        $settings = wp_parse_args( $settings, $default_settings[$calendar_type] );
        
        wpcalendars_builder_general_events_settings( $settings );
        
        do_action( 'wpcalendars_builder_monthly_calendar_settings_events', $settings );
    }
    
    /**
     * Display calendar output
     * @global type $wp_locale
     * @param type $calendar_id
     * @param type $settings
     */
    public static function output( $calendar_id, $settings = array() ) {
        $default_settings = wpcalendars_get_default_calendar_settings();
        $default_settings = $default_settings['monthly'];

        if ( $settings === array() ) {
            $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( $calendar_id ), $calendar_id );
            $settings = $calendar['settings'];
        }

        $settings = wp_parse_args( $settings, $default_settings );

        $current_year  = date( 'Y' );
        $current_month = date( 'm' );

        if ( '' === $settings['start_date'] && '' === $settings['end_date'] ) {
            $start_month = $current_month;
            $start_year  = $current_year;
            $end_month   = $current_month;
            $end_year    = $current_year;

            $days_in_month = date( 't', mktime( 0, 0, 0, $start_month, 1, $start_year ) );

            $settings['start_date'] = sprintf( '%s-%s-01', $start_year, zeroise( $start_month, 2 ) );
            $settings['end_date']   = sprintf( '%s-%s-%s', $start_year, zeroise( $start_month, 2 ), $days_in_month );
        }

        $start_date = explode( '-', $settings['start_date'] );
        $end_date   = explode( '-', $settings['end_date'] );

        $start_month = intval( $start_date[1] );
        $start_year  = intval( $start_date[0] );
        $end_month   = intval( $end_date[1] );
        $end_year    = intval( $end_date[0] );

        // List Month

        $list_month = array();

        if ( $start_year === $end_year ) {
            for ( $i = $start_month; $i <= $end_month; $i++ ) {
                $list_month[] = array(
                    'year'  => $start_year,
                    'month' => zeroise( $i, 2)
                );
            }
        } else {
            for ( $i = $start_month; $i <= 12; $i++ ) {
                $list_month[] = array(
                    'year'  => $start_year,
                    'month' => zeroise( $i, 2)
                );
            }

            if ( ( $end_year - $start_year ) > 1 ) {
                for ( $i = $start_year + 1; $i < $end_year; $i++ ) {
                    for ( $j = 1; $j <= 12; $j++ ) {
                        $list_month[] = array(
                            'year'  => $i,
                            'month' => zeroise( $j, 2)
                        );
                    }
                }
            }

            for ( $i = 1; $i <= $end_month; $i++ ) {
                $list_month[] = array(
                    'year'  => $end_year,
                    'month' => zeroise( $i, 2)
                );
            }
        }

        // Daily Events

        $events = wpcalendars_get_events( apply_filters( 'wpcalendars_events_args', array(
            'start_date'         => $settings['start_date'],
            'end_date'           => $settings['end_date'],
            'categories'         => $settings['categories'],
            'tags'               => $settings['tags'],
            'venues'             => $settings['venues'],
            'organizers'         => $settings['organizers'],
            'exclude_events'     => $settings['exclude_events'],
            'show_hidden_events' => $settings['show_hidden_events'],
        ), $settings ) );

        $daily_events = array();

        if ( $start_year === $end_year ) {
            for ( $i = $start_month; $i <= $end_month; $i++ ) {
                $days_in_month = date( 't', mktime( 0, 0, 0, $i, 1, $start_year ) );

                $current_month_daily_events = array();

                for ( $current_date = 1; $current_date <= $days_in_month; $current_date++ ) {
                    $current_date_events = array();

                    foreach ( $events as $event ) {
                        $str_date = sprintf( '%s-%s-%s', $start_year, zeroise( $i, 2 ), zeroise( $current_date, 2 ) );

                        if ( strtotime( $event['start_date'] ) <= strtotime( $str_date ) && strtotime( $event['end_date'] ) >= strtotime( $str_date ) ) {
                            $current_date_events[] = $event;
                        }
                    }

                    $current_month_daily_events[$current_date] = $current_date_events;
                }

                $daily_events[$start_year][$i] = $current_month_daily_events;
            }
        }

        $classes = array(
            sprintf( 'wpcalendars-monthly-calendar-%s', $settings['weekday_position'] ),
        );

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

        do_action( 'wpcalendars_monthly_calendar_before_navigation', $calendar_id, $settings );
        
        echo '<div class="wpcalendars-nav-container">';
        do_action( 'wpcalendars_monthly_calendar_navigation', $calendar_id, $settings );
        echo '</div>';
        
        do_action( 'wpcalendars_monthly_calendar_after_navigation', $calendar_id, $settings );

        echo '<div class="' . implode( ' ', $classes ). '">';

        // Monthly Calendara

        echo '<div class="wpcalendars-monthly-calendar-calendars">';

        global $wp_locale;

        foreach ( $list_month as $list ) {
            $settings['year']  = $list['year'];
            $settings['month'] = $list['month'];

            $single_month = array();

            $month_name = $wp_locale->get_month( $settings['month'] );

            if ( 'three-letter' === $settings['month_format'] ) {
                $month_name = $wp_locale->get_month_abbrev( $month_name );
            }

            $single_month['year']       = $settings['year'];
            $single_month['month']      = $settings['month'];
            $single_month['month_name'] = $month_name;

            // Weekdays

            $weekdays = array();
            $weekday_ids = array();

            if ( $settings['weekday_start'] > 0 ) {
                for ( $i = (int) $settings['weekday_start']; $i <= 6; $i++ ) {
                    $weekday_ids[] = $i;
                }

                for ( $i = 0; $i < (int) $settings['weekday_start']; $i++ ) {
                    $weekday_ids[] = $i;
                }
            } else {
                for ( $i = 0; $i < 7; $i++ ) {
                    $weekday_ids[] = $i;
                }
            }

            foreach ( $weekday_ids as $weekday_id ) {
                $weekday_name = $wp_locale->get_weekday( $weekday_id );

                if ( 'one-letter' === $settings['weekday_format'] ) {
                    $weekday_name = $wp_locale->get_weekday_initial( $weekday_name );
                } elseif ( 'three-letter' === $settings['weekday_format'] ) {
                    $weekday_name = $wp_locale->get_weekday_abbrev( $weekday_name );
                }

                $weekdays[] = array(
                    'weekday'      => $weekday_id,
                    'weekday_name' => $weekday_name
                );
            }

            $single_month['weekdays'] = $weekdays;

            // Days in a Week

            $current_date   = 1;
            $weekday_number = date( 'w', mktime( 0, 0, 0, $settings['month'], $current_date, $settings['year'] ) );
            $days_in_month  = date( 't', mktime( 0, 0, 0, $settings['month'], 1, $settings['year'] ) );

            if ( $settings['month'] > 1 ) {
                $days_in_before_month  = date( 't', mktime( 0, 0, 0, $settings['month'] - 1, 1, $settings['year'] ) );
            } else {
                $days_in_before_month  = date( 't', mktime( 0, 0, 0, 12, 1, $settings['year'] - 1 ) );
            }

            $start     = false;
            $prev_date = $days_in_before_month;
            $next_date = 1;

            foreach ( $weekday_ids as $weekday_id ) {
                if ( (int) $weekday_id === (int) $weekday_number ) {
                    break;
                }

                $prev_date--;
            }

            for ( $i = 0; $i < 6; $i++ ) {
                $week_dates = array();

                foreach ( $weekday_ids as $weekday_id ) {
                    if ( (int) $weekday_id === (int) $weekday_number ) {
                        $start = true;
                    }

                    if ( $start && $current_date <= $days_in_month ) {
                        $current_weekday_number = date( 'w', mktime( 0, 0, 0, $settings['month'], $current_date, $settings['year'] ) );

                        $year  = intval( $settings['year'] );
                        $month = intval( $settings['month'] );

                        $week_dates[] = array(
                            'content'        => $current_date,
                            'group'          => 'general-date',
                            'weekday_number' => $current_weekday_number,
                            'current_events' => $daily_events[$year][$month][$current_date]
                        );

                        $current_date++;
                    } else {
                        if ( $start ) {
                            $week_dates[] = array(
                                'content' => $next_date++,
                                'group'   => 'prevnext-date',
                            );
                        } else {
                            $week_dates[] = array(
                                'content' => ++$prev_date,
                                'group'   => 'prevnext-date',
                            );
                        }
                    }
                }

                $single_month['week_dates'][] = $week_dates;
            }

            printf( '<div id="wpcalendars-monthly-calendar-%s-%s" class="wpcalendars-monthly-calendar">', $settings['year'], $settings['month'] );
            echo '<div class="wpcalendars-monthly-calendar-inner">';
            echo '<table>';
            echo '<tbody>';

            if ( 'left' === $settings['weekday_position'] ) {
                for ( $i = 0; $i < 7; $i++ ) {
                    echo '<tr>';

                    printf( '<td><div class="wpcalendars-monthly-calendar-weekday wpcalendars-monthly-calendar-weekday-%s">%s</div></td>', $single_month['weekdays'][$i]['weekday'], $single_month['weekdays'][$i]['weekday_name'] );

                    for ( $j = 0; $j < 6; $j++ ) {
                        $date_class = sprintf( 'wpcalendars-monthly-calendar-%s', $single_month['week_dates'][$j][$i]['group'] );

                        $settings['group'] = $single_month['week_dates'][$j][$i]['group'];
                        $settings['content'] = $single_month['week_dates'][$j][$i]['content'];

                        echo '<td>';
                        printf( '<div class="%s">%s</div>', $date_class, $single_month['week_dates'][$j][$i]['content'] );

                        if ( ! empty( $single_month['week_dates'][$j][$i]['current_events'] ) ) {
                            echo '<div class="wpcalendars-monthly-calendar-events">';

                            foreach ( $single_month['week_dates'][$j][$i]['current_events'] as $event ) {
                                $date_class = array( 
                                    'wpcalendars-monthly-calendar-event', 
                                    sprintf( 'wpcalendars-event-category-%s', $event['category_id'] ) 
                                );

                                $settings['current_events'] = array( $event );

                                $date_class = apply_filters( 'wpcalendars_monthly_calendar_date_class', $date_class, $calendar_id, $settings );
                                $date_attr  = apply_filters( 'wpcalendars_monthly_calendar_date_attr', array(), $calendar_id, $settings );

                                if ( 'Y' === $event['disable_event_details'] ) {
                                    printf( '<div class="%s" %s><span>%s</span></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), $event['event_title'] );
                                } else {
                                    printf( '<div class="%s" %s><a href="%s">%s</a></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), wpcalendars_get_permalink( $event ), $event['event_title'] );
                                }
                            }

                            echo '</div>';
                        }

                        echo '</td>';
                    }

                    echo '</tr>';
                }


            } else {
                echo '<tr>';

                foreach ( $single_month['weekdays'] as $weekday ) {
                    printf( '<td><div class="wpcalendars-monthly-calendar-weekday wpcalendars-monthly-calendar-weekday-%s">%s</div></td>', $weekday['weekday'], $weekday['weekday_name'] );
                }

                echo '</tr>';

                foreach ( $single_month['week_dates'] as $data_row ) {
                    echo '<tr>';

                    foreach ( $data_row as $column ) {
                        $date_class = sprintf( 'wpcalendars-monthly-calendar-%s', $column['group'] );

                        $settings['group'] = $column['group'];
                        $settings['content'] = $column['content'];

                        echo '<td>';
                        printf( '<div class="%s">%s</div>', $date_class, $column['content'] );

                        if ( ! empty( $column['current_events'] ) ) {
                            echo '<div class="wpcalendars-monthly-calendar-events">';

                            foreach ( $column['current_events'] as $event ) {
                                $date_class = array( 
                                    'wpcalendars-monthly-calendar-event', 
                                    sprintf( 'wpcalendars-event-category-%s', $event['category_id'] ) 
                                );

                                $settings['current_events'] = array( $event );

                                $date_class = apply_filters( 'wpcalendars_monthly_calendar_date_class', $date_class, $calendar_id, $settings );
                                $date_attr  = apply_filters( 'wpcalendars_monthly_calendar_date_attr', array(), $calendar_id, $settings );

                                if ( 'Y' === $event['disable_event_details'] ) {
                                    printf( '<div class="%s" %s><span>%s</span></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), $event['event_title'] );
                                } else {
                                    printf( '<div class="%s" %s><a href="%s">%s</a></div>', implode( ' ', $date_class ), implode( ' ', $date_attr ), wpcalendars_get_permalink( $event ), $event['event_title'] );
                                }
                            }

                            echo '</div>';
                        }

                        echo '</td>';
                    }

                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // Calendar End

        if ( count( $events ) > 0 ) {
            do_action( 'wpcalendars_monthly_calendar_after', $calendar_id, $settings, $events );
        }

        echo '</div>';

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
     * Show events listings
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
            $start_date = explode( '-', $settings['start_date'] );
            $end_date   = explode( '-', $settings['end_date'] );

            $start_month = intval( $start_date[1] );
            $start_year  = intval( $start_date[0] );
            $end_month   = intval( $end_date[1] );
            $end_year    = intval( $end_date[0] );

            if ( zeroise( $start_month, 2 ) === '01' ) {
                $prev_start_date = sprintf( '%s-12-01', $start_year - 1 );
                $prev_end_date   = sprintf( '%s-12-31', $start_year - 1 );
            } else {
                $days_in_month   = date( 't', mktime( 0, 0, 0, $start_month - 1, 1, $start_year ) );
                $prev_start_date = sprintf( '%s-%s-01', $start_year, zeroise( $start_month - 1, 2 ) );
                $prev_end_date   = sprintf( '%s-%s-%s', $start_year, zeroise( $start_month - 1, 2 ), $days_in_month );
            }

            if ( zeroise( $start_month, 2 ) === '12' ) {
                $next_start_date = sprintf( '%s-01-01', $start_year + 1 );
                $next_end_date   = sprintf( '%s-01-31', $start_year + 1 );
            } else {
                $days_in_month   = date( 't', mktime( 0, 0, 0, $start_month + 1, 1, $start_year ) );
                $next_start_date = sprintf( '%s-%s-01', $start_year, zeroise( $start_month + 1, 2 ) );
                $next_end_date   = sprintf( '%s-%s-%s', $start_year, zeroise( $start_month + 1, 2 ), $days_in_month );
            }

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

WPCalendars_Monthly_View_Calendar::instance();