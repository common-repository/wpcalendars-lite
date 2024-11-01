<?php
// if uninstall.php is not called by WordPress, die
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

$wpcalendars_options = get_option( 'wpcalendars_options' );

if ( empty( $wpcalendars_options['general']['uninstall_remove_data'] ) || 'N' === $wpcalendars_options['general']['uninstall_remove_data'] ) {
    return;
}

// Delete tables
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wpcalendars_events' );

// Delete options
delete_option( 'wpcalendars_options' );
delete_option( 'wpcalendars_activation_redirect' );
delete_option( 'wpcalendars_hidden_events' );
delete_option( 'wpcalendars_queue_flush_rewrite_rules' );
delete_option( 'wpcalendars_pro_queue_flush_rewrite_rules' );

// Delete calendars/events/colors/venues/organizers
$post_types = array( 'wpcalendars', 'wpcalendars_event', 'wpcalendars_evcat', 'wpcalendars_venue', 'wpcalendars_organizr' );

foreach ( $post_types as $post_type ) {
    $posts = get_posts( array(
        'post_type'   => $post_type,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
    ) );

    if ( $posts ) {
        foreach ( $posts as $post ) {
            wp_delete_post( $post, true );
        }
    }
}

// Delete tags
$taxonomies = array( 'wpcalendars_event_tag' );

foreach ( $taxonomies as $taxonomy ) {
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ) );

    if ( $terms ) {
        foreach ( $terms as $term ) {
            wp_delete_term( $term->term_id, $taxonomy );
        }
    }
}

// Remove sample calendars (if any)

$sample_calendar_page = get_option( 'wpcalendars_sample_calendar_page' );
$sample_calendars     = get_option( 'wpcalendars_sample_calendars' );
$sample_categories    = get_option( 'wpcalendars_sample_categories' );
$sample_venues        = get_option( 'wpcalendars_sample_venues' );
$sample_organizers    = get_option( 'wpcalendars_sample_organizers' );
$sample_events        = get_option( 'wpcalendars_sample_events' );
        
// Delete calendar page

if ( intval( $sample_calendar_page ) > 0 ) {
    wp_delete_post( intval( $sample_calendar_page ), true );
}

// Delete sample calendars

foreach ( $sample_calendars as $calendar_id ) {
    wp_delete_post( intval( $calendar_id ), true );
}
        
// Delete sample categories

foreach ( $sample_categories as $category_id ) {
    wp_delete_post( intval( $category_id ), true );
}

// Delete sample venues

foreach ( $sample_venues as $venue_id ) {
    wp_delete_post( intval( $venue_id ), true );
}
        
// Delete sample organizers

foreach ( $sample_organizers as $organizer_id ) {
    wp_delete_post( intval( $organizer_id ), true );
}

// Delete sample events

$table_name = $wpdb->prefix . 'wpcalendars_events';
        
foreach ( $sample_events as $event_id ) {
    wp_delete_post( intval( $event_id ), true );
    $wpdb->delete( $table_name, array( 'event_id' => $event_id ), array( '%d' ) );
}

delete_option( 'wpcalendars_sample_calendar_page' );
delete_option( 'wpcalendars_sample_calendars' );
delete_option( 'wpcalendars_sample_categories' );
delete_option( 'wpcalendars_sample_venues' );
delete_option( 'wpcalendars_sample_organizers' );
delete_option( 'wpcalendars_sample_events' );
delete_option( 'wpcalendars_install_sample_calendar' );

// Remove any transients we've left behind.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpcalendars\_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_wpcalendars\_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_wpcalendars\_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_wpcalendars\_%'" );
