<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Admin_Meta_Boxes {

    private static $_instance = NULL;
    private static $saved_meta_boxes = false;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'add_meta_boxes',                       array( $this, 'add_meta_boxes' ), 30 );
        add_action( 'save_post',                            array( $this, 'save_meta_boxes' ), 1, 2 );
        add_action( 'wpcalendars_save_event_post_meta',     array( $this, 'save_event_meta_box' ), 10, 2 );
        add_action( 'wpcalendars_save_venue_post_meta',     array( $this, 'save_venue_details_meta_box' ), 10, 2 );
        add_action( 'wpcalendars_save_organizer_post_meta', array( $this, 'save_organizer_details_meta_box' ), 10, 2 );
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
     * Meta boxes initialization
     */
    public function add_meta_boxes() {
        add_meta_box( 'wpcalendars-event-date-time',   __( 'Event Date / Time', 'wpcalendars' ), array( $this, 'add_event_date_time_meta_box' ), 'wpcalendars_event', 'normal', 'high' );
        add_meta_box( 'wpcalendars-event-details',     __( 'Event Details', 'wpcalendars' ),     array( $this, 'add_event_details_meta_box' ), 'wpcalendars_event', 'normal', 'high' );
        add_meta_box( 'wpcalendars-venue-details',     __( 'Venue Details', 'wpcalendars' ),     array( $this, 'add_venue_details_meta_box' ), 'wpcalendars_venue', 'normal', 'default' );
        add_meta_box( 'wpcalendars-organizer-details', __( 'Organizer Details', 'wpcalendars' ), array( $this, 'add_organizer_details_meta_box' ), 'wpcalendars_organizr', 'normal', 'default' );
    }
    
    /**
     * Add event date time metabox
     * @param type $post
     * @param type $box
     */
    public function add_event_date_time_meta_box( $post, $box ) {
        $event = wpcalendars_get_event( $post->ID );
        
        $start_date = empty( $event['start_date'] ) ? $start_date = date( 'Y-m-d' ) : $event['start_date'];
        $start_time = empty( $event['start_time'] ) ? sprintf( '%s:00', date_i18n( 'H' ) ) : $event['start_time'];
        $end_date   = empty( $event['end_date'] ) ? $start_date : $event['end_date'];
        $end_time   = empty( $event['end_time'] ) ? sprintf( '%s:30', date_i18n( 'H' ) ) : $event['end_time'];
        $all_day    = empty( $event['all_day'] ) ? $all_day = 'Y' : $event['all_day'];
        $time_class = 'Y' === $all_day ? '' : 'active';
        
        wp_nonce_field( 'wpcalendars_save_event', 'wpcalendars_event_nonce' );
        ?>
        <table class="form-table">
            <tr valign="top">
				<th scope="row" class="titledesc"><?php echo esc_html__( 'Start', 'wpcalendars' ) ?></th>
				<td class="forminp">
                    <input type="text" id="start-datepicker" value="" readonly="readonly">
                    <input type="hidden" id="start-datepicker-alt" name="start_date" value="<?php echo esc_attr( $start_date ) ?>">
                    <input type="time" step="1800" name="start_time" value="<?php echo esc_attr( $start_time ) ?>" id="start-time" class="<?php echo $time_class ?>">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><?php echo esc_html__( 'To', 'wpcalendars' ) ?></th>
				<td class="forminp">
                    <input type="text" id="end-datepicker" value="" readonly="readonly">
                    <input type="hidden" id="end-datepicker-alt" name="end_date" value="<?php echo esc_attr( $end_date ) ?>">
                    <input type="time" step="1800" name="end_time" value="<?php echo esc_attr( $end_time ) ?>" id="end-time" class="<?php echo $time_class ?>">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc">&nbsp;</th>
				<td class="forminp">
                    <span>
                        <input type="hidden" name="all_day" value="N">
                        <label for="all-day"><input id="all-day" type="checkbox" name="all_day" value="Y" <?php checked( 'Y' === $all_day ) ?>>
                        <?php echo esc_html__( 'All Day', 'wpcalendars' ) ?></label>
                    </span>
				</td>
			</tr>
        </table>
        <?php
        do_action( 'wpcalendars_event_date_time_metabox', $post );
    }
    
    /**
     * Add event details metabox
     * @param type $post
     * @param type $box
     */
    public function add_event_details_meta_box( $post, $box ) {
        $event = wpcalendars_get_event( $post->ID );

        $category_options = wpcalendars_get_event_categories();
        $event_category   = empty( $event['category_id'] ) ? wpcalendars_settings_value( 'general', 'default_event_category' ) : $event['category_id'];
        
        $venue_options = wpcalendars_get_venues();
        $event_venue   = $event['venue_id'];
        
        $organizer_options = wpcalendars_get_organizers();
        $event_organizer   = $event['organizer_id'];
        
        $website = $event['website'];
        
        $disable_event_details = empty( $event['disable_event_details'] ) ? 'N' : $event['disable_event_details'];
        $hide_event_listings   = empty( $event['hide_event_listings'] ) ? 'N' : $event['hide_event_listings'];
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html__( 'Category', 'wpcalendars' ) ?></th>
                <td class="forminp">
                    <select id="wpcalendars-event-category-select" name="event_category" class="">
                        <?php foreach ( $category_options as $option ): ?>
                        <option value="<?php echo $option['category_id'] ?>"<?php echo selected( $option['category_id'], $event_category ) ?>><?php echo $option['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                    <p><a href="#" id="wpcalendars-new-category-button">+ <?php echo esc_html__( 'Create New Category', 'wpcalendars' ) ?></a></p>
                    <div id="wpcalendars-new-category" style="display:none;">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row" class="titledesc"><label for="wpcalendars-new-category-name"><?php echo esc_html__( 'New Category Name', 'wpcalendars' ) ?></label></th>
                                <td class="forminp"><input type="text" id="wpcalendars-new-category-name" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc"><label for="wpcalendars-new-category-bgcolor"><?php echo esc_html__( 'Background Color', 'wpcalendars' ) ?></label></th>
                                <td class="forminp"><input type="text" id="wpcalendars-new-category-bgcolor" class="color-picker"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">&nbsp;</th>
                                <td class="forminp"><button type="button" id="wpcalendars-add-category-button" class="button-secondary"><?php echo esc_html__( 'Add New Category', 'wpcalendars' ) ?></button></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html__( 'Venue', 'wpcalendars' ) ?></th>
                <td class="forminp">
                    <select id="wpcalendars-event-venue-select" name="event_venue" class="">
                        <option value="">&mdash; <?php echo esc_html__( 'Select', 'wpcalendars' ) ?> &mdash;</option>
                        <?php foreach ( $venue_options as $option ): ?>
                        <option value="<?php echo $option['venue_id'] ?>"<?php echo selected( $option['venue_id'], $event_venue ) ?>><?php echo $option['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                    <p><a href="#" id="wpcalendars-new-venue-button">+ <?php echo esc_html__( 'Create New Venue', 'wpcalendars' ) ?></a></p>
                    <div id="wpcalendars-new-venue" style="display:none;">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row" class="titledesc"><label for="wpcalendars-new-venue-name"><?php echo esc_html__( 'New Venue Name', 'wpcalendars' ) ?></label></th>
                                <td class="forminp">
                                    <input type="text" id="wpcalendars-new-venue-name" class="regular-text">
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">&nbsp;</th>
                                <td class="forminp">
                                    <button type="button" id="wpcalendars-add-venue-button" class="button-secondary"><?php echo esc_html__( 'Add New Venue', 'wpcalendars' ) ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html__( 'Organizer', 'wpcalendars' ) ?></th>
                <td class="forminp">
                    <select id="wpcalendars-event-organizer-select" name="event_organizer" class="">
                        <option value="">&mdash; <?php echo esc_html__( 'Select', 'wpcalendars' ) ?> &mdash;</option>
                        <?php foreach ( $organizer_options as $option ): ?>
                        <option value="<?php echo $option['organizer_id'] ?>"<?php echo selected( $option['organizer_id'], $event_organizer ) ?>><?php echo $option['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                    <p><a href="#" id="wpcalendars-new-organizer-button">+ <?php echo esc_html__( 'Create New Organizer', 'wpcalendars' ) ?></a></p>
                    <div id="wpcalendars-new-organizer" style="display:none;">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row" class="titledesc"><label for="wpcalendars-new-organizer-name"><?php echo esc_html__( 'New Organizer Name', 'wpcalendars' ) ?></label></th>
                                <td class="forminp">
                                    <input type="text" id="wpcalendars-new-organizer-name" class="regular-text">
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row" class="titledesc">&nbsp;</th>
                                <td class="forminp">
                                    <button type="button" id="wpcalendars-add-organizer-button" class="button-secondary"><?php echo esc_html__( 'Add New Organizer', 'wpcalendars' ) ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html__( 'Website / URL', 'wpcalendars' ) ?></th>
                <td class="forminp">
                    <input id="website" type="url" name="website" value="<?php echo esc_url( $website ) ?>" placeholder="http://" class="regular-text">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html__( 'Event Visibility', 'wpcalendars' ) ?></th>
                <td class="forminp">
                    <p><input type="hidden" name="disable_event_details" value="N">
                    <label for="disable-event-details"><input id="disable-event-details" type="checkbox" name="disable_event_details" value="Y" <?php checked( 'Y' === $disable_event_details ) ?>>
                    <?php echo esc_html__( 'Disable Single Event Details', 'wpcalendars' ) ?></label></p>
                    <p><input type="hidden" name="hide_event_listings" value="N">
                    <label for="hide-event-listings"><input id="hide-event-listings" type="checkbox" name="hide_event_listings" value="Y" <?php checked( 'Y' === $hide_event_listings ) ?>>
                    <?php echo esc_html__( 'Hide From Event Listings', 'wpcalendars' ) ?></label></p>
                </td>
            </tr>
        </table>
        <?php
    }
        
    /**
     * Save event details
     * @param integer $post_id
     * @param object $post
     * @return bool
     */
    public function save_event_meta_box( $post_id, $post ) {
        // Check the nonce
        if ( empty( $_POST['wpcalendars_event_nonce'] ) || !wp_verify_nonce( $_POST['wpcalendars_event_nonce'], 'wpcalendars_save_event' ) ) {
            return;
        }
        
        $data = array( 'event_id' => $post_id );
        $format = array( '%d' );
        
        // Event Type
        
        $data['event_type'] = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : 'no-repeat';
        $format[] = '%s';
        
        // Event Start Date
        
        if ( isset( $_POST['start_date'] ) ) {
            $data['start_date'] = sanitize_text_field( $_POST['start_date'] );
            $format[] = '%s';
        }
        
        // Event End Date
        
        if ( isset( $_POST['end_date'] ) ) {
            $data['end_date'] = sanitize_text_field( $_POST['end_date'] );
            $format[] = '%s';
        }
        
        // Event All Day
        
        $all_day_value = isset( $_POST['all_day'] ) && 'Y' === $_POST['all_day'] ? 'Y' : 'N';
        
        $data['all_day'] = $all_day_value;
        $format[] = '%s';
        
        // Event Start / End Time
        
        if ( 'Y' === $all_day_value ) {
            $start_time = '00:00:00';
            $end_time   = '00:00:00';
        } else {
            $start_time = sanitize_text_field( $_POST['start_time'] );
            $end_time   = sanitize_text_field( $_POST['end_time'] );
        }
        
        $data['start_time'] = $start_time;
        $format[] = '%s';
        
        $data['end_time'] = $end_time;
        $format[] = '%s';
        
        // Event Category
        
        if ( isset( $_POST['event_category'] ) ) {
            $data['category_id'] = intval( $_POST['event_category'] );
            $format[] = '%d';
        }
        
        // Event Visibility
        
        $disable_event_details = isset( $_POST['disable_event_details'] ) && 'Y' === $_POST['disable_event_details'] ? 'Y' : 'N';
        
        $data['disable_event_details'] = $disable_event_details;
        $format[] = '%s';
        
        $hide_event_listings = isset( $_POST['hide_event_listings'] ) && 'Y' === $_POST['hide_event_listings'] ? 'Y' : 'N';
        
        $data['hide_event_listings'] = $hide_event_listings;
        $format[] = '%s';
        
        // Website

        if ( isset( $_POST['website'] ) ) {
            $data['website'] = esc_url_raw( $_POST['website'] );
            $format[] = '%s';
        }
        
        // Venue
        
        if ( isset( $_POST['event_venue'] ) ) {
            $data['venue_id'] = intval( $_POST['event_venue'] );
            $format[] = '%d';
        }
        
        // Organizer
        
        if ( isset( $_POST['event_organizer'] ) ) {
            $data['organizer_id'] = intval( $_POST['event_organizer'] );
            $format[] = '%d';
        }
        
        wpcalendars_save_event( $post_id, $data, $format );
    }
    
    /**
     * Add venue details metabox
     * @param object $post
     * @param object $box
     */
    public function add_venue_details_meta_box( $post, $box ) {
        $address     = get_post_meta( $post->ID, '_address', true );
        $city        = get_post_meta( $post->ID, '_city', true );
        $country     = get_post_meta( $post->ID, '_country', true );
        $state       = get_post_meta( $post->ID, '_state', true );
        $postal_code = get_post_meta( $post->ID, '_postal_code', true );
        $email       = get_post_meta( $post->ID, '_email', true );
        $phone       = get_post_meta( $post->ID, '_phone', true );
        $website     = get_post_meta( $post->ID, '_website', true );
        $latitude    = get_post_meta( $post->ID, '_latitude', true );
        $longitude   = get_post_meta( $post->ID, '_longitude', true );
        $place_id    = get_post_meta( $post->ID, '_place_id', true );
        
        $countries = include WPCALENDARS_PLUGIN_DIR . 'includes/countries.php';

        global $states;
        
        if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php' ) ) {
            include WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php';
        }
        
        $map_service        = wpcalendars_settings_value( 'general', 'map_provider' );
        $map_zoom           = wpcalendars_settings_value( 'general', 'map_zoom' );
        $google_maps_apikey = wpcalendars_settings_value( 'api', 'google_maps_apikey' );

        $remove_location_style = ( '' === $latitude && '' === $longitude ) ? 'display:none' : '';

        wp_nonce_field( 'wpcalendars_save_venue_details', 'wpcalendars_venue_details_nonce' );
        ?>
        <table class="form-table">
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="address"><?php echo esc_html__( 'Address', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="address" type="text" name="address" value="<?php echo $address ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="city"><?php echo esc_html__( 'City', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="city" type="text" name="city" value="<?php echo $city ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="country"><?php echo esc_html__( 'Country', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <select id="country" name="country">
                        <option value="">&mdash; <?php echo esc_html__( 'Select Country', 'wpcalendars' ) ?> &mdash;</option>
                        <?php foreach ( $countries as $code => $name ): ?>
                        <option value="<?php echo $code ?>"<?php selected( $country, $code ) ?>><?php echo $name ?></option>
                        <?php endforeach ?>
                    </select>
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="state"><?php echo esc_html__( 'State / Province', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <div id="states-container">
                        <?php if ( isset( $states[$country] ) ): ?>
                        <select id="state" name="state">
                            <option value="">&mdash; <?php echo __( 'Select State / Province', 'wpcalendars' ) ?> &mdash;</option>
                            <?php foreach ( $states[$country] as $code => $name ): ?>
                            <option value="<?php echo $code ?>"<?php selected( $state, $code ) ?>><?php echo $name ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php else: ?>
                        <input id="state" type="text" name="state" value="<?php echo $state ?>" class="regular-text">
                        <?php endif ?>
                    </div>
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="postal_code"><?php echo esc_html__( 'Postal Code', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="postal_code" type="number" name="postal_code" value="<?php echo $postal_code ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="email"><?php echo esc_html__( 'Email', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="email" type="email" name="email" value="<?php echo $email ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="phone"><?php echo esc_html__( 'Phone', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="phone" type="text" name="phone" value="<?php echo $phone ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="website"><?php echo esc_html__( 'Website', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="website" type="url" name="website" value="<?php echo $website ?>" placeholder="http://" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="address"><?php echo esc_html__( 'Maps', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="latitude" type="hidden" name="latitude" value="<?php echo $latitude ?>">
                    <input id="longitude" type="hidden" name="longitude" value="<?php echo $longitude ?>">
                    <input id="place_id" type="hidden" name="place_id" value="<?php echo $place_id ?>">
                    <button type="button" id="wpcalendars-get-location-button" class="button-secondary"><?php echo esc_html__( 'Update Location', 'wpcalendars' ) ?></button>
                    <button style="<?php echo $remove_location_style ?>" type="button" id="wpcalendars-remove-location-button" class="button-secondary"><?php echo esc_html__( 'Remove Location', 'wpcalendars' ) ?></button>
                    <div style="display:none"><input class="controls" id="venue-search" type="text" placeholder="<?php echo esc_html__( 'Search Venue', 'wpcalendars' ) ?>"></div>
                    <div id="infowindow-content">
                        <span id="place-name" class="title"></span><br>
                        <span id="place-address"></span>
                    </div>
                    <div id="wpcalendars-location-map" class="wpcalendars-map-panel mfp-hide">
                        <div id="map"></div>
                        <p>
                            <button type="button" id="wpcalendars-update-location-button" class="button-primary"><?php echo esc_html__( 'Update Location', 'wpcalendars' ) ?></button>
                            <button type="button" id="wpcalendars-cancel-location-button" class="wpcalendars-close-button button-secondary"><?php echo esc_html__( 'Cancel', 'wpcalendars' ) ?></button>
                        </p>
                    </div>
                    <div id="wpcalendars-location-static-map" class="location-static-map regular-text">
                        <?php if ( 'google' === $map_service && $latitude !== '' && $longitude !== '' ): ?>
                        <img src="//maps.googleapis.com/maps/api/staticmap?center=<?php echo $latitude, ',', $longitude ?>&markers=color:red%7Clabel:S%7C<?php echo $latitude, ',', $longitude ?>&zoom=<?php echo $map_zoom ?>&size=640x400&key=<?php echo $google_maps_apikey?>" />
                        <?php endif ?>
                    </div>
				</td>
			</tr>
        </table>
        <?php
        do_action( 'wpcalendars_venue_details_metabox', $post );
    }
    
    /**
     * Save venue details metabox
     * @param integer $post_id
     * @param object $post
     * @return bool
     */
    public function save_venue_details_meta_box( $post_id, $post ) {
        // Check the nonce
        if ( empty( $_POST['wpcalendars_venue_details_nonce'] ) || !wp_verify_nonce( $_POST['wpcalendars_venue_details_nonce'], 'wpcalendars_save_venue_details' ) ) {
            return;
        }
        
        if ( isset( $_POST['address'] ) ) {
            update_post_meta( $post_id, '_address', sanitize_text_field( $_POST['address'] ) );
		}
        
        if ( isset( $_POST['city'] ) ) {
            update_post_meta( $post_id, '_city', sanitize_text_field( $_POST['city'] ) );
		}
        
        if ( isset( $_POST['country'] ) ) {
            update_post_meta( $post_id, '_country', sanitize_text_field( $_POST['country'] ) );
		}
        
        if ( isset( $_POST['state'] ) ) {
            update_post_meta( $post_id, '_state', sanitize_text_field( $_POST['state'] ) );
		}
        
        if ( isset( $_POST['postal_code'] ) ) {
            update_post_meta( $post_id, '_postal_code', sanitize_text_field( $_POST['postal_code'] ) );
		}
        
        if ( isset( $_POST['email'] ) ) {
			update_post_meta( $post_id, '_email', sanitize_text_field( $_POST['email'] ) );
		}
        
        if ( isset( $_POST['phone'] ) ) {
			update_post_meta( $post_id, '_phone', sanitize_text_field( $_POST['phone'] ) );
		}
        
        if ( isset( $_POST['website'] ) ) {
			update_post_meta( $post_id, '_website', sanitize_text_field( $_POST['website'] ) );
		}
        
        if ( isset( $_POST['latitude'] ) ) {
			update_post_meta( $post_id, '_latitude', sanitize_text_field( $_POST['latitude'] ) );
		}
        
        if ( isset( $_POST['longitude'] ) ) {
			update_post_meta( $post_id, '_longitude', sanitize_text_field( $_POST['longitude'] ) );
		}
        
        if ( isset( $_POST['place_id'] ) ) {
			update_post_meta( $post_id, '_place_id', sanitize_text_field( $_POST['place_id'] ) );
		}
    }
    
    /**
     * Add organizer details metabox
     * @param object $post
     * @param object $box
     */
    public function add_organizer_details_meta_box( $post, $box ) {
        $email   = get_post_meta( $post->ID, '_email', true );
        $phone   = get_post_meta( $post->ID, '_phone', true );
        $website = get_post_meta( $post->ID, '_website', true );
        
        wp_nonce_field( 'wpcalendars_save_organizer_details', 'wpcalendars_organizer_details_nonce' );
        ?>
        <table class="form-table">
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="email"><?php echo esc_html__( 'Email', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="email" type="email" name="email" value="<?php echo $email ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="phone"><?php echo esc_html__( 'Phone', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="phone" type="text" name="phone" value="<?php echo $phone ?>" class="regular-text">
				</td>
			</tr>
            <tr valign="top">
				<th scope="row" class="titledesc"><label for="website"><?php echo esc_html__( 'Website', 'wpcalendars' ) ?></label></th>
				<td class="forminp">
                    <input id="website" type="url" name="website" value="<?php echo $website ?>" placeholder="http://" class="regular-text">
				</td>
			</tr>
        </table>
        <?php
    }
    
    /**
     * Save organizer details metabox
     * @param integer $post_id
     * @param object $post
     * @return bool
     */
    public function save_organizer_details_meta_box( $post_id, $post ) {
        // Check the nonce
        if ( empty( $_POST['wpcalendars_organizer_details_nonce'] ) || !wp_verify_nonce( $_POST['wpcalendars_organizer_details_nonce'], 'wpcalendars_save_organizer_details' ) ) {
            return;
        }
        
        if ( isset( $_POST['email'] ) ) {
			update_post_meta( $post_id, '_email', sanitize_text_field( $_POST['email'] ) );
		}
        
        if ( isset( $_POST['phone'] ) ) {
			update_post_meta( $post_id, '_phone', sanitize_text_field( $_POST['phone'] ) );
		}
        
        if ( isset( $_POST['website'] ) ) {
			update_post_meta( $post_id, '_website', sanitize_text_field( $_POST['website'] ) );
		}
    }
        
    /**
     * Save meta boxes
     * @param type $post_id
     * @param type $post
     * @return type
     */
    public function save_meta_boxes( $post_id, $post ) {
        if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        self::$saved_meta_boxes = true;

        switch ( $post->post_type ) {
            case 'wpcalendars_event':
                do_action( 'wpcalendars_save_event_post_meta', $post_id, $post );
                break;
            case 'wpcalendars_venue':
                do_action( 'wpcalendars_save_venue_post_meta', $post_id, $post );
                break;
            case 'wpcalendars_organizr':
                do_action( 'wpcalendars_save_organizer_post_meta', $post_id, $post );
                break;
        }
    }
}

WPCalendars_Admin_Meta_Boxes::instance();
