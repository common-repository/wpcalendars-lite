<?php
/**
 * Setup settings on fresh install
 */
function wpcalendars_setup_fresh_install() {
    $default_event_category = __( 'General Events', 'wpcalendars' );
    
    $category_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars_evcat',
        'post_title'  => $default_event_category,
        'post_status' => 'publish'
    ) );

    update_post_meta( $category_id, '_bgcolor', '#000000' );
    
    $default_settings = wpcalendars_get_default_settings();
    $wpcalendars_options = get_option( 'wpcalendars_options', $default_settings );

    $wpcalendars_options['general']['default_event_category'] = $category_id;

    update_option( 'wpcalendars_options', $wpcalendars_options );
    update_option( 'wpcalendars_version', WPCALENDARS_PLUGIN_VERSION );
}

/**
 * Add new calendar
 * @param array $args
 * @return integer
 */
function wpcalendars_add_new_calendar( $args ) {
    $calendar_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars',
        'post_title'  => $args['name'],
        'post_status' => 'publish'
    ) );
    
    update_post_meta( $calendar_id, '_type', $args['type'] );
    update_post_meta( $calendar_id, '_settings', $args['settings'] );
    
    return $calendar_id;
}

/**
 * Save revision calendar
 * @param type $args
 * @return type
 */
function wpcalendars_save_rev_calendar( $args ) {
    if ( empty( $args['rev_calendar_id'] ) ) {
        $rev_calendar_id = wp_insert_post( array( 
            'post_type'   => 'wpcalendars',
            'post_title'  => $args['name'],
            'post_status' => 'inherit',
            'post_parent' => intval( $args['calendar_id'] )
        ) );

        update_post_meta( $rev_calendar_id, '_type', $args['type'] );
        update_post_meta( $rev_calendar_id, '_settings', $args['settings'] );
    } else {
        $rev_calendar_id = $args['rev_calendar_id'];
        update_post_meta( $rev_calendar_id, '_settings', $args['settings'] );
    }
    
    return $rev_calendar_id;
}

/**
 * Delete revision calendar
 * @param type $ori_calendar_id
 * @return boolean
 */
function wpcalendars_delete_rev_calendar( $ori_calendar_id ) {
    $args = array(
        'post_type'   => 'wpcalendars',
        'post_status' => 'inherit',
        'post_parent' => intval( $ori_calendar_id )
    );
    
    $revisions = get_children( $args );
    
    if ( !$revisions ) {
        return;
    }
    
    foreach ( $revisions as $revision ) {
        wp_delete_post( intval( $revision->ID ), true );
    }
    
    return true;
}

/**
 * Save calendar
 * @param array $args
 * @return boolean
 */
function wpcalendars_save_calendar( $args ) {
    wp_update_post( array(
        'ID'         => intval( $args['calendar_id'] ),
        'post_title' => $args['name'],
    ) );
    
    update_post_meta( $args['calendar_id'], '_type', $args['type'] );
    update_post_meta( $args['calendar_id'], '_settings', $args['settings'] );
    
    return true;
}

/**
 * Change calendar type
 * @param array $args
 * @return boolean
 */
function wpcalendars_change_calendar_type( $args ) {
    wp_update_post( array(
        'ID'         => intval( $args['calendar_id'] ),
        'post_title' => $args['name'],
    ) );
    
    $post_meta = get_post_meta( intval( $args['calendar_id'] ) );
    
    foreach ( $post_meta as $key => $value ) {
        delete_post_meta( $args['calendar_id'], $key );
    }
    
    update_post_meta( $args['calendar_id'], '_type', $args['type'] );
    update_post_meta( $args['calendar_id'], '_settings', $args['settings'] );
    
    return true;
}

/**
 * Delete calendar
 * @param integer $calendar_id
 * @return boolean
 */
function wpcalendars_delete_calendar( $calendar_id ) {
    wp_delete_post( intval( $calendar_id ), true );
    return true;
}

/**
 * Duplicate the existing calendar
 * @param integer $calendar_id
 * @return type
 */
function wpcalendars_duplicate_calendar( $calendar_id ) {
    $old_calendar = wpcalendars_get_calendar( $calendar_id );
    
    if ( !$old_calendar ) {
        return;
    }
    
    $args = array(
        'name'     => $old_calendar['name'],
        'type'     => $old_calendar['type'],
        'settings' => $old_calendar['settings']
    );
    
    $new_calendar_id = wpcalendars_add_new_calendar( $args );
    
    wp_update_post( array(
        'ID'         => intval( $new_calendar_id ),
        'post_title' => sprintf( '%s (ID #%s)', $old_calendar['name'], $new_calendar_id ),
    ) );
}

/**
 * Save event
 * @global array $wpdb
 * @param type $event_id
 * @param type $data
 * @param type $format
 */
function wpcalendars_save_event( $event_id, $data, $format ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $event = wpcalendars_get_event( $event_id );

    if ( empty( $event['event_id'] ) ) {
        $wpdb->insert( $table_name, $data, $format );
    } else {
        $wpdb->update( $table_name, $data, array( 'event_id' => $event_id, 'event_parent' => 0 ), $format, array( '%d', '%d' ) );
    }
}

/**
 * Delete event
 * @global array $wpdb
 * @param type $event_id
 */
function wpcalendars_delete_event( $event_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    if ( ! empty( $event_id ) ) {
        $wpdb->delete( $table_name, array( 'event_id' => $event_id ), array( '%d' ) );
    }
}

/**
 * Setup sample categories
 * @param array $args
 * @return boolean
 */
function wpcalendars_setup_sample_category( $args ) {
    $sample_categories = get_option( 'wpcalendars_sample_categories' );
    
    if ( $sample_categories ) {
        return false;
    }
    
    $category_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars_evcat',
        'post_title'  => $args['name'],
        'post_status' => 'publish'
    ) );

    update_post_meta( $category_id, '_bgcolor', $args['bgcolor'] );
    
    if ( is_wp_error( $category_id ) ) {
        return false;
    }
    
    return $category_id;
}

/**
 * Setup sample venues
 * @param type $args
 * @return boolean
 */
function wpcalendars_setup_sample_venue( $args ) {
    $sample_venues = get_option( 'wpcalendars_sample_venues' );
    
    if ( $sample_venues ) {
        return false;
    }
    
    $post_content  = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam consectetuer mollis dolor. Integer fringilla. Phasellus at purus sed purus cursus iaculis.</p>';
    $post_content .= '<p>Nam molestie nisl at metus. Proin diam augue, semper vitae, varius et, viverra id, felis. Aenean luctus vulputate turpis. Nam pharetra.</p>';
    
    $venue_id = wp_insert_post( array( 
        'post_type'    => 'wpcalendars_venue',
        'post_title'   => $args['name'],
        'post_content' => $post_content,
        'post_status'  => 'publish'
    ) );
    
    if ( is_wp_error( $venue_id ) ) {
        return false;
    }
    
    update_post_meta( $venue_id, '_address', $args['address'] );
    update_post_meta( $venue_id, '_city', $args['city'] );
    update_post_meta( $venue_id, '_country', $args['country'] );
    update_post_meta( $venue_id, '_state', $args['state'] );
    update_post_meta( $venue_id, '_postal_code', $args['postal_code'] );
    update_post_meta( $venue_id, '_email', $args['email'] );
    update_post_meta( $venue_id, '_phone', $args['phone'] );
    update_post_meta( $venue_id, '_website', $args['website'] );
    update_post_meta( $venue_id, '_latitude', $args['latitude'] );
    update_post_meta( $venue_id, '_longitude', $args['longitude'] );
    update_post_meta( $venue_id, '_place_id', $args['place_id'] );
    
    return $venue_id;
}

/**
 * Setup sample organizers
 * @param type $args
 * @return boolean
 */
function wpcalendars_setup_sample_organizer( $args ) {
    $sample_organizers = get_option( 'wpcalendars_sample_organizers' );
    
    if ( $sample_organizers ) {
        return false;
    }
    
    $post_content  = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam consectetuer mollis dolor. Integer fringilla. Phasellus at purus sed purus cursus iaculis.</p>';
    $post_content .= '<p>Nam molestie nisl at metus. Proin diam augue, semper vitae, varius et, viverra id, felis. Aenean luctus vulputate turpis. Nam pharetra.</p>';
    
    $organizer_id = wp_insert_post( array( 
        'post_type'    => 'wpcalendars_organizr',
        'post_title'   => $args['name'],
        'post_content' => $post_content,
        'post_status'  => 'publish'
    ) );
    
    if ( is_wp_error( $organizer_id ) ) {
        return false;
    }
    
    update_post_meta( $organizer_id, '_email', $args['email'] );
    update_post_meta( $organizer_id, '_phone', $args['phone'] );
    update_post_meta( $organizer_id, '_website', $args['website'] );
    
    return $organizer_id;
}

/**
 * Setup sample events
 * @global array $wpdb
 * @param type $args
 * @return boolean
 */
function wpcalendars_setup_sample_event( $args ) {
    $sample_events = get_option( 'wpcalendars_sample_events' );
    
    if ( $sample_events ) {
        return false;
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $post_content  = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam consectetuer mollis dolor. Integer fringilla. Phasellus at purus sed purus cursus iaculis.</p>';
    $post_content .= '<p>Nam molestie nisl at metus. Proin diam augue, semper vitae, varius et, viverra id, felis. Aenean luctus vulputate turpis. Nam pharetra.</p>';
    
    $post_excerpt = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam consectetuer mollis dolor. Integer fringilla. Phasellus at purus sed purus cursus iaculis.';

    $post_id = wp_insert_post( array( 
        'post_type'    => 'wpcalendars_event',
        'post_title'   => $args['name'],
        'post_content' => $post_content,
        'post_excerpt' => $post_excerpt,
        'post_status'  => 'publish'
    ) );
    
    if ( is_wp_error( $post_id ) ) {
        return false;
    }
    
    $data = array(
        'event_id'              => $post_id,
        'start_date'            => $args['start_date'],
        'end_date'              => $args['end_date'],
        'start_time'            => $args['start_time'],
        'end_time'              => $args['end_time'],
        'all_day'               => $args['all_day'],
        'category_id'           => $args['category_id'],
        'venue_id'              => $args['venue_id'],
        'organizer_id'          => $args['organizer_id'],
        'hide_event_listings'   => $args['hide_event_listings'],
        'disable_event_details' => $args['disable_event_details']
    );
    
    $format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' );
    
    $wpdb->insert( $table_name, $data, $format );
    
    return $post_id;
}

/**
 * Add new event category
 * @param array $args
 * @return type
 */
function wpcalendars_add_new_event_category( $args ) {
    $category_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars_evcat',
        'post_title'  => $args['name'],
        'post_status' => 'publish'
    ) );
    
    update_post_meta( $category_id, '_bgcolor', $args['bgcolor'] );
    
    return $category_id;
}

/**
 * Delete event category
 * @global array $wpdb
 * @param integer $category_id
 * @return boolean
 */
function wpcalendars_delete_event_category( $category_id ) {
    global $wpdb;
    
    $default_event_category = wpcalendars_settings_value( 'general', 'default_event_category' );

    $table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $wpdb->update( $table_name, array( 'category_id' => $default_event_category ), array( 'category_id' => $category_id ), array( '%d' ), array( '%d' ) );
    
    wp_delete_post( $category_id, true );
    return true;
}

/**
 * Save event category
 * @param array $args
 * @return boolean
 */
function wpcalendars_save_event_category( $args ) {
    wp_update_post( array(
        'ID'         => $args['category_id'],
        'post_title' => $args['name'],
    ) );
    
    update_post_meta( $args['category_id'], '_bgcolor', $args['bgcolor'] );
    
    return true;
}

/**
 * Upgrade database for version 1.0.5
 * @global array $wpdb
 */
function wpcalendars_upgrade105() {
    global $wpdb;
    
    $wpcalendars_version = get_option( 'wpcalendars_version', '1.0.0' );
        
    if ( version_compare( $wpcalendars_version, '1.0.5', '<' ) ) {
        $format = 'select p.ID as calendar_id, p.post_title as name, pm.meta_value as type from %s p ';
        $format .= 'join %s pm on p.ID = pm.post_id ';
        $format .= 'where p.post_type = "%s" and p.post_status = "%s" and pm.meta_key = "%s" and pm.meta_value = "%s" ';
        $format .= 'order by pm.meta_value ASC';

        $results = $wpdb->get_results( sprintf( $format, $wpdb->posts, $wpdb->postmeta, 'wpcalendars', 'publish', '_wpcalendars_calendar_type', 'monthly' ) );

        foreach ( $results as $result ) {
            update_post_meta( $result->calendar_id, '_wpcalendars_calendar_type', 'multiple-months' );
        }
    }
}

/**
 * Upgrade database for version 1.2
 * @global array $wpdb
 */
function wpcalendars_upgrade12() {
    global $wpdb;
    
    $old_table_name = $wpdb->prefix . 'wpcalendars_event_details';
    $new_table_name = $wpdb->prefix . 'wpcalendars_events';
    
    $wpcalendars_version = get_option( 'wpcalendars_version', '1.0.0' );
        
    if ( version_compare( $wpcalendars_version, '1.1.4', '<' ) ) {
        // --- Update Monthly Calendar ---
        
        $format = 'select p.ID as calendar_id, p.post_title as name, pm.meta_value as type from %s p ';
        $format .= 'join %s pm on p.ID = pm.post_id ';
        $format .= 'where p.post_type = "%s" and p.post_status = "%s" and pm.meta_key = "%s" and pm.meta_value = "%s" ';
        $format .= 'order by pm.meta_value ASC';

        $results = $wpdb->get_results( sprintf( $format, $wpdb->posts, $wpdb->postmeta, 'wpcalendars', 'publish', '_wpcalendars_calendar_type', 'single-month' ) );

        foreach ( $results as $result ) {
            update_post_meta( $result->calendar_id, '_wpcalendars_calendar_type', 'monthly' );
        }
        
        // --- Update Calendar Settings ---
        
        $objs = get_posts( array(
            'post_type'      => 'wpcalendars',
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC'
        ) );

        $calendars = array();

        foreach( $objs as $obj ) {
            $calendars[] = array(
                'calendar_id' => $obj->ID,
                'type'        => get_post_meta( $obj->ID, '_wpcalendars_calendar_type', true ),
                'settings'    => get_post_meta( $obj->ID, '_wpcalendars_calendar_settings', true ),
            );
        }
        
        foreach ( $calendars as $calendar ) {
            update_post_meta( $calendar['calendar_id'], '_type', $calendar['type'] );
            update_post_meta( $calendar['calendar_id'], '_settings', $calendar['settings'] );
        }
        
        // --- Update Categories ---
        
        $objs = get_posts( array(
            'post_type'      => 'wpcalendars_evcat',
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC'
        ) );

        $categories = array();

        foreach( $objs as $obj ) {
            $categories[] = array(
                'category_id' => $obj->ID,
                'bgcolor'     => get_post_meta( $obj->ID, '_wpcalendars_event_bgcolor', true ),
            );
        }
        
        foreach ( $categories as $category ) {
            update_post_meta( $category['category_id'], '_bgcolor', $category['bgcolor'] );
        }
        
        // --- Update Events ---
        
        $sql = "select a.*, b.* from $wpdb->posts a left join $old_table_name b on a.ID = b.post_id ";
        $results = $wpdb->get_results( $sql );
        
        foreach ( $results as $result ) {
            $data = array(
                'event_id'              => $result->ID,
                'start_date'            => $result->start_date,
                'end_date'              => $result->end_date,
                'start_time'            => $result->start_time,
                'end_time'              => $result->end_time,
                'all_day'               => $result->all_day,
                'category_id'           => $result->category,
                'venue_id'              => $result->venue,
                'organizer_id'          => $result->organizer,
                'hide_event_listings'   => $result->hide_event_listings,
                'disable_event_details' => $result->disable_event_details,
            );

            $format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' );

            $wpdb->insert( $new_table_name, $data, $format );
        }
        
        // --- Delete Old Table
        
        $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wpcalendars_event_details' );
        
        // --- Update Venues ---
        
        $objs = get_posts( array(
            'post_type'      => 'wpcalendars_venue',
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC'
        ) );

        $venues = array();

        foreach( $objs as $obj ) {
            $venues[] = array(
                'venue_id'    => $obj->ID,
                'address'     => get_post_meta( $obj->ID, '_wpcalendars_venue_address', true ),
                'city'        => get_post_meta( $obj->ID, '_wpcalendars_venue_city', true ),
                'country'     => get_post_meta( $obj->ID, '_wpcalendars_venue_country', true ),
                'state'       => get_post_meta( $obj->ID, '_wpcalendars_venue_state', true ),
                'postal_code' => get_post_meta( $obj->ID, '_wpcalendars_venue_postal_code', true ),
                'latitude'    => get_post_meta( $obj->ID, '_wpcalendars_venue_latitude', true ),
                'longitude'   => get_post_meta( $obj->ID, '_wpcalendars_venue_longitude', true ),
                'place_id'    => get_post_meta( $obj->ID, '_wpcalendars_venue_place_id', true ),
                'email'       => get_post_meta( $obj->ID, '_wpcalendars_venue_email', true ),
                'phone'       => get_post_meta( $obj->ID, '_wpcalendars_venue_phone', true ),
                'website'     => get_post_meta( $obj->ID, '_wpcalendars_venue_website', true ),
            );
        }
        
        foreach ( $venues as $venue ) {
            update_post_meta( $venue['venue_id'], '_address', $venue['address'] );
            update_post_meta( $venue['venue_id'], '_city', $venue['city'] );
            update_post_meta( $venue['venue_id'], '_country', $venue['country'] );
            update_post_meta( $venue['venue_id'], '_state', $venue['state'] );
            update_post_meta( $venue['venue_id'], '_postal_code', $venue['postal_code'] );
            update_post_meta( $venue['venue_id'], '_latitude', $venue['latitude'] );
            update_post_meta( $venue['venue_id'], '_longitude', $venue['longitude'] );
            update_post_meta( $venue['venue_id'], '_place_id', $venue['place_id'] );
            update_post_meta( $venue['venue_id'], '_email', $venue['email'] );
            update_post_meta( $venue['venue_id'], '_phone', $venue['phone'] );
            update_post_meta( $venue['venue_id'], '_website', $venue['website'] );
        }
        
        // --- Update Organizers ---
        
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
                'email'        => get_post_meta( $obj->ID, '_wpcalendars_organizer_email', true ),
                'phone'        => get_post_meta( $obj->ID, '_wpcalendars_organizer_phone', true ),
                'website'      => get_post_meta( $obj->ID, '_wpcalendars_organizer_website', true ),
            );
        }
        
        foreach ( $organizers as $organizer ) {
            update_post_meta( $organizer['organizer_id'], '_email', $organizer['email'] );
            update_post_meta( $organizer['organizer_id'], '_phone', $organizer['phone'] );
            update_post_meta( $organizer['organizer_id'], '_website', $organizer['website'] );
        }
    }
}

/**
 * Add new venue
 * @param string $venue_name
 * @return integer
 */
function wpcalendars_add_venue( $venue_name ) {
    $venue_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars_venue',
        'post_title'  => $venue_name,
        'post_status' => 'publish'
    ) );
    
    return $venue_id;
}

/**
 * Add new organizer
 * @param string $organizer_name
 * @return integer
 */
function wpcalendars_add_organizer( $organizer_name ) {
    $organizer_id = wp_insert_post( array( 
        'post_type'   => 'wpcalendars_organizr',
        'post_title'  => $organizer_name,
        'post_status' => 'publish'
    ) );
    
    return $organizer_id;
}

/**
 * Render builder heading settings
 * @param type $settings
 * @param type $show_format_options
 */
function wpcalendars_builder_heading_settings( $settings, $show_format_options = false ) {
    $format_options = array(
        'startmonth-endmonth' => __( 'Start Month - End Month', 'wpcalendars' ),
        'startyear-endyear'   => __( 'Start Year - End Year', 'wpcalendars' )
    );
    ?>
    <h3><?php echo esc_html__( 'Heading Settings', 'wpcalendars' ) ?></h3>
    
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[show_heading]" value="N">
            <label><input type="checkbox" name="settings[show_heading]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['show_heading'] ) ?>> <?php echo esc_html__( 'Show Heading', 'wpcalendars' ) ?></label>
        </div>
    </div>
    
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Text Before', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="text" name="settings[heading_text_before]" class="wpcalendars-form-input-text wpcalendars-full-width-input" value="<?php echo esc_attr( $settings['heading_text_before'] ) ?>">
        </div>
    </div>
    
    <?php if ( $show_format_options ): ?>
    
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Heading Format', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[heading_format]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $format_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['heading_format'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Separator', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="text" name="settings[heading_separator]" class="wpcalendars-form-input-text wpcalendars-full-width-input" value="<?php echo esc_attr( $settings['heading_separator'] ) ?>">
        </div>
    </div>
    <?php
    endif;
}

/**
 * Render builder display settings
 * @param type $settings
 */
function wpcalendars_builder_display_settings( $settings ) {
    $current_year  = date( 'Y' );
    $current_month = date( 'm' );
        
    $display_options = array(
        'two-months'   => __( 'Two Months', 'wpcalendars' ),
        'three-months' => __( 'Three Months', 'wpcalendars' ),
        'four-months'  => __( 'Four Months', 'wpcalendars' ),
        'six-months'   => __( 'Six Months', 'wpcalendars' ),
        'one-year'     => __( 'One Year', 'wpcalendars' ),
        'custom'       => __( 'Custom Months', 'wpcalendars' ),
    );

    $first_month_options = array(
        'default' => __( 'Default Month', 'wpcalendars' ),
        'current' => __( 'Current Month', 'wpcalendars' )
    );
    
    $month_options = wpcalendars_get_month_options();
    
    $custom_default_start_year_options = array();
    
    $max_counter = date( 'Y' ) + 5;
    
    for ( $i = 2019; $i < $max_counter; $i++ ) {
        $custom_default_start_year_options[$i] = $i;
    }
    
    $settings['custom_start_month']        = isset( $settings['custom_start_month'] ) && '' !== $settings['custom_start_month'] ? $settings['custom_start_month'] : '07';
    $settings['custom_end_month']          = isset( $settings['custom_end_month'] ) && '' !== $settings['custom_end_month'] ? $settings['custom_end_month'] : '06';
    $settings['custom_default_start_year'] = isset( $settings['custom_default_start_year'] ) && '' !== $settings['custom_default_start_year'] ? $settings['custom_default_start_year'] : date( 'Y' );
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Display Calendar for', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="wpcalendars-display-selection" name="settings[display]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $display_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['display'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div id="wpcalendars-display-non-custom" style="<?php if ( 'custom' === $settings['display'] ) echo 'display: none;' ?>">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'First Month', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select name="settings[first_month]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $first_month_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['first_month'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div id="wpcalendars-display-custom" style="<?php if ( 'custom' !== $settings['display'] ) echo 'display: none;' ?>">
        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'Start Month', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select name="settings[custom_start_month]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $month_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['custom_start_month'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        
        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'End Month', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select name="settings[custom_end_month]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $month_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['custom_end_month'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        
        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'Default Start Year', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select name="settings[custom_default_start_year]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $custom_default_start_year_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['custom_default_start_year'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render builder month format settings
 * @param type $settings
 */
function wpcalendars_builder_month_format_settings( $settings ) {
    $month_format_options = array(
        'three-letter' => __( 'Three Letter', 'wpcalendars' ),
        'full'         => __( 'Full Name', 'wpcalendars' )
    );
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Month Format', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[month_format]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $month_format_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['month_format'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder weekday format settings
 * @param type $settings
 */
function wpcalendars_builder_weekday_format_settings( $settings ) {
    $weekday_format_options   = array(
        'one-letter'   => __( 'One Letter', 'wpcalendars' ),
        'three-letter' => __( 'Three Letter', 'wpcalendars' ),
        'full'         => __( 'Full Name', 'wpcalendars' )
    );
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Weekday Format', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[weekday_format]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $weekday_format_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['weekday_format'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder weekday position settings
 * @param type $settings
 */
function wpcalendars_builder_weekday_position_settings( $settings ) {
    $weekday_position_options = array(
        'top'  => __( 'Top', 'wpcalendars' ),
        'left' => __( 'Left', 'wpcalendars' )
    );
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Weekday Position', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[weekday_position]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $weekday_position_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['weekday_position'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder weekday start settings
 * @param type $settings
 */
function wpcalendars_builder_weekday_start_settings( $settings ) {
    $weekday_start_options = wpcalendars_get_weekday_options();
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Weekday Start', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[weekday_start]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $weekday_start_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['weekday_start'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder date format settings
 * @param type $settings
 */
function wpcalendars_builder_date_format_settings( $settings ) {
    $date_format_options = wpcalendars_get_date_format_options();
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Date Format', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[date_format]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $date_format_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['date_format'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[show_year]" value="N">
            <label><input type="checkbox" name="settings[show_year]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['show_year'] ) ?>> <?php echo esc_html__( 'Show Event Year', 'wpcalendars' ) ?></label>
        </div>
    </div>
    <?php
}

/**
 * Render builder time format settings
 * @param type $settings
 */
function wpcalendars_builder_time_format_settings( $settings ) {
    $time_format_options = wpcalendars_get_time_format_options();
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Time Format', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[time_format]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $time_format_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['time_format'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder column size settings
 * @param type $settings
 */
function wpcalendars_builder_column_size_settings( $settings ) {
    $column_size_options   = array(
        'small'  => __( 'Small Column', 'wpcalendars' ),
        'medium' => __( 'Medium Column', 'wpcalendars' ),
        'large'  => __( 'Large Column', 'wpcalendars' ),
        'full'   => __( 'Full Width', 'wpcalendars' )
    );
    ?>
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Column Size', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[column_size]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $column_size_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['column_size'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder prev/next navigation settings
 * @param type $settings
 */
function wpcalendars_builder_prevnext_navigation_settings( $settings ) {
    ?>
    <h3><?php echo esc_html__( 'Prev/Next Navigation', 'wpcalendars' ) ?></h3>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[prevnext_nav]" value="N">
            <label><input type="checkbox" name="settings[prevnext_nav]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['prevnext_nav'] ) ?>> <?php echo esc_html__( 'Enable Prev/Next Navigation', 'wpcalendars' ) ?></label>
        </div>
    </div>
    <?php
}

/**
 * Render builder daily event navigation settings
 * @param type $settings
 */
function wpcalendars_builder_daily_event_navigation_settings( $settings ) {
    $daily_event_nav_options = array(
        'popup'   => __( 'Popup Window', 'wpcalendars' ),
        'tooltip' => __( 'Tooltip', 'wpcalendars' ),
    );
    
    $tooltip_theme_options = array(
        'borderless' => __( 'Borderless', 'wpcalendars' ),
        'light'      => __( 'Light', 'wpcalendars' ),
        'noir'       => __( 'Noir', 'wpcalendars' ),
        'punk'       => __( 'Punk', 'wpcalendars' ),
        'shadow'     => __( 'Shadow', 'wpcalendars' ),
    );
    
    $tooltip_trigger_options = array(
        'click' => __( 'Click', 'wpcalendars' ),
        'hover' => __( 'Hover', 'wpcalendars' ),
    );
    
    $layout_options = array(
        'simple'   => __( 'Simple Layout', 'wpcalendars' ),
        'advanced' => __( 'Advanced Layout', 'wpcalendars' ),
    );
    ?>
    <h3><?php echo esc_html__( 'Daily Event Navigation', 'wpcalendars' ) ?></h3>
        
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[enable_daily_event_nav]" value="N">
            <label><input type="checkbox" name="settings[enable_daily_event_nav]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['enable_daily_event_nav'] ) ?>> <?php echo esc_html__( 'Enable Daily Event Navigation', 'wpcalendars' ) ?></label>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Navigation Type', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="wpcalendars-event-nav-selection" name="settings[daily_event_nav]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $daily_event_nav_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['daily_event_nav'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    
    <div id="wpcalendars-daily-event-nav-tooltip" style="<?php if ( 'popup' === $settings['daily_event_nav'] ) echo 'display:none;' ?>">
        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'Tooltip Theme', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select id="" name="settings[tooltip_theme]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $tooltip_theme_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['tooltip_theme'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        
        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'Tooltip Trigger', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select id="" name="settings[tooltip_trigger]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $tooltip_trigger_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['tooltip_trigger'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <div class="wpcalendars-form-field">
            <div class="wpcalendars-form-label"><?php echo esc_html__( 'Tooltip Layout', 'wpcalendars' ) ?></div>
            <div class="wpcalendars-form-input">
                <select id="wpcalendars-tooltip-layout-selection" name="settings[tooltip_layout]" class="wpcalendars-select wpcalendars-full-width-input">
                    <?php foreach ( $layout_options as $key => $name ): ?>
                    <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['tooltip_layout'] ) ?>><?php echo esc_html( $name ) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render builder general events settings
 * @param type $settings
 */
function wpcalendars_builder_general_events_settings( $settings ) {
    $order_options = array(
        'ASC'  => __( 'Ascending', 'wpcalendars' ),
        'DESC' => __( 'Descending', 'wpcalendars' ),
    );

    $categories = wpcalendars_get_event_categories();
    $tags       = wpcalendars_get_tags();
    $venues     = wpcalendars_get_venues();
    $organizers = wpcalendars_get_organizers();
    ?>
    <h3><?php echo esc_html__( 'General Events Settings', 'wpcalendars' ) ?></h3>
        
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Select Categories', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[categories]" value="<?php echo $settings['categories'] ?>">
            <select multiple="multiple" data-placeholder="<?php echo esc_attr__( 'All Categories', 'wpcalendars' ) ?>" class="wpcalendars-select wpcalendars-select-multiple wpcalendars-full-width-input">
                <?php foreach ( $categories as $category ): ?>
                <option value="<?php echo intval( $category['category_id'] ) ?>"<?php echo selected( in_array( $category['category_id'], explode( ',', $settings['categories'] ) ) ) ?>><?php echo esc_html( $category['name'] ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Select Tags', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[tags]" value="<?php echo $settings['tags'] ?>">
            <select multiple="multiple" data-placeholder="<?php echo esc_attr__( 'All Tags', 'wpcalendars' ) ?>" class="wpcalendars-select wpcalendars-select-multiple wpcalendars-full-width-input">
                <?php foreach ( $tags as $key => $name ): ?>
                <option value="<?php echo intval( $key ) ?>"<?php echo selected( in_array( $key, explode( ',', $settings['tags'] ) ) ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Select Venues', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[venues]" value="<?php echo $settings['venues'] ?>">
            <select multiple="multiple" data-placeholder="<?php echo esc_attr__( 'All Venues', 'wpcalendars' ) ?>" class="wpcalendars-select wpcalendars-select-multiple wpcalendars-full-width-input">
                <?php foreach ( $venues as $venue ): ?>
                <option value="<?php echo intval( $venue['venue_id'] ) ?>"<?php echo selected( in_array( $venue['venue_id'], explode( ',', $settings['venues'] ) ) ) ?>><?php echo esc_html( $venue['name'] ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Select Organizers', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[organizers]" value="<?php echo $settings['organizers'] ?>">
            <select multiple="multiple" data-placeholder="<?php echo esc_attr__( 'All Organizers', 'wpcalendars' ) ?>" class="wpcalendars-select wpcalendars-select-multiple wpcalendars-full-width-input">
                <?php foreach ( $organizers as $organizer ): ?>
                <option value="<?php echo intval( $organizer['organizer_id'] ) ?>"<?php echo selected( in_array( $organizer['organizer_id'], explode( ',', $settings['organizers'] ) ) ) ?>><?php echo esc_html( $organizer['name'] ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Exclude Events', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="text" name="settings[exclude_events]" value="<?php echo esc_attr( $settings['exclude_events'] ) ?>" class="wpcalendars-form-input-text wpcalendars-full-width-input">
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Sort Order', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="" name="settings[sort_order]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $order_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['sort_order'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[show_hidden_events]" value="N">
            <label><input type="checkbox" name="settings[show_hidden_events]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['show_hidden_events'] ) ?>> <?php echo esc_html__( 'Show Hidden Events', 'wpcalendars' ) ?></label>
        </div>
    </div>
    <?php
}

/**
 * Render builder event listings settings
 * @param type $settings
 */
function wpcalendars_builder_event_listings_settings( $settings ) {
    $layout_options = array(
        'simple'   => __( 'Simple Layout', 'wpcalendars' ),
        'advanced' => __( 'Advanced Layout', 'wpcalendars' ),
    );
    
    $date_position_options = array(
        'above-title' => __( 'Above Event Title', 'wpcalendars' ),
        'below-title' => __( 'Below Event Title', 'wpcalendars' ),
        'left-title'  => __( 'Left Event Title', 'wpcalendars' ),
        'right-title' => __( 'Right Event Title', 'wpcalendars' ),
    );
    ?>
    <h3><?php echo esc_html__( 'Event Listings', 'wpcalendars' ) ?></h3>
        
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[show_event_listings]" value="N">
            <label><input type="checkbox" name="settings[show_event_listings]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['show_event_listings'] ) ?>> <?php echo esc_html__( 'Show Event Listings', 'wpcalendars' ) ?></label>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Heading Title', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="text" name="settings[event_listings_title]" class="wpcalendars-form-input-text wpcalendars-full-width-input" value="<?php echo esc_attr( $settings['event_listings_title'] ) ?>">
        </div>
    </div>
    
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Layout', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <select id="wpcalendars-event-listings-layout-selection" name="settings[event_listings_layout]" class="wpcalendars-select wpcalendars-full-width-input">
                <?php foreach ( $layout_options as $key => $name ): ?>
                <option value="<?php echo esc_attr( $key ) ?>"<?php echo selected( $key, $settings['event_listings_layout'] ) ?>><?php echo esc_html( $name ) ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Render builder category listings settings
 * @param type $settings
 */
function wpcalendars_builder_category_listings_settings( $settings ) {
    ?>
    <h3><?php echo esc_html__( 'Category Listings', 'wpcalendars' ) ?></h3>
        
    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-input">
            <input type="hidden" name="settings[show_category_listings]" value="N">
            <label><input type="checkbox" name="settings[show_category_listings]" class="wpcalendars-form-input-checkbox" value="Y"<?php echo checked( 'Y', $settings['show_category_listings'] ) ?>> <?php echo esc_html__( 'Show Category Listings', 'wpcalendars' ) ?></label>
        </div>
    </div>

    <div class="wpcalendars-form-field">
        <div class="wpcalendars-form-label"><?php echo esc_html__( 'Heading Title', 'wpcalendars' ) ?></div>
        <div class="wpcalendars-form-input">
            <input type="text" name="settings[category_listings_title]" class="wpcalendars-form-input-text wpcalendars-full-width-input" value="<?php echo esc_attr( $settings['category_listings_title'] ) ?>">
        </div>
    </div>
    <?php
}
