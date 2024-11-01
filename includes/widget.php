<?php
class WPCalendars_Widget_Upcoming_Events extends WP_Widget {
    
    protected $defaults;
    
    /**
     * Sets up a new Upcoming Events widget instance.
     */
    function __construct() {
        $this->defaults = array(
            'title'               => __( 'Upcoming Events', 'wpcalendars' ),
            'category'            => '',
            'tag'                 => '',
            'exclude_events'      => '',
            'show_hidden_events'  => false,
            'num_events'          => 5,
            'date_format'         => 'medium',
            'time_format'         => '24-hour',
            'show_day_name'       => false,
            'show_featured_image' => false,
            'show_excerpt'        => false,
            'show_venue'          => false,
            'show_year'           => false,
            'show_more'           => true,
            'more_text'           => __( 'View More', 'wpcalendars' ),
            'more_page_id'        => ''
        );

        $widget_slug = 'widget-wpcalendars-upcoming-events';

        $widget_ops = array(
            'classname'   => $widget_slug,
            'description' => esc_html_x( 'Display upcoming events.', 'Widget', 'wpcalendars' ),
        );

        $control_ops = array(
            'id_base' => $widget_slug,
        );

        parent::__construct( $widget_slug, esc_html_x( 'Upcoming Events', 'Widget', 'wpcalendars' ), $widget_ops, $control_ops );
    }
    
    /**
     * Outputs the content for the current Upcoming Events widget instance.
     * @global object $wp_locale
     * @param array $args
     * @param array $instance
     */
    function widget( $args, $instance ) {
        $instance = wp_parse_args( ( array ) $instance, $this->defaults );
        
        $event_args = array(
            'start_date'         => date( 'Y-m-d' ),
            'categories'         => $instance['category'],
            'tags'               => $instance['tag'],
            'exclude_events'     => $instance['exclude_events'],
            'show_hidden_events' => $instance['show_hidden_events'] ? 'Y' : 'N',
            'posts_per_page'     => $instance['num_events']
        );
        
        $upcoming_events = wpcalendars_get_events( $event_args );

        echo $args['before_widget'];

        if ( !empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        if ( empty( $upcoming_events ) ) {
            echo '<p>', esc_html__( 'No Events', 'wpcalendars' ), '</p>';
        } else {
            echo '<ul>';
            
            global $wp_locale;
            
            $show_year           = $instance['show_year'] ? 'Y' : 'N';
            $show_day_name       = $instance['show_day_name'] ? 'Y' : 'N';
            $show_featured_image = $instance['show_featured_image'] ? 'Y' : 'N';
            $show_excerpt        = $instance['show_excerpt'] ? 'Y' : 'N';
            $show_venue          = $instance['show_venue'] ? 'Y' : 'N';
            
            foreach ( $upcoming_events as $event ) {
                $start_date = explode( '-', $event['start_date'] );
                $weekday_name = $wp_locale->get_weekday( date( 'w', strtotime( $event['start_date'] ) ) );
                $weekday_name = $wp_locale->get_weekday_abbrev( $weekday_name );
                
                printf( '<li class="">' );
                
                if ( 'Y' === $show_day_name ) {
                    $start_date = explode( '-', $event['start_date'] );
                    $weekday_name = $wp_locale->get_weekday( date( 'w', strtotime( $event['start_date'] ) ) );
                    $weekday_name = $wp_locale->get_weekday_abbrev( $weekday_name );
                
                    printf( '<div class="wpcalendars-upcoming-event-day wpcalendars-event-category-%s">', $event['category_id'] );
                    printf( '<div class="wpcalendars-upcoming-event-day-name">%s</div>', $weekday_name );
                    printf( '<div class="wpcalendars-upcoming-event-day-date">%s</div>', $start_date[2] );
                    echo '</div>'; // Day Name
                }
                
                if ( 'Y' === $show_featured_image && has_post_thumbnail( $event['event_id'] ) ) {
                    echo '<div class="wpcalendars-upcoming-event-featured-image">';
                    echo get_the_post_thumbnail( $event['event_id'], 'wpcalendars-featured-image' );
                    echo '</div>'; // Featured Image
                }
                
                echo '<div class="wpcalendars-upcoming-event-summary">';
                
                if ( 'Y' === $event['disable_event_details'] ) {
                    printf( '<div class="wpcalendars-upcoming-event-summary-title">%s</div>', $event['event_title'] );
                } else {
                    printf( '<div class="wpcalendars-upcoming-event-summary-title"><a href="%s">%s</a></div>', wpcalendars_get_permalink( $event ), $event['event_title'] );
                }
                
                $date_time = wpcalendars_format_date( $event['start_date'], $event['end_date'], $instance['date_format'], $show_year );
                
                if ( 'N' === $event['all_day'] ) {
                    $date_time .= ' @ ' . wpcalendars_format_time( $event['start_time'], $event['end_time'], $instance['time_format'] );
                }
                
                printf( '<div class="wpcalendars-upcoming-event-summary-date">%s</div>', apply_filters( 'wpcalendars_event_widget_date_time', $date_time, $event ) );
                
                if ( 'Y' === $show_venue ) {
                    $venue = wpcalendars_get_venue( $event['venue_id'] );
                    
                    $countries = include WPCALENDARS_PLUGIN_DIR . 'includes/countries.php';

                    $location = array();
                    
                    if ( isset( $venue['name'] ) && '' !== $venue['name'] ) {
                        $location[] = $venue['name'];
                    }

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
                    
                    echo '<div class="wpcalendars-upcoming-event-summary-venue">';
                    echo implode( ', ', $location );
                    echo '</div>'; // Venue
                }
                
                if ( 'Y' === $show_excerpt ) {
                    echo '<div class="wpcalendars-upcoming-event-summary-excerpt">';
                    echo $event['event_excerpt'];
                    echo '</div>'; // Excerpt
                }
                
                echo '</div>'; // Event Summary
                echo '</li>';
            }
            
            echo '</ul>';
            
            if ( $instance['show_more'] ) {
                printf( '<div class="wpcalendars-upcoming-event-more"><a href="%s">%s</a></div>', get_permalink( $instance['more_page_id'] ), $instance['more_text'] );
            }
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Handles updating settings for the current Upcoming Events widget instance.
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    function update( $new_instance, $old_instance ) {
        $new_instance['title']               = wp_strip_all_tags( $new_instance['title'] );
        $new_instance['category']            = $new_instance['category'];
        $new_instance['tag']                 = $new_instance['tag'];
        $new_instance['exclude_events']      = $new_instance['exclude_events'];
        $new_instance['show_hidden_events']  = isset( $new_instance['show_hidden_events'] ) ? '1' : false;
        $new_instance['num_events']          = absint( $new_instance['num_events'] );
        $new_instance['date_format']         = $new_instance['date_format'];
        $new_instance['time_format']         = $new_instance['time_format'];
        $new_instance['show_day_name']       = isset( $new_instance['show_day_name'] ) ? '1' : false;
        $new_instance['show_featured_image'] = isset( $new_instance['show_featured_image'] ) ? '1' : false;
        $new_instance['show_excerpt']        = isset( $new_instance['show_excerpt'] ) ? '1' : false;
        $new_instance['show_venue']          = isset( $new_instance['show_venue'] ) ? '1' : false;
        $new_instance['show_year']           = isset( $new_instance['show_year'] ) ? '1' : false;
        $new_instance['show_more']           = isset( $new_instance['show_more'] ) ? '1' : false;
        $new_instance['more_text']           = wp_strip_all_tags( $new_instance['more_text'] );
        $new_instance['more_page_id']        = absint( $new_instance['more_page_id'] );

        return $new_instance;
    }
    
    /**
     * Outputs the settings form for the Upcoming Events widget.
     * @param array $instance
     */
    function form( $instance ) {
        $instance = wp_parse_args( ( array ) $instance, $this->defaults );
        
        $categories = wpcalendars_get_event_categories();
        $tags       = wpcalendars_get_tags();
        
        $date_format_options = wpcalendars_get_date_format_options();
        $time_format_options = wpcalendars_get_time_format_options();
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                <?php esc_html( _ex( 'Title:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'category' ); ?>">
                <?php esc_html( _ex( 'Category:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <select id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>" class="widefat">
                <option value=""><?php echo __( 'All Categories', 'wpcalendars' ) ?></option>
                <?php foreach ( $categories as $category ): ?>
                <option value="<?php echo $category['category_id'] ?>"<?php selected( $category['category_id'], $instance['category'] ) ?>><?php echo $category['name'] ?></option>
                <?php endforeach ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'tag' ); ?>">
                <?php esc_html( _ex( 'Tag:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <select id="<?php echo $this->get_field_id( 'tag' ); ?>" name="<?php echo $this->get_field_name( 'tag' ); ?>" class="widefat">
                <option value=""><?php echo __( 'All Tags', 'wpcalendars' ) ?></option>
                <?php foreach ( $tags as $key => $name ): ?>
                <option value="<?php echo $key ?>"<?php selected( $key, $instance['tag'] ) ?>><?php echo $name ?></option>
                <?php endforeach ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'exclude_events' ); ?>">
                <?php esc_html( _ex( 'Exclude Events:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <input type="text" id="<?php echo $this->get_field_id( 'exclude_events' ); ?>" name="<?php echo $this->get_field_name( 'exclude_events' ); ?>" value="<?php echo esc_attr( $instance['exclude_events'] ); ?>" class="widefat"/>
        </p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_hidden_events' ); ?>"
					name="<?php echo $this->get_field_name( 'show_hidden_events' ); ?>" <?php checked( '1', $instance['show_hidden_events'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_hidden_events' ); ?>"><?php esc_html( _ex( 'Show Hidden Events', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
            <label for="<?php echo $this->get_field_id( 'num_events' ); ?>">
                <?php esc_html( _ex( 'Number of Events:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <input type="number" id="<?php echo $this->get_field_id( 'num_events' ); ?>" name="<?php echo $this->get_field_name( 'num_events' ); ?>" value="<?php echo esc_attr( $instance['num_events'] ); ?>" class="widefat"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'date_format' ); ?>">
                <?php esc_html( _ex( 'Date Format:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <select id="<?php echo $this->get_field_id( 'date_format' ); ?>" name="<?php echo $this->get_field_name( 'date_format' ); ?>" class="widefat">
                <?php foreach ( $date_format_options as $key => $name ): ?>
                <option value="<?php echo $key ?>"<?php selected( $key, $instance['date_format'] ) ?>><?php echo $name ?></option>
                <?php endforeach ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'time_format' ); ?>">
                <?php esc_html( _ex( 'Time Format:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <select id="<?php echo $this->get_field_id( 'time_format' ); ?>" name="<?php echo $this->get_field_name( 'time_format' ); ?>" class="widefat">
                <?php foreach ( $time_format_options as $key => $name ): ?>
                <option value="<?php echo $key ?>"<?php selected( $key, $instance['time_format'] ) ?>><?php echo $name ?></option>
                <?php endforeach ?>
            </select>
        </p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_year' ); ?>"
					name="<?php echo $this->get_field_name( 'show_year' ); ?>" <?php checked( '1', $instance['show_year'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_year' ); ?>"><?php esc_html( _ex( 'Show Year', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_day_name' ); ?>"
					name="<?php echo $this->get_field_name( 'show_day_name' ); ?>" <?php checked( '1', $instance['show_day_name'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_day_name' ); ?>"><?php esc_html( _ex( 'Show Day Name', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_featured_image' ); ?>"
					name="<?php echo $this->get_field_name( 'show_featured_image' ); ?>" <?php checked( '1', $instance['show_featured_image'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_featured_image' ); ?>"><?php esc_html( _ex( 'Show Featured Image', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"
					name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" <?php checked( '1', $instance['show_excerpt'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php esc_html( _ex( 'Show Excerpt', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_venue' ); ?>"
					name="<?php echo $this->get_field_name( 'show_venue' ); ?>" <?php checked( '1', $instance['show_venue'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_venue' ); ?>"><?php esc_html( _ex( 'Show Venue', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_more' ); ?>"
					name="<?php echo $this->get_field_name( 'show_more' ); ?>" <?php checked( '1', $instance['show_more'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_more' ); ?>"><?php esc_html( _ex( 'Show More Events Button', 'Widget', 'wpcalendars' ) ); ?></label>
		</p>
        <p>
            <label for="<?php echo $this->get_field_id( 'more_text' ); ?>">
                <?php esc_html( _ex( 'More Events Text:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <input type="text" id="<?php echo $this->get_field_id( 'more_text' ); ?>" name="<?php echo $this->get_field_name( 'more_text' ); ?>" value="<?php echo esc_attr( $instance['more_text'] ); ?>" class="widefat"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'more_page_id' ); ?>">
                <?php esc_html( _ex( 'Calendar Page:', 'Widget', 'wpcalendars' ) ); ?>
            </label>
            <?php wp_dropdown_pages( array(
                'name'             => $this->get_field_name( 'more_page_id' ),
                'class'            => 'widefat',
                'selected'         => $instance['more_page_id'],
                'show_option_none' => sprintf( '&mdash; %s &mdash;', __( 'Select', 'wpcalendars' ) ),
            ) ) ?>
        </p>
        <?php
    }
}

/**
 * Register widgets
 */
function wpcalendars_register_widgets() {
    register_widget( 'WPCalendars_Widget_Upcoming_Events' );
}

add_action( 'widgets_init', 'wpcalendars_register_widgets' );
