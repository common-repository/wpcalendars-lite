<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Ajax_Action {

    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'wp_ajax_wpcalendars_save_calendar',           array( $this, 'save_calendar' ) );
        add_action( 'wp_ajax_wpcalendars_add_category',            array( $this, 'add_category' ) );
        add_action( 'wp_ajax_wpcalendars_update_preview',          array( $this, 'update_preview' ) );
        add_action( 'wp_ajax_wpcalendars_apply_prevnext',          array( $this, 'show_prevnext' ) );
        add_action( 'wp_ajax_nopriv_wpcalendars_apply_prevnext',   array( $this, 'show_prevnext' ) );
        add_action( 'wp_ajax_wpcalendars_add_venue',               array( $this, 'add_venue' ) );
        add_action( 'wp_ajax_wpcalendars_add_organizer',           array( $this, 'add_organizer' ) );
        add_action( 'wp_ajax_wpcalendars_get_states',              array( $this, 'get_states' ) );
        add_action( 'wp_ajax_wpcalendars_get_tooltip',             array( $this, 'get_tooltip' ) );
        add_action( 'wp_ajax_nopriv_wpcalendars_get_tooltip',      array( $this, 'get_tooltip' ) );
        add_action( 'wp_ajax_wpcalendars_get_popup',               array( $this, 'get_popup' ) );
        add_action( 'wp_ajax_nopriv_wpcalendars_get_popup',        array( $this, 'get_popup' ) );
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
     * Save the calendar
     */
    public function save_calendar() {
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $form_post = json_decode( stripslashes( $_POST['data'] ) );
        
        $data   = wpcalendars_get_form_data( $form_post );
        $result = wpcalendars_save_calendar( $data );
        
        wpcalendars_delete_rev_calendar( intval( $data['calendar_id'] ) );

        do_action( 'wpcalendars_builder_save_calendar', $data );
        
        if ( !$result ) {
            die( esc_html__( 'An error occurred and the calendar could not be saved', 'wpcalendars' ) );
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * Add event category
     */
    public function add_category() {
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $posted_data = $_POST['data'];
        
        $args = array(
            'name'    => sanitize_text_field( $posted_data['name'] ),
            'bgcolor' => sanitize_text_field( $posted_data['bgcolor'] ),
        );
        
        $result = wpcalendars_add_new_event_category( $args );

        if ( !$result ) {
            die( esc_html__( 'An error occurred and the category could not be saved', 'wpcalendars' ) );
        } else {
            wp_send_json_success( array( 'category_id' => $result ) );
        }
    }
    
    /**
     * Show prev/next calendar content
     */
    public function show_prevnext() {
        check_ajax_referer( 'wpcalendars', 'nonce' );
        
        $calendar_id = sanitize_text_field( $_POST['calendar_id'] );
        
        $data = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( $calendar_id ), $calendar_id );
        
        $data['settings']['start_date']  = sanitize_text_field( $_POST['start_date'] );
        $data['settings']['end_date']    = sanitize_text_field( $_POST['end_date'] );
        $data['settings']['_categories'] = sanitize_text_field( $_POST['categories'] );
        $data['settings']['_tags']       = sanitize_text_field( $_POST['tags'] );
        $data['settings']['_venues']     = sanitize_text_field( $_POST['venues'] );
        $data['settings']['_organizers'] = sanitize_text_field( $_POST['organizers'] );
        
        $calendar = false;
        
        if ( '' !== $data['type'] ) {
            $calendar = wpcalendars_get_calendar_output( $data['calendar_id'], $data['settings'] );
        }

        if ( !$calendar ) {
            die( esc_html__( 'An error occurred and the calendar could not be rendered', 'wpcalendars' ) );
        } else {
            wp_send_json_success( array( 'calendar' => $calendar ) );
        }
    }
    
    /**
     * Update preview calendar builder
     */
    public function update_preview() {
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $form_post = json_decode( stripslashes( $_POST['data'] ) );
        
        $data = wpcalendars_get_form_data( $form_post );
        
        // Update revision calendar
        
        $rev_calendar_id = wpcalendars_save_rev_calendar( $data );
        
        // Show preview calendar
        
        $calendar = false;
        
        if ( '' !== $data['type'] ) {
            $calendar = wpcalendars_get_calendar_output( $rev_calendar_id );
        }
        
        if ( !$calendar ) {
            die( esc_html__( 'An error occurred and the calendar could not be rendered', 'wpcalendars' ) );
        } else {
            wp_send_json_success( array( 'calendar' => $calendar, 'rev_calendar_id' => $rev_calendar_id ) );
        }
    }
    
    /**
     * Add event organizer
     */
    public function add_organizer() {
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $organizer_name = sanitize_text_field( $_POST['data'] );
        
        $result = wpcalendars_add_organizer( $organizer_name );

        if ( !$result ) {
            die( esc_html__( 'An error occurred and the organizer could not be saved', 'wpcalendars' ) );
        } else {
            wp_send_json_success( array( 'organizer_id' => $result ) );
        }
    }
    
    /**
     * Get venue states
     * @global type $states
     */
    public function get_states() {
        global $states;
        
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $data = $_POST['data'];
        $country = sanitize_text_field( $data['country'] );
        
        if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php' ) ) {
            include WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php';
            $output = '<select id="state" name="state">';
            $output .= '<option value="">&mdash; ' . __( 'Select State / Province', 'wpcalendars' ) . ' &mdash;</option>';
            foreach ( $states[$country] as $code => $name ) {
                $output .= '<option value="' . $code . '">' . $name . '</option>';
            }
            $output .= '</select>';
        } else {
            $output = '<input id="state" type="text" name="state" value="" class="regular-text">';
        }
        
        wp_send_json_success( array( 'states' => $output ) );
    }
    
    /**
     * Get event venue
     */
    public function add_venue() {
        check_ajax_referer( 'wpcalendars_admin', 'nonce' );
        
        $venue_name = sanitize_text_field( $_POST['data'] );
        
        $result = wpcalendars_add_venue( $venue_name );

        if ( !$result ) {
            die( esc_html__( 'An error occurred and the venue could not be saved', 'wpcalendars' ) );
        } else {
            wp_send_json_success( array( 'venue_id' => $result ) );
        }
    }
    
    /**
     * Get event popup content
     * @global type $states
     */
    public function get_popup() {
        check_ajax_referer( 'wpcalendars', 'nonce' );
        
        $page_id     = sanitize_text_field( $_POST['page_id'] );
        $calendar_id = sanitize_text_field( $_POST['calendar'] );
        $events      = json_decode( base64_decode( sanitize_text_field( $_POST['events'] ) ), true );
        
        $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( intval( $calendar_id ) ), intval( $calendar_id ) );
        
        $settings = $calendar['settings'];
        
        $html = '';
        
        foreach ( $events as $data ) {
            $event = wpcalendars_get_event( $data['event_id'], $data['detail_id'] );
            
            $html .= '<div class="wpcalendars-popup-item">';
    
            $title = sprintf( '<a href="%s">%s</a>', wpcalendars_get_permalink( $event ), $event['event_title'] );
                
            if ( 'Y' === $event['disable_event_details'] ) {
                $title = $event['event_title'];
            }
                
            $html .= sprintf( '<div class="wpcalendars-popup-title">%s</div>', $title );
            
            $html .= '<div class="wpcalendars-popup-content">';
            
            $date_time = wpcalendars_format_date( $event['start_date'], $event['end_date'], $settings['date_format'], $settings['show_year'] );
            
            if ( 'N' === $event['all_day'] ) {
                $date_time .= ' @ ' . wpcalendars_format_time( $event['start_time'], $event['end_time'], $settings['time_format'] );
            }
            
            $html .= '<div class="wpcalendars-popup-date-section">';
            $html .= sprintf( '<span class="wpcalendars-popup-date-heading">%s</span>', esc_html__( 'Date / Time:', 'wpcalendars' ) );
            $html .= sprintf( '<span class="wpcalendars-popup-date-range">%s</span>', apply_filters( 'wpcalendars_popup_date_time', $date_time, $event ) );
            $html .= '</div>';
            
            if ( has_post_thumbnail( $event['event_id'] ) ) {
                $html .= '<div class="wpcalendars-popup-image-section">';
                $html .= get_the_post_thumbnail( $event['event_id'], 'wpcalendars-featured-image' );
                $html .= '</div>';
            }

            $html .= '<div class="wpcalendars-popup-detail-section">';

            $html .= sprintf( '<div class="wpcalendars-popup-detail-heading">%s</div>', esc_html__( 'Event Details', 'wpcalendars' ) );

            $html .= '<div class="wpcalendars-popup-detail-content">';
            $html .= sprintf( '<div id="wpcalendars-popup-detail-content-short-%s" class="wpcalendars-detail-content-wrapper">%s</div>', $event['event_id'], wp_trim_words( $event['event_content'], 55, '...<span class="wpcalendars-more-less-detail"><a href="#wpcalendars-popup-detail-content-full-' . $event['event_id'] . '">' . esc_html__( 'More...', 'wpcalendars' ) . '</a></span>' ) );
            $html .= sprintf( '<div id="wpcalendars-popup-detail-content-full-%s" class="wpcalendars-detail-content-wrapper" style="display:none">%s<span class="wpcalendars-more-less-detail"><a href="#wpcalendars-popup-detail-content-short-' . $event['event_id'] . '">%s</a></span></div>', $event['event_id'], wpautop( $event['event_content'] ), esc_html__( 'Less', 'wpcalendars' ) );

            $html .= '</div>'; // content

            $html .= '</div>'; // section

            if ( ! empty( $event['venue_id'] ) ) {
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

                $html .= '<div class="wpcalendars-popup-venue-section">';

                $html .= sprintf( '<div class="wpcalendars-popup-venue-heading">%s</div>', esc_html__( 'Venue', 'wpcalendars' ) );

                $html .= '<div class="wpcalendars-popup-venue-content">';

                $html .= '<div class="wpcalendars-popup-venue-name wpcalendars-detail-tooltip">';
                $html .= $venue['name'];
                if ( '' !== $venue['detail'] ) {
                    $html .= sprintf( '<span><a class="wpcalendars-detail-tooltip-link" href="#">+ %s</a></span>', esc_html__( 'Show Details', 'wpcalendars' ) );
                    $html .= sprintf( '<div class="wpcalendars-detail-tooltip-content" style="display:none"><div class="wpcalendars-detail-tooltip-content-inner">%s</div></div>', wpautop( $venue['detail'] ) );
                }
                $html .= '</div>';

                $html .= '<div class="wpcalendars-popup-venue-location">';
                $html .= implode( ', ', $location );
                if ( '' !== $venue['latitude'] && '' !== $venue['longitude'] ) {
                    $map_provider = wpcalendars_settings_value( 'general', 'map_provider' );
                    if ( 'google' === $map_provider ) {
                        $html .= '<span><a href="//www.google.com/maps/dir/?api=1&destination=' . urlencode( $venue['name'] . ',' . implode( ', ', $location ) ) . '&destination_place_id=' . $venue['place_id'] . '" target="_blank" rel="nofollow">+ ' . esc_html__( 'Google Maps Direction', 'wpcalendars' ) . '</a></span>';
                    }
                }
                $html .= '</div>';

                $html .= '<div class="wpcalendars-popup-venue-metadata">';

                if ( '' !== $venue['phone'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-venue-phone"><div class="wpcalendars-popup-venue-phone-icon"></div>%s</div>', $venue['phone'] );
                }

                if ( '' !== $venue['email'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-venue-email"><div class="wpcalendars-popup-venue-email-icon"></div><a href="mailto:%s" rel="nofollow">%s</a></div>', $venue['email'], $venue['email'] );
                }

                if ( '' !== $venue['website'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-venue-website"><div class="wpcalendars-popup-venue-website-icon"></div><a href="%s" target="_blank" rel="nofollow">%s</a></div>', $venue['website'], $venue['website'] );
                }

                $html .= '</div>'; // metadata

                if ( '' !== $venue['latitude'] && '' !== $venue['longitude'] ) {
                    $map_provider       = wpcalendars_settings_value( 'general', 'map_provider' );
                    $map_zoom           = wpcalendars_settings_value( 'general', 'map_zoom' );
                    $google_maps_apikey = wpcalendars_settings_value( 'api', 'google_maps_apikey' );

                    $html .= '<div class="wpcalendars-popup-venue-map">';

                    if ( 'google' === $map_provider ) {
                        $html .= apply_filters( 'wpcalendars_event_venue_google_maps', '<img src="//maps.googleapis.com/maps/api/staticmap?center=' . $venue['latitude'] . ',' . $venue['longitude'] . '&markers=color:red%7Clabel:S%7C' . $venue['latitude'] . ',' . $venue['longitude'] . '&zoom=' . $map_zoom . '&size=640x400&key=' . $google_maps_apikey . '" />', $venue );
                    } else {

                    }

                    $html .= '</div>'; // map
                }

                $html .= '</div>'; // content

                $html .= '</div>'; // section
            }
            
            if ( ! empty( $event['organizer_id'] ) ) {
                $organizer = wpcalendars_get_organizer( $event['organizer_id'] );

                $html .= '<div class="wpcalendars-popup-organizer-section">';

                $html .= sprintf( '<div class="wpcalendars-popup-organizer-heading">%s</div>', esc_html__( 'Organizer', 'wpcalendars' ) );

                $html .= '<div class="wpcalendars-popup-organizer-content">';

                $html .= '<div class="wpcalendars-popup-organizer-name wpcalendars-detail-tooltip">';
                $html .= $organizer['name'];
                if ( '' !== $organizer['detail'] ) {
                    $html .= sprintf( '<span><a class="wpcalendars-detail-tooltip-link" href="#">+ %s</a></span>', esc_html__( 'Show Details', 'wpcalendars' ) );
                    $html .= sprintf( '<div class="wpcalendars-detail-tooltip-content" style="display:none"><div class="wpcalendars-detail-tooltip-content-inner">%s</div></div>', wpautop( $organizer['detail'] ) );
                }
                $html .= '</div>';

                $html .= '<div class="wpcalendars-popup-organizer-metadata">';

                if ( '' !== $organizer['phone'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-organizer-phone"><div class="wpcalendars-popup-organizer-phone-icon"></div>%s</div>', $organizer['phone'] );
                }

                if ( '' !== $organizer['email'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-organizer-email"><div class="wpcalendars-popup-organizer-email-icon"></div><a href="mailto:%s" rel="nofollow">%s</a></div>', $organizer['email'], $organizer['email'] );
                }

                if ( '' !== $organizer['website'] ) {
                    $html .= sprintf( '<div class="wpcalendars-popup-organizer-website"><div class="wpcalendars-popup-organizer-website-icon"></div><a href="%s" target="_blank" rel="nofollow">%s</a></div>', $organizer['website'], $organizer['website'] );
                }

                $html .= '</div>'; // metadata

                $html .= '</div>'; // content

                $html .= '</div>'; // section
            }
            
            ob_start();
            do_action( 'wpcalendars_popup_content', $event, $settings );
            $html .= ob_get_clean();
            
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        wp_send_json_success( array( 'content' => $html ) );
    }
    
    /**
     * Get event tooltip content
     */
    public function get_tooltip() {
        check_ajax_referer( 'wpcalendars', 'nonce' );
        
        $page_id     = sanitize_text_field( $_POST['page_id'] );
        $calendar_id = sanitize_text_field( $_POST['calendar'] );
        $events      = json_decode( base64_decode( sanitize_text_field( $_POST['events'] ) ), true );
        
        $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( intval( $calendar_id ) ), intval( $calendar_id ) );
        
        $settings = $calendar['settings'];
        
        $class = '';
        $attr  = '';
        
        $num_events = count( $events );
        
        if ( $num_events > 1 ) {
            $class = ' wpcalendars-tooltip-accordion';
        }
        
        $html = sprintf( '<div class="wpcalendars-tooltip-container%s">', $class );
        
        foreach ( $events as $i => $data ) {
            $event = wpcalendars_get_event( $data['event_id'], $data['detail_id'] );
            
            $date_time = wpcalendars_format_date( $event['start_date'], $event['end_date'], $settings['date_format'], $settings['show_year'] );
            
            if ( 'N' === $event['all_day'] ) {
                $date_time .= ' @ ' . wpcalendars_format_time( $event['start_time'], $event['end_time'], $settings['time_format'] );
            }
            
            $title = sprintf( '<a href="%s">%s</a>', wpcalendars_get_permalink( $event ), $event['event_title'] );
                
            if ( 'Y' === $event['disable_event_details'] ) {
                $title = $event['event_title'];
            }
            
            if ( 'simple' === $settings['tooltip_layout'] ) {
                $html .= sprintf( '<div class="wpcalendars-tooltip-simple wpcalendars-tooltip-item wpcalendars-event-category-%s">', $event['category_id'] );
                $html .= '<div class="wpcalendars-tooltip-item-inner">';
                $html .= sprintf( '<div class="wpcalendars-tooltip-title">%s</div>', $title );
                $html .= '</div>';
                $html .= '</div>';
            } else {
                $html .= sprintf( '<div class="wpcalendars-tooltip-advanced wpcalendars-tooltip-item wpcalendars-event-category-%s">', $event['category_id'] );
                $html .= '<div class="wpcalendars-tooltip-item-inner">';
                
                if ( $num_events > 1 ) {
                    $html .= '<div class="wpcalendars-tooltip-item-nav">';
                    $html .= sprintf( '<button><span>%s</span></button>', __( 'Show Details', 'wpcalendars' ) );
                    $html .= '</div>';
                }
                
                $style = $i > 0 ? 'display: none;' : '';
                
                $html .= sprintf( '<div class="wpcalendars-tooltip-title">%s</div>', $title );
                $html .= sprintf( '<div class="wpcalendars-tooltip-details" style="%s">', $style );
                $html .= sprintf( '<div class="wpcalendars-tooltip-date">%s</div>', $date_time );
                
                $html .= '<div class="wpcalendars-tooltip-column-container">';
                
                if ( has_post_thumbnail( $event['event_id'] ) ) {
                    $html .= '<div class="wpcalendars-tooltip-first-column">';
                    $html .= '<div class="wpcalendars-tooltip-image">';
                    $html .= get_the_post_thumbnail( $event['event_id'], 'thumbnail' );
                    $html .= '</div>';
                    $html .= '</div>'; // Column 1
                }
                
                $html .= '<div class="wpcalendars-tooltip-second-column">';
                $html .= sprintf( '<div class="wpcalendars-tooltip-excerpt">%s</div>', $event['event_excerpt'] );
                $html .= '</div>'; // Column 2
                
                $html .= '</div>'; // Columns Container
                
                ob_start();
                do_action( 'wpcalendars_event_tooltip_content', $event, $settings );
                $html .= ob_get_clean();
                
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        wp_send_json_success( array( 'tooltip' => $html ) );
    }
    
}

WPCalendars_Ajax_Action::instance();