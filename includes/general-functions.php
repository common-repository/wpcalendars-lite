<?php
/**
 * Get the plugin settings
 */
function wpcalendars_get_settings() {
    if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'pro/config/plugin-settings.php' ) ) {
        $settings = require WPCALENDARS_PLUGIN_DIR . 'pro/config/plugin-settings.php';
    } else {
        $settings = require WPCALENDARS_PLUGIN_DIR . 'config/plugin-settings.php';
    }
    
    $settings = apply_filters( 'wpcalendars_settings', $settings );
    
    return $settings;
}

/**
 * Get the default of plugin settings
 * @return array
 */
function wpcalendars_get_default_settings() {
    $default_settings = array();
    $settings = wpcalendars_get_settings();

    foreach ( $settings as $tab => $tab_settings ) {
        $tmp_options = array();

        foreach ( $tab_settings as $key => $setting ) {
            $tmp_options[$key] = $setting['default_value'];
        }

        $default_settings[$tab] = $tmp_options;
    }
    
    return $default_settings;
}

/**
 * Get the value of plugin settings
 * @global type $wpcalendars_options
 * @param string $section
 * @param string $key
 * @return string Settings value or False if the setting not found
 */
function wpcalendars_settings_value( $section = false, $key = false ) {
    global $wpcalendars_options;
    
    $default_settings = wpcalendars_get_default_settings();
            
    if ( empty( $wpcalendars_options ) ) {
        $wpcalendars_options = get_option( 'wpcalendars_options', $default_settings );
    }
    
    if ( isset( $wpcalendars_options[$section][$key] ) ) {
        return $wpcalendars_options[$section][$key];
    } elseif ( isset( $default_settings[$section][$key] ) ) {
        return $default_settings[$section][$key];
    } else {
        return false;
    }
}

/**
 * Get calendar type options
 * @return array
 */
function wpcalendars_get_calendar_type_options() {
    return apply_filters( 'wpcalendars_calendar_type_options', array(
        'monthly' => array(
            'name'        => __( 'Monthly View Calendar', 'wpcalendars' ),
            'description' => __( 'Create your events calendar for the month. You can choose the start of the week and the calendar updates automatically.', 'wpcalendars' )
        ),
        'multiple-months' => array(
            'name'        => __( 'Multiple Months View Calendar', 'wpcalendars' ),
            'description' => __( 'Create your events calendar for multiple months. You can choose the start of the week and the calendar updates automatically.', 'wpcalendars' )
        ),
        'weekly' => array(
            'name'        => __( 'Weekly View Calendar', 'wpcalendars' ),
            'description' => __( 'Create your events calendar for the week.', 'wpcalendars' )
        ),
        'daily' => array(
            'name'        => __( 'Daily View Calendar', 'wpcalendars' ),
            'description' => __( 'A daily calendar shows one day at a time, which can help you focus on the events for those 24 hours.', 'wpcalendars' )
        ),
        'list' => array(
            'name'        => __( 'List View Calendar', 'wpcalendars' ),
            'description' => __( 'A list view calendar displays events in a simple vertical list for a specific interval of time.', 'wpcalendars' )
        ),
    ) );
}

/**
 * Get the list of calendars for Gutenberg block
 * @return array
 */
function wpcalendars_options_for_gutenberg_block() {
    $calendars = wpcalendars_get_calendars();
    
    $gutenberg_options = array(
        array(
            'value' => '',
            'label' => __( 'Select Calendar', 'wpcalendars' )
        )
    );
    
    foreach ( $calendars as $calendar ) {
        $gutenberg_options[] = array(
            'value' => $calendar['calendar_id'],
            'label' => $calendar['name']
        );
    }
    
    return $gutenberg_options;
}

/**
 * Get weekday options
 * @global array $wp_locale
 * @return array
 */
function wpcalendars_get_weekday_options() {
    global $wp_locale;
    
    $weekdays = array();
    
    for ( $i = 0; $i < 7; $i = $i +1 ) {
        $weekdays[] = $wp_locale->get_weekday($i);
    }
    
    return $weekdays;
}

/**
 * Get month options
 * @global array $wp_locale
 * @return array
 */
function wpcalendars_get_month_options() {
    global $wp_locale;
    
    $months = array();
    
    for ( $i = 1; $i < 13; $i = $i +1 ) {
        $monthnum = zeroise($i, 2);
        $monthtext = $wp_locale->get_month( $i );
        $months[$monthnum] = $monthtext;
    }

    return $months;
}

/**
 * Convert HEX color to RGB
 * @param string $color
 * @return array
 */
function wpcalendars_hex2rgb( $color ) {
	$color = trim( $color, '#' );

	if ( strlen( $color ) === 3 ) {
		$r = hexdec( substr( $color, 0, 1 ).substr( $color, 0, 1 ) );
		$g = hexdec( substr( $color, 1, 1 ).substr( $color, 1, 1 ) );
		$b = hexdec( substr( $color, 2, 1 ).substr( $color, 2, 1 ) );
	} else if ( strlen( $color ) === 6 ) {
		$r = hexdec( substr( $color, 0, 2 ) );
		$g = hexdec( substr( $color, 2, 2 ) );
		$b = hexdec( substr( $color, 4, 2 ) );
	} else {
		return array();
	}

	return array( 'red' => $r, 'green' => $g, 'blue' => $b );
}

/**
 * Check whether the current day is weekend or not
 * @param integer $weekday_number
 * @param array $weekend_days
 * @return boolean
 */
function wpcalendars_is_weekend( $weekday_number, $weekend_days ) {
    if ( in_array( $weekday_number, $weekend_days ) ) {
        return true;
    }
    
    return false;
}

/**
 * Get form data
 * @param array $form_post
 * @return array
 */
function wpcalendars_get_form_data( $form_post ) {
    $data = array();

    if ( !is_null( $form_post ) && $form_post ) {
        foreach ( $form_post as $post_input_data ) {
            // For input names that are arrays (e.g. `menu-item-db-id[3][4][5]`),
            // derive the array path keys via regex and set the value in $_POST.
            preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

            $array_bits = array( $matches[1] );

            if ( isset( $matches[3] ) ) {
                $array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
            }

            $new_post_data = array();

            // Build the new array value from leaf to trunk.
            for ( $i = count( $array_bits ) - 1; $i >= 0; $i -- ) {
                if ( $i === count( $array_bits ) - 1 ) {
                    $new_post_data[$array_bits[$i]] = wp_slash( $post_input_data->value );
                } else {
                    $new_post_data = array(
                        $array_bits[$i] => $new_post_data,
                    );
                }
            }

            $data = array_replace_recursive( $data, $new_post_data );
        }
    }
    
    return $data;
}

/**
 * Get formatted event date
 * @global array $wp_locale
 * @param type $start_date
 * @param type $end_date
 * @param type $date_format
 * @param type $show_year
 * @return type
 */
function wpcalendars_format_date( $start_date, $end_date, $date_format, $show_year ) {
    global $wp_locale;
    
    $formatted_date = '';

    if ( '' !== $start_date && '' !== $end_date ) {
        if ( $start_date === $end_date ) {
            $start_date = explode( '-', $start_date );

            if ( 'short' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s/%s/%s', $start_date[1], $start_date[2], $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s/%s', $start_date[1], $start_date[2] );
                }
            } elseif ( 'medium' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s %s, %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2] );
                }
            } elseif ( 'long' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s %s, %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2] );
                }
            } elseif ( 'short-alt' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s/%s/%s', $start_date[2], $start_date[1], $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s/%s', $start_date[2], $start_date[1] );
                }
            } elseif ( 'medium-alt' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s %s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ) );
                }
            } elseif ( 'long-alt' === $date_format ) {
                if ( 'Y' === $show_year ) {
                    $formatted_date .= sprintf( '%s %s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ), $start_date[0] );
                } else {
                    $formatted_date .= sprintf( '%s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ) );
                }
            }
        } else {
            $start_date = explode( '-', $start_date );
            $end_date = explode( '-', $end_date );

            if ( $start_date[0] === $end_date[0] ) { // Same Year
                if ( $start_date[1] === $end_date[1] ) { // Same Month
                    if ( 'short' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[1], $start_date[2], $start_date[0], $end_date[1], $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[1], $start_date[2], $end_date[1], $end_date[2] );
                        }
                    } elseif ( 'medium' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s, %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], (int) $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], (int) $end_date[2] );
                        }
                    } elseif ( 'long' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s, %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], (int) $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], (int) $end_date[2] );
                        }
                    } elseif ( 'short-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[2], $start_date[1], $start_date[0], $end_date[2], $start_date[1], $start_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[2], $start_date[1], $end_date[2], $start_date[1] );
                        }
                    } elseif ( 'medium-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s - %s %s %s', (int) $start_date[2], (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), $start_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s - %s %s', (int) $start_date[2], (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ) );
                        }
                    } elseif ( 'long-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s - %s %s %s', (int) $start_date[2], (int) $end_date[2], $wp_locale->get_month( $start_date[1] ), $start_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s - %s %s', (int) $start_date[2], (int) $end_date[2], $wp_locale->get_month( $start_date[1] ) );
                        }
                    }
                } else {
                    if ( 'short' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[1], $start_date[2], $start_date[0], $end_date[1], $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[1], $start_date[2], $end_date[1], $end_date[2] );
                        }
                    } elseif ( 'medium' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s %s, %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), (int) $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), (int) $end_date[2] );
                        }
                    } elseif ( 'long' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s %s, %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], $wp_locale->get_month( $end_date[1] ), (int) $end_date[2], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], $wp_locale->get_month( $end_date[1] ), (int) $end_date[2] );
                        }
                    } elseif ( 'short-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[2], $start_date[1], $start_date[0], $end_date[2], $end_date[1], $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[2], $start_date[1], $end_date[2], $end_date[1] );
                        }
                    } elseif ( 'medium-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s %s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ) );
                        }
                    } elseif ( 'long-alt' === $date_format ) {
                        if ( 'Y' === $show_year ) {
                            $formatted_date .= sprintf( '%s %s - %s %s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ), (int) $end_date[2], $wp_locale->get_month( $end_date[1] ), $end_date[0] );
                        } else {
                            $formatted_date .= sprintf( '%s %s - %s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ), (int) $end_date[2], $wp_locale->get_month( $end_date[1] ) );
                        }
                    }
                }
            } else {
                if ( 'short' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[1], $start_date[2], $start_date[0], $end_date[1], $end_date[2], $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[1], $start_date[2], $end_date[1], $end_date[2] );
                    }
                } elseif ( 'medium' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s %s, %s - %s %s, %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], $start_date[0], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), (int) $end_date[2], $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s %s - %s %s', $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), (int) $end_date[2] );
                    }
                } elseif ( 'long' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s %s, %s - %s %s, %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], $start_date[0], $wp_locale->get_month( $end_date[1] ), (int) $end_date[2], $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s %s - %s %s', $wp_locale->get_month( $start_date[1] ), (int) $start_date[2], $wp_locale->get_month( $end_date[1] ), (int) $end_date[2] );
                    }
                } elseif ( 'short-alt' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s/%s/%s - %s/%s/%s', $start_date[2], $start_date[1], $start_date[0], $end_date[2], $end_date[1], $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s/%s - %s/%s', $start_date[2], $start_date[1], $end_date[2], $end_date[1] );
                    }
                } elseif ( 'medium-alt' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s %s %s - %s %s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), $start_date[0], (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ), $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s %s - %s %s', (int) $start_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $start_date[1] ) ), (int) $end_date[2], $wp_locale->get_month_abbrev( $wp_locale->get_month( $end_date[1] ) ) );
                    }
                } elseif ( 'long-alt' === $date_format ) {
                    if ( 'Y' === $show_year ) {
                        $formatted_date .= sprintf( '%s %s %s - %s %s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ), $start_date[0], (int) $end_date[2], $wp_locale->get_month( $end_date[1] ), $end_date[0] );
                    } else {
                        $formatted_date .= sprintf( '%s %s - %s %s', (int) $start_date[2], $wp_locale->get_month( $start_date[1] ), (int) $end_date[2], $wp_locale->get_month( $end_date[1] ) );
                    }
                }
            }
        }
    }
    
    return $formatted_date;
}

/**
 * Get formatted event time
 * @global array $wp_locale
 * @param type $start_time
 * @param type $end_time
 * @param type $time_format
 * @return type
 */
function wpcalendars_format_time( $start_time, $end_time, $time_format ) {
    global $wp_locale;
    
    $formatted_time = '';
    
    if ( '12-hour' === $time_format ) {
        $formatted_time .= sprintf( '%s - %s', date( 'h:i A', strtotime( sprintf( '%s %s', date( 'Y-m-d' ), $start_time ) ) ), date( 'h:i A', strtotime( sprintf( '%s %s', date( 'Y-m-d' ), $end_time ) ) ) );
    } else {
        $formatted_time .= sprintf( '%s - %s', date( 'H:i', strtotime( sprintf( '%s %s', date( 'Y-m-d' ), $start_time ) ) ), date( 'H:i', strtotime( sprintf( '%s %s', date( 'Y-m-d' ), $end_time ) ) ) );
    }
    
    return $formatted_time;
}

/**
 * Get default calendar settings
 * @return array
 */
function wpcalendars_get_default_calendar_settings() {
    if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'pro/config/calendar-settings.php' ) ) {
        $settings = require WPCALENDARS_PLUGIN_DIR . 'pro/config/calendar-settings.php';
    } else {
        $settings = require WPCALENDARS_PLUGIN_DIR . 'config/calendar-settings.php';
    }
    
    $settings = apply_filters( 'wpcalendars_default_calendar_settings', $settings );
    
    return $settings;
}

/**
 * Get date format options
 * @return array
 */
function wpcalendars_get_date_format_options() {
    $options = array(
        'short'      => __( 'Short (Example: 11/30)', 'wpcalendars' ),
        'medium'     => __( 'Medium (Example: Nov 30)', 'wpcalendars' ),
        'long'       => __( 'Long (Example: November 30)', 'wpcalendars' ),
        'short-alt'  => __( 'Short Alt (Example: 30/11)', 'wpcalendars' ),
        'medium-alt' => __( 'Medium Alt (Example: 30 Nov)', 'wpcalendars' ),
        'long-alt'   => __( 'Long Alt (Example: 30 November)', 'wpcalendars' ),
    );
    
    return $options;
}

/**
 * Get time format options
 * @return array
 */
function wpcalendars_get_time_format_options() {
    $options = array(
        '12-hour' => __( '12 Hour (Example: 11:25 PM)', 'wpcalendars' ),
        '24-hour' => __( '24 Hour (Example: 23:25)', 'wpcalendars' )
    );
    
    return $options;
}

/**
 * Calculate difference date
 * @param string $start_date
 * @param string $end_date
 * @return integer
 */
function wpcalendars_calculate_difference_date( $start_date, $end_date ) {
    $result = ( strtotime( $end_date ) - strtotime( $start_date ) ) / ( 60 * 60 * 24 );
    
    return $result;
}
