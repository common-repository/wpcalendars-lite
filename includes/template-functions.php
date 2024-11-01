<?php
/**
 * Get calendar output
 * @param type $calendar_id
 * @param type $settings
 * @return type
 */
function wpcalendars_get_calendar_output( $calendar_id, $settings = array() ) {
    $calendar = apply_filters( 'wpcalendars_get_calendar', wpcalendars_get_calendar( intval( $calendar_id ) ), intval( $calendar_id ) );
        
    ob_start();
    
    switch ( $calendar['type'] ) {
        case 'monthly':
            WPCalendars_Monthly_View_Calendar::output( $calendar['calendar_id'], $settings );
            break;
        case 'multiple-months':
            WPCalendars_Multiple_Months_View_Calendar::output( $calendar['calendar_id'], $settings );
            break;
        case 'weekly':
            WPCalendars_Weekly_View_Calendar::output( $calendar['calendar_id'], $settings );
            break;
        case 'daily':
            WPCalendars_Daily_View_Calendar::output( $calendar['calendar_id'], $settings );
            break;
        case 'list':
            WPCalendars_List_View_Calendar::output( $calendar['calendar_id'], $settings );
            break;
        default:
            do_action( 'wpcalendars_generate_calendar_' . $calendar['type'], $calendar['calendar_id'], $settings  );
            break;
    }
    
    $output = ob_get_clean();

    return sprintf( '<div id="wpcalendars-block-%s" class="wpcalendars-block">%s</div>', $calendar['calendar_id'], $output );
}

/**
 * Get the list of event calendars
 * @global array $wpdb object
 * @return array
 */
function wpcalendars_get_calendars() {
    global $wpdb;
    
    $calendar_types = wpcalendars_get_calendar_type_options();
    
    $formatted_calendar_types = array();
    
    foreach ( $calendar_types as $calendar_type_id => $calendar_type ) {
        $formatted_calendar_types[] = sprintf( '"%s"', $calendar_type_id );
    }
    
    $str_calendar_types = implode( ',', $formatted_calendar_types );
    
    $format = 'select p.ID as calendar_id, p.post_title as name, pm.meta_value as type from %s p ';
    $format .= 'join %s pm on p.ID = pm.post_id ';
    $format .= 'where p.post_type = "%s" and p.post_status = "%s" and pm.meta_key = "%s" and pm.meta_value in (%s) ';
    $format .= 'order by pm.meta_value ASC';
    
    $results = $wpdb->get_results( sprintf( $format, $wpdb->posts, $wpdb->postmeta, 'wpcalendars', 'publish', '_type', $str_calendar_types ) );
    
    $calendars = array();
    
    foreach ( $results as $result ) {
        $calendars[] = array( 
            'calendar_id' => $result->calendar_id,
            'name'        => $result->name,
            'type'        => $result->type
        );
    }
    
    return $calendars;
}

/**
 * Get single event calendar
 * @param integer $id
 * @return array Calendar or False if not found
 */
function wpcalendars_get_calendar( $id ) {
    $obj = get_post( intval( $id ) );
    
    if ( $obj ) {
        $calendar = array(
            'calendar_id' => $obj->ID,
            'name'        => $obj->post_title,
            'type'        => get_post_meta( $obj->ID, '_type', true ),
            'settings'    => get_post_meta( $obj->ID, '_settings', true ),
        );
        
        return $calendar;
    }
    
    return false;
}

/**
 * Get upcoming events
 * @global array $wpdb
 * @param type $args
 * @return type
 */
function wpcalendars_get_events( $args = array() ) {
    global $wpdb;

    $params = array();
    
    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $sql = "select distinct(a.id) detail_id, b.ID as post_id, b.post_status, b.post_parent, b.post_title as event_title, b.post_name as event_name, b.post_excerpt as event_excerpt, ";
    $sql .= "a.event_id, a.event_parent, a.event_type, a.start_date, a.end_date, a.start_time, a.end_time, a.all_day, ";
    $sql .= "a.category_id, a.hide_event_listings, a.disable_event_details, a.website, ";
    $sql .= "a.venue_id, a.organizer_id ";
    $sql .= "from $table_name a left join $wpdb->posts b on a.event_id = b.ID ";
    
    if ( !empty( $args['tags'] ) ) {
        $sql .= "left join $wpdb->term_relationships c on b.ID = c.object_id ";
    }
    
    $sql .= "where 1=1 ";
    $sql .= "and b.post_type = 'wpcalendars_event' ";
    $sql .= "and b.post_status in ('publish') ";
    
    $event_types = apply_filters( 'wpcalendars_event_types', array( 'no-repeat' ) );
    
    $placeholders = array_fill( 0, count( $event_types ), '%s' );
    $sql .= sprintf( 'and a.event_type in (%s) ', implode( ', ', $placeholders ) );

    foreach ( $event_types as $event_type ) {
        $params[] = $event_type;
    }
    
    if ( !empty( $args['event_type'] ) ) {
        $sql .= 'and a.event_type = %s ';
        $params[] = $args['event_type'];
    }
    
    if ( isset( $args['show_parent_only'] ) && 'Y' === $args['show_parent_only'] ) {
        $sql .= 'and a.event_parent = 0 ';
    }
    
    if ( !empty( $args['categories'] ) ) {
        $cats = explode( ',', $args['categories'] );
        
        $placeholders = array_fill( 0, count( $cats ), '%d' );
        $sql .= sprintf( 'and a.category_id in (%s) ', implode( ', ', $placeholders ) );
        
        foreach ( $cats as $cat ) {
            $params[] = $cat;
        }
    }
    
    if ( !empty( $args['tags'] ) ) {
        $tags = explode( ',', $args['tags'] );
        $placeholders = array_fill( 0, count( $tags ), '%d' );
        $sql .= sprintf( 'and c.term_taxonomy_id in (%s) ', implode( ', ', $placeholders ) );
        
        foreach ( $tags as $tag ) {
            $params[] = $tag;
        }
    }
    
    if ( !empty( $args['venues'] ) ) {
        $venues = explode( ',', $args['venues'] );

        $placeholders = array_fill( 0, count( $venues ), '%d' );
        $sql .= sprintf( 'and a.venue_id in (%s) ', implode( ', ', $placeholders ) );
        
        foreach ( $venues as $venue ) {
            $params[] = $venue;
        }
    }

    if ( !empty( $args['organizers'] ) ) {
        $organizers = explode( ',', $args['organizers'] );

        $placeholders = array_fill( 0, count( $organizers ), '%d' );
        $sql .= sprintf( 'and a.organizer_id in (%s) ', implode( ', ', $placeholders ) );
        
        foreach ( $organizers as $organizer ) {
            $params[] = $organizer;
        }
    }
    
    if ( !empty( $args['exclude_events'] ) ) {
        $exclude_events = explode( ',', $args['exclude_events'] );
        $placeholders = array_fill( 0, count( $exclude_events ), '%d' );
        $sql .= sprintf( 'and b.ID not in (%s) ', implode( ', ', $placeholders ) );
        
        foreach ( $exclude_events as $exclude_event ) {
            $params[] = $exclude_event;
        }
    }
    
    if ( isset( $args['show_hidden_events'] ) && 'Y' === $args['show_hidden_events'] ) {
        $sql .= "and a.hide_event_listings in ('Y', 'N') ";
    } else {
        $sql .= "and a.hide_event_listings in ('N') ";
    }
    
    if ( isset( $args['start_date'] ) ) {
        if ( isset( $args['end_date'] ) ) {
            $sql .= "and ((a.start_date >= %s and a.end_date <= %s) or ";
            $sql .= "(a.start_date >= %s and a.start_date <= %s and a.end_date >= %s) || ";
            $sql .= "(a.start_date <= %s and a.end_date >= %s and a.end_date <= %s) || ";
            $sql .= "(a.start_date <= %s and a.end_date >= %s)) ";

            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
            $params[] = $args['end_date'];
            $params[] = $args['start_date'];
            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
            $params[] = $args['start_date'];
            $params[] = $args['end_date'];
        } else {
            $sql .= "and (a.start_date >= %s or (a.start_date < %s and a.end_date >= %s))";
            $params[] = $args['start_date'];
            $params[] = $args['start_date'];
            $params[] = $args['start_date'];
        }
    }
        
    $sql .= "order by a.start_date asc, a.start_time asc ";
    
    if ( isset( $args['posts_per_page'] ) ) {
        $sql .= "limit 0, %d ";
        $params[] = $args['posts_per_page'];
    }
    
    $results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
    
    return $results;
}

/**
 * Get single event
 * @global array $wpdb object
 * @param integer $id
 * @return array
 */
function wpcalendars_get_event( $id, $detail_id = '' ) {
    global $wpdb, $post;

    if ( is_singular( 'wpcalendars_event' ) && $post && $post->ID === $id ) {
        $results = array();
        
        foreach ( $post as $key => $value ) {
            $results[$key] = $value;
        }
        
        return $results;
    }
    
    $id = intval( $id );

    $table_name = $wpdb->prefix . 'wpcalendars_events';

    $sql = "select b.ID as post_id, b.post_status, b.post_parent, b.post_title as event_title, b.post_name as event_name, b.post_excerpt as event_excerpt, b.post_content as event_content, ";
    $sql .= "a.event_id, a.event_type, a.start_date, a.end_date, a.start_time, a.end_time, a.all_day, ";
    $sql .= "a.category_id, a.hide_event_listings, a.disable_event_details, a.website, ";
    $sql .= "a.venue_id, a.organizer_id ";
    $sql .= "from $table_name a left join $wpdb->posts b on a.event_id = b.ID ";
    $sql .= "where b.ID = $id ";
    
    if ( '' !== $detail_id ) {
        $detail_id = intval( $detail_id );
        $sql .= " and a.id = $detail_id ";
    }

    $results = $wpdb->get_row( $sql, ARRAY_A );

    return $results;
}

/**
 * Get event tags
 * @param boolean $hide_empty
 * @return array
 */
function wpcalendars_get_tags( $hide_empty = true ) {
    $args = array(
        'taxonomy'   => 'wpcalendars_event_tag',
        'hide_empty' => $hide_empty
    );
    
    $obj_terms = get_terms( $args );
    
    $terms = array();
    
    foreach ( $obj_terms as $obj ) {
        $terms[$obj->term_id] = $obj->name;
    }
    
    return $terms;
}

/**
 * Get event categories
 * @global array $wpdb
 * @return array
 */
function wpcalendars_get_event_categories() {
    global $wpdb;
    
    $args = "SELECT p.ID AS category_id, p.post_title AS name, pm.meta_value AS bgcolor ";
    $args .= "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id ";
    $args .= "WHERE p.post_type = 'wpcalendars_evcat' AND p.post_status = 'publish' AND pm.meta_key = '_bgcolor' ";
    $args .= "ORDER BY p.post_title ASC";
    
    $results = $wpdb->get_results( $args, ARRAY_A );

    return $results;
}

/**
 * Get event category
 * @param integer $id
 * @return boolean
 */
function wpcalendars_get_event_category( $id ) {
    $obj = get_post( $id );
    
    if ( $obj ) {
        $category = array(
            'category_id' => $obj->ID,
            'name'        => $obj->post_title,
            'bgcolor'     => get_post_meta( $obj->ID, '_bgcolor', true ),
        );
        
        return $category;
    }
    
    return false;
}

/**
 * Get event date
 * @param integer $event_id
 * @param string $date_format
 * @param string $time_format
 * @return string
 */
function wpcalendars_get_event_date( $event_id, $date_format = 'long', $time_format = '12-hour' ) {
    $event = wpcalendars_get_event( $event_id );
    
    $start_date = $event['start_date'];
    $start_time = $event['start_time'];
    $end_date   = $event['end_date'];
    $end_time   = $event['end_time'];
    $all_day    = $event['all_day'];
    
    if ( isset ( $_GET['date'] ) ) {
        $diff = wpcalendars_calculate_difference_date( $start_date, $end_date );
        $start_date = date( 'Y-m-d', intval( $_GET['date'] ) );
        $end_date = date( 'Y-m-d', strtotime( sprintf( $start_date . ' +%s days', $diff ) ) );
    }
    
    $event_date = wpcalendars_format_date( $start_date, $end_date, $date_format, 'Y' );
    
    if ( 'N' === $all_day ) {
        $event_date .= ' @ ' . wpcalendars_format_time( $start_time, $end_time, $time_format );
    }
    
    return apply_filters( 'wpcalendars_event_date', $event_date, $event_id );
}

/**
 * Get event time
 * @param integer $event_id
 * @param string $format
 * @return string
 */
function wpcalendars_get_event_time( $event_id, $format = 'medium' ) {
    $event = wpcalendars_get_event( $event_id );
    
    $start_time = $event['start_time'];
    $end_time = $event['end_time'];
    
    $event_time = sprintf( '%s - %s', $start_time, $end_time );
    
    return apply_filters( 'wpcalendars_event_time', $event_time, $event_id );
}

/**
 * Get event tags
 * @param integer $event_id
 * @return array
 */
function wpcalendars_get_event_tags( $event_id ) {
    $terms = get_the_terms( $event_id, 'wpcalendars_event_tag' );
    
    $termlist = array();
    
    foreach ( $terms as $term ) {
        $termlist[] = sprintf( '<a href="%s">%s</a>', get_term_link( $term->term_id ), $term->name );
    }
    
    return implode( ', ', $termlist );
}

/**
 * Get event website
 * @param integer $event_id
 * @return string
 */
function wpcalendars_get_event_website( $event_id ) {
    $event = wpcalendars_get_event( $event_id );
    $website = $event['website'];
    
    return $website;
}

/**
 * Get event single color
 * @return string
 */
function wpcalendars_get_event_single_color() {
    $event_categories = wpcalendars_get_event_categories();
    
    $css = array();
    
    foreach ( $event_categories as $category ) {
        $css[] = sprintf( '.wpcalendars-event-category-%s,.wpcalendars-block .wpcalendars-event-category-%s {background:%s;color:#fff;}' . "\n", $category['category_id'], $category['category_id'], $category['bgcolor'] );
    }
    
    return implode( '', $css );
}

/**
 * Get event multi colors
 * @param type $categories
 * @return type
 */
function wpcalendars_get_event_multi_colors( $categories = array() ) {
    if ( $categories === array() ) {
        return;
    }
    
    $tmp_all_categories = wpcalendars_get_event_categories();
    
    $all_categories = array();
    
    foreach ( $tmp_all_categories as $category ) {
        $id = $category['category_id'];
        $all_categories[$id] = $category;
    }
    
    $num_categories = count( $categories );
    
    $css = '';
        
    // Two colors
    if ( $num_categories === 2 ) {
        if ( $categories[0] === $categories[1] ) {}
        else {
            $background  = 'data:image/svg+xml;base64,' . base64_encode( sprintf( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" height="100" width="100"><polygon points="0,100 0,0 100,0" style="fill:%s;stroke:none;" /><polygon points="0,100 100,100 100,0" style="fill:%s;stroke:none;" /></svg>', $all_categories[$categories[0]]['bgcolor'], $all_categories[$categories[1]]['bgcolor'] ) );
            $css = sprintf( "background:url('%s') center no-repeat;background-size:%s;-ms-background-size:%s;-o-background-size:%s;-moz-background-size:%s;-webkit-background-size:%s;color:#fff;", $background, '100% 100%', '100% 100%', '100% 100%', '100% 100%', '100% 100%' );
        }
    }

    // Three Colors
    if ( $num_categories === 3 ) {
        if ( $categories[0] === $categories[1] && $categories[1] === $categories[2] ) {}
        else {
            $background  = 'data:image/svg+xml;base64,' . base64_encode( sprintf( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" height="100" width="100"><polygon points="0,100 0,0 100,0" style="fill:%s;stroke:none;" /><polygon points="0,100 100,100 50,50" style="fill:%s;stroke:none;" /><polygon points="100,0 100,100 50,50" style="fill:%s;stroke:none;" /></svg>', $all_categories[$categories[0]]['bgcolor'], $all_categories[$categories[1]]['bgcolor'], $all_categories[$categories[2]]['bgcolor'] ) );
            $css = sprintf( "background:url('%s') center no-repeat;background-size:%s;-ms-background-size:%s;-o-background-size:%s;-moz-background-size:%s;-webkit-background-size:%s;color:#fff;", $background, '100% 100%', '100% 100%', '100% 100%', '100% 100%', '100% 100%' );
        }
    }

    // Four Colors
    if ( $num_categories === 4 ) {
        if ( $categories[0] === $categories[1] && $categories[1] === $categories[2] && $categories[2] === $categories[3] ) {}
        else {
            $background  = 'data:image/svg+xml;base64,' . base64_encode( sprintf( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" height="100" width="100"><polygon points="0,0 0,100 50,50" style="fill:%s;stroke:none;" /><polygon points="0,0 100,0 50,50" style="fill:%s;stroke:none;" /><polygon points="100,0 100,100 50,50" style="fill:%s;stroke:none;" /><polygon points="0,100 100,100 50,50" style="fill:%s;stroke:none;" /></svg>', $all_categories[$categories[0]]['bgcolor'], $all_categories[$categories[1]]['bgcolor'], $all_categories[$categories[2]]['bgcolor'], $all_categories[$categories[3]]['bgcolor'] ) );
            $css = sprintf( "background:url('%s') center no-repeat;background-size:%s;-ms-background-size:%s;-o-background-size:%s;-moz-background-size:%s;-webkit-background-size:%s;color:#fff;", $background, '100% 100%', '100% 100%', '100% 100%', '100% 100%', '100% 100%' );
        }
    }

    // Five Colors
    if ( $num_categories === 5 ) {
        if ( $categories[0] === $categories[1] && $categories[1] === $categories[2] && $categories[2] === $categories[3] && $categories[3] === $categories[4] ) {}
        else {
            $background  = 'data:image/svg+xml;base64,' . base64_encode( sprintf( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" height="100" width="100"><polygon points="0,0 25,25 25,75 0,100" style="fill:%s;stroke:none;" /><polygon points="0,0 25,25 75,25 100,0" style="fill:%s;stroke:none;" /><polygon points="100,0 75,25 75,75 100,100" style="fill:%s;stroke:none;" /><polygon points="0,100 25,75 75,75 100,100" style="fill:%s;stroke:none;" /><polygon points="25,25 75,25 75,75 25,75" style="fill:%s;stroke:none;" /></svg>', $all_categories[$categories[0]]['bgcolor'], $all_categories[$categories[1]]['bgcolor'], $all_categories[$categories[2]]['bgcolor'], $all_categories[$categories[3]]['bgcolor'], $all_categories[$categories[4]]['bgcolor'] ) );
            $css = sprintf( "background:url('%s') center no-repeat;background-size:%s;-ms-background-size:%s;-o-background-size:%s;-moz-background-size:%s;-webkit-background-size:%s;color:#fff;", $background, '100% 100%', '100% 100%', '100% 100%', '100% 100%', '100% 100%' );
        }
    }
    
    return $css;
}

/**
 * Get venues
 * @return array
 */
function wpcalendars_get_venues() {
    $objs = get_posts( array(
        'post_type'      => 'wpcalendars_venue',
        'posts_per_page' => -1,
        'orderby'        => 'post_title',
        'order'          => 'ASC'
    ) );
    
    $venues = array();
    
    foreach( $objs as $obj ) {
        $venues[] = array(
            'venue_id' => $obj->ID,
            'name'     => $obj->post_title,
        );
    }
    
    return $venues;
}

/**
 * Get single venue
 * @param integer $id
 * @return array
 */
function wpcalendars_get_venue( $id ) {
    $obj = get_post( $id );
    
    if ( $obj ) {
        $venue = array(
            'venue_id'    => $obj->ID,
            'name'        => $obj->post_title,
            'detail'      => $obj->post_content,
            'address'     => get_post_meta( $obj->ID, '_address', true ),
            'city'        => get_post_meta( $obj->ID, '_city', true ),
            'country'     => get_post_meta( $obj->ID, '_country', true ),
            'state'       => get_post_meta( $obj->ID, '_state', true ),
            'postal_code' => get_post_meta( $obj->ID, '_postal_code', true ),
            'latitude'    => get_post_meta( $obj->ID, '_latitude', true ),
            'longitude'   => get_post_meta( $obj->ID, '_longitude', true ),
            'place_id'    => get_post_meta( $obj->ID, '_place_id', true ),
            'email'       => get_post_meta( $obj->ID, '_email', true ),
            'phone'       => get_post_meta( $obj->ID, '_phone', true ),
            'website'     => get_post_meta( $obj->ID, '_website', true ),
        );
        
        return $venue;
    }
    
    return false;
}

/**
 * Get organizer
 * @return array
 */
function wpcalendars_get_organizers() {
    $objs = get_posts( array(
        'post_type'      => 'wpcalendars_organizr',
        'posts_per_page' => -1,
        'orderby'        => 'post_title',
        'order'          => 'ASC'
    ) );
    
    $organizers = array();
    
    foreach( $objs as $obj ) {
        $organizers[] = array(
            'organizer_id' => $obj->ID,
            'name'         => $obj->post_title
        );
    }
    
    return $organizers;
}

/**
 * Get single organizer
 * @param integer $id
 * @return array
 */
function wpcalendars_get_organizer( $id ) {
    $obj = get_post( $id );
    
    if ( $obj ) {
        $organizer = array(
            'organizer_id' => $obj->ID,
            'name'         => $obj->post_title,
            'detail'       => $obj->post_content,
            'email'        => get_post_meta( $obj->ID, '_email', true ),
            'phone'        => get_post_meta( $obj->ID, '_phone', true ),
            'website'      => get_post_meta( $obj->ID, '_website', true ),
        );
        
        return $organizer;
    }
    
    return false;
}

/**
 * Display prev/next navigation
 * @param type $args
 */
function wpcalendars_show_prevnext_navigation( $args = array() ) {
    echo '<div class="wpcalendars-prevnext-nav left">';
    printf( '<button class="wpcalendars-button wpcalendars-prevnext-nav-button" title="%s" data-calendarid="%s" data-startdate="%s" data-enddate="%s"><span class="wpcalendars-button-text">%s</span></button>', esc_html__( 'Previous Calendar', 'wpcalendars' ), $args['calendar_id'], $args['prev_start_date'], $args['prev_end_date'], esc_html__( 'Previous Calendar', 'wpcalendars' ) );
    echo '</div>';

    echo '<div class="wpcalendars-prevnext-nav right">';
    printf( '<button class="wpcalendars-button wpcalendars-prevnext-nav-button" title="%s" data-calendarid="%s" data-startdate="%s" data-enddate="%s"><span class="wpcalendars-button-text">%s</span></button>', esc_html__( 'Next Calendar', 'wpcalendars' ), $args['calendar_id'], $args['next_start_date'], $args['next_end_date'], esc_html__( 'Next Calendar', 'wpcalendars' ) );
    echo '</div>';
}

/**
 * Get hidden events
 * @global array $wpdb
 * @return type
 */
function wpcalendars_get_hidden_events() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $event_ids = array();
    
    $sql = "select a.ID as post_id from {$wpdb->posts} a left join {$table_name} b on a.ID = b.event_id ";
    $sql .= "where a.post_type = 'wpcalendars_event' and a.post_status = 'publish' and b.disable_event_details = 'Y'";
    $results = $wpdb->get_results( $sql );
    
    if ( ! empty( $results ) ) {
        foreach ( $results as $result ) {
            $event_ids[] = $result->post_id;
        }
    }
    
    return $event_ids;
}

/**
 * Show category listings
 * @param type $settings
 * @param type $events
 */
function wpcalendars_show_category_listings( $settings, $events ) {
    if ( 'Y' === $settings['show_category_listings'] ) {
        $event_categories = array();

        foreach ( $events as $event ) {
            $event_categories[] = $event['category_id'];
        }

        $event_categories = array_unique( $event_categories );

        $categories = wpcalendars_get_event_categories();

        printf( '<h3 class="wpcalendars-event-category-heading">%s</h3>', $settings['category_listings_title'] );

        echo '<div class="wpcalendars-event-category-listings">';

        foreach ( $categories as $category ) {
            if ( in_array( $category['category_id'], $event_categories ) ) {
                echo '<div class="wpcalendars-event-category-listing">';
                printf( '<span class="wpcalendars-event-category-listing-color" style="background:%s;"></span>', $category['bgcolor'] );
                printf( '<span class="wpcalendars-event-category-listing-name">%s</span>', $category['name'] );
                echo '</div>';
            }
        }

        echo '</div>';
    }
}

/**
 * Show event listings
 * @param type $settings
 * @param type $events
 */
function wpcalendars_show_event_listings( $settings, $events ) {
    if ( 'Y' === $settings['show_event_listings'] ) {
        if ( 'DESC' === $settings['sort_order'] ) {
            $events = array_reverse( $events );
        }

        echo '<div class="wpcalendars-event-listings">';

        printf( '<h3 class="wpcalendars-event-listings-heading">%s</h3>', $settings['event_listings_title'] );

        if ( empty( $events ) ) {
            printf( '<div class="wpcalendars-no-events">%s</div>', esc_html__( 'No Events', 'wpcalendars' ) );
        } else {
            $class = 'simple' === $settings['event_listings_layout'] ? 'wpcalendars-event-item-simple' : 'wpcalendars-event-item-advanced';
            
            foreach ( $events as $event ) {
                printf( '<div class="wpcalendars-event-item %s">', $class );

                $date_time = wpcalendars_format_date( $event['start_date'], $event['end_date'], $settings['date_format'], $settings['show_year'] );
                
                if ( 'N' === $event['all_day'] ) {
                    $date_time .= ' @ ' . wpcalendars_format_time( $event['start_time'], $event['end_time'], $settings['time_format'] );
                }
                
                $title = sprintf( '<a href="%s">%s</a>', wpcalendars_get_permalink( $event ), $event['event_title'] );
                
                if ( 'Y' === $event['disable_event_details'] ) {
                    $title = $event['event_title'];
                }
                
                printf( '<div class="wpcalendars-event-date">%s</div>', $date_time );
                printf( '<div class="wpcalendars-event-title">%s</div>', $title );
                
                if ( 'advanced' === $settings['event_listings_layout'] ) {
                    echo '<div class="wpcalendars-event-column-container">';

                    if ( has_post_thumbnail( $event['event_id'] ) ) {
                        echo '<div class="wpcalendars-event-first-column">';
                        echo '<div class="wpcalendars-event-image">';
                        echo get_the_post_thumbnail( $event['event_id'], 'thumbnail' );
                        echo '</div>';
                        echo '</div>'; // Column 1
                    }

                    echo '<div class="wpcalendars-event-second-column">';
                    printf( '<div class="wpcalendars-event-excerpt">%s</div>', $event['event_excerpt'] );
                    if ( 'N' === $event['disable_event_details'] ) {
                        printf( '<div class="wpcalendars-event-more"><a href="%s">%s</a></div>', wpcalendars_get_permalink( $event ), __( 'Read More', 'wpcalendars' ) );
                    }
                    echo '</div>'; // Column 2

                    echo '</div>'; // Columns Container
                }

                do_action( 'wpcalendars_event_listings_item', $event, $settings );

                echo '</div>';
            }
        }

        echo '</div>';
    }
}

/**
 * Get event permalink
 * @param type $event
 * @return type
 */
function wpcalendars_get_permalink( $event ) {
    if ( 'repeat' === $event['event_type'] ) {
        $permalinks = (array) get_option( 'wpcalendars_permalinks', array() );
        $event_base = isset( $permalinks['event_base'] ) && '' !== $permalinks['event_base'] ? $permalinks['event_base'] : 'event';
        
        if ( $event['event_parent'] > 0 ) {
            $event_parent = wpcalendars_get_event( $event['event_parent'] );
        } else {
            $event_parent = $event;
        }
    
        if ( get_option('permalink_structure') ) {
            $permalink = home_url( sprintf( '%s/%s/%s/', $event_base, $event_parent['event_name'], $event['start_date'] ) );
        } else {
            $permalink = home_url( add_query_arg( array( 'event' => $event_parent['event_name'], 'event_date' => $event['start_date'] ), '' ) );
        }
    } else {
        $permalink = get_permalink( $event['event_id'] );
    }
    
    return $permalink;
}

/**
 * Show calendar heading
 * @global type $wp_locale
 * @param type $calendar_id
 * @param type $settings
 */
function wpcalendars_show_heading( $calendar_id, $settings ) {
    global $wp_locale;
    
    $start_date = explode( '-', $settings['start_date'] );
    $end_date   = explode( '-', $settings['end_date'] );

    $start_month_name = $wp_locale->get_month( $start_date[1] );

    if ( 'three-letter' === $settings['month_format'] ) {
        $start_month_name = $wp_locale->get_month_abbrev( $start_month_name );
    }

    $end_month_name = $wp_locale->get_month( $end_date[1] );

    if ( 'three-letter' === $settings['month_format'] ) {
        $end_month_name = $wp_locale->get_month_abbrev( $end_month_name );
    }
            
    if ( isset( $settings['show_heading'] ) && 'Y' === $settings['show_heading'] ) {
        echo '<div class="wpcalendars-heading-navigation">';
        
        printf( '<span>%s</span> ', $settings['heading_text_before'] );
        
        if ( 'startdate' === $settings['heading_format'] ) {
            printf( '<span>%s %s, %s</span>', $start_month_name, $start_date[2], $start_date[0] );
        } elseif ( 'startmonth' === $settings['heading_format'] ) {
            printf( '<span>%s %s</span>', $start_month_name, $start_date[0] );
        } elseif ( 'startyear' === $settings['heading_format'] ) {
            printf( '<span>%s</span>', $start_date[0] );
        } elseif ( 'startdate-enddate' === $settings['heading_format'] ) {
            if ( $start_date[0] === $end_date[0] ) {
                if ( $start_date[1] === $end_date[1] ) {
                    printf( '<span>%s %s %s %s, %s</span>', $start_month_name, $start_date[2], $settings['heading_separator'], $end_date[2], $start_date[0] );
                } else {
                    printf( '<span>%s %s %s %s %s, %s</span>', $start_month_name, $start_date[2], $settings['heading_separator'], $end_month_name, $end_date[2], $start_date[0] );
                }
            } else {
                printf( '<span>%s %s, %s %s %s %s, %s</span>', $start_month_name, $start_date[2], $start_date[0], $settings['heading_separator'], $end_month_name, $end_date[2], $end_date[0] );
            }
        } elseif ( 'startmonth-endmonth' === $settings['heading_format'] ) {
            if ( $start_date[0] === $end_date[0] ) {
                printf( '<span>%s %s %s %s</span>', $start_month_name, $settings['heading_separator'], $end_month_name, $end_date[0] );
            } else {
                printf( '<span>%s %s %s %s %s</span>', $start_month_name, $start_date[0], $settings['heading_separator'], $end_month_name, $end_date[0] );
            }
        } elseif ( 'startyear-endyear' === $settings['heading_format'] ) {
            if ( $start_date[0] === $end_date[0] ) {
                printf( '<span>%s</span>', $start_date[0] );
            } else {
                printf( '<span>%s %s %s</span>', $start_date[0], $settings['heading_separator'], $end_date[0] );
            }
        }
        
        echo '</div>';
    }
}
