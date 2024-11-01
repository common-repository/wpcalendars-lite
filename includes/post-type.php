<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Post_Type {

    private static $_instance = NULL;
    
    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'init',                                            array( $this, 'add_rewrite_rule' ) );
        add_action( 'init',                                            array( $this, 'register_post_type' ) );
        add_action( 'wpcalendars_after_register_post_type',            array( $this, 'flush_rewrite_rules' ) );
        add_action( 'manage_wpcalendars_event_posts_custom_column',    array( $this, 'render_event_columns' ), 10, 2 );
        add_action( 'manage_wpcalendars_venue_posts_custom_column',    array( $this, 'render_venue_columns' ), 2 );
        add_action( 'manage_wpcalendars_organizr_posts_custom_column', array( $this, 'render_organizer_columns' ), 2 );
        add_action( 'restrict_manage_posts',                           array( $this, 'restrict_manage_posts' ) );
        add_action( 'admin_head',                                      array( $this, 'remove_date_dropdown' ) );
        add_action( 'admin_print_scripts',                             array( $this, 'disable_autosave' ) );
        add_action( 'template_redirect',                               array( $this, 'disable_event_details' ) );
        add_action( 'before_delete_post',                              array( $this, 'delete_event' ) );
        
        add_filter( 'manage_wpcalendars_event_posts_columns',            array( $this, 'event_columns' ) );
        add_filter( 'manage_wpcalendars_venue_posts_columns',            array( $this, 'venue_columns' ) );
        add_filter( 'manage_wpcalendars_organizr_posts_columns',         array( $this, 'organizer_columns' ) );
        add_filter( 'manage_edit-wpcalendars_event_sortable_columns',    array( $this, 'event_sortable_columns' ) );
        add_filter( 'manage_edit-wpcalendars_venue_sortable_columns',    array( $this, 'venue_sortable_columns' ) );
        add_filter( 'manage_edit-wpcalendars_organizr_sortable_columns', array( $this, 'organizer_sortable_columns' ) );
        add_filter( 'bulk_actions-edit-wpcalendars_event',               array( $this, 'event_bulk_actions' ) );
        add_filter( 'bulk_actions-edit-wpcalendars_venue',               array( $this, 'venue_bulk_actions' ) );
        add_filter( 'bulk_actions-edit-wpcalendars_organizr',            array( $this, 'organizer_bulk_actions' ) );
        add_filter( 'post_updated_messages',                             array( $this, 'post_updated_messages' ) );
        add_filter( 'bulk_post_updated_messages',                        array( $this, 'bulk_post_updated_messages' ), 10, 2 );
        add_filter( 'post_row_actions',                                  array( $this, 'row_actions' ), 100, 2 );
        add_filter( 'enter_title_here',                                  array( $this, 'enter_title_here' ), 1, 2 );
        add_filter( 'posts_clauses',                                     array( $this, 'posts_clauses' ), 10, 2 );
        add_filter( 'query_vars',                                        array( $this, 'add_query_vars' ) );
        add_filter( 'wpseo_posts_where',                                 array( $this, 'wpseo_posts_where' ), 10, 2 );
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
     * Register custom post types and taxonomy
     */
    public function register_post_type() {
        $permalinks = (array) get_option( 'wpcalendars_permalinks', array() );
        $event_base = isset( $permalinks['event_base'] ) && '' !== $permalinks['event_base'] ? $permalinks['event_base'] : 'event';
            
        if ( 'yes' === get_option( 'wpcalendars_queue_flush_rewrite_rules', 'yes' ) ) {
            update_option( 'wpcalendars_queue_flush_rewrite_rules', 'yes' );
        }
        
        register_post_type( 'wpcalendars', apply_filters( 'wpcalendars_register_post_type_calendar', array(
            'public'              => false,
            'show_ui'             => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'hierarchical'        => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'query_var'           => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false
        ) ) );
        
        register_taxonomy( 'wpcalendars_event_tag', 'wpcalendars_event', apply_filters( 'wpcalendars_register_taxonomy_event_tag', array(
            'labels' => array(
                'name'              => __( 'Event Tags', 'wpcalendars' ),
                'singular_name'     => __( 'Event Tag', 'wpcalendars' ),
                'menu_name'         => _x( 'Tags', 'Admin menu name', 'wpcalendars' ),
                'search_items'      => __( 'Search Tag', 'wpcalendars' ),
                'popular_items'     => __( 'Popular Tag', 'wpcalendars' ),
                'all_items'         => __( 'All Tags', 'wpcalendars' ),
                'parent_item'       => __( 'Parent Tag', 'wpcalendars' ),
                'parent_item_colon' => __( 'Parent Tag:', 'wpcalendars' ),
                'edit_item'         => __( 'Edit Tag', 'wpcalendars' ),
                'view_item'         => __( 'View Tag', 'wpcalendars' ),
                'update_item'       => __( 'Update Tag', 'wpcalendars' ),
                'add_new_item'      => __( 'Add New Tag', 'wpcalendars' ),
                'new_item_name'     => __( 'New Tag', 'wpcalendars' ),
                'not_found'         => __( 'No tag found.', 'wpcalendars' ),
                'no_terms'          => __( 'No tag', 'wpcalendars' )
            ),
            'public'       => false,
            'hierarchical' => false,
            'show_ui'      => true,
        ) ) );
        
        register_post_type( 'wpcalendars_event', apply_filters( 'wpcalendars_register_post_type_event', array(
            'labels' => array(
                'name'                  => __( 'Events', 'wpcalendars' ),
                'singular_name'         => __( 'Event', 'wpcalendars' ),
                'menu_name'             => _x( 'WPCalendars', 'Admin menu name', 'wpcalendars' ),
                'add_new'               => __( 'Add New Event', 'wpcalendars' ),
                'add_new_item'          => __( 'Add New Event', 'wpcalendars' ),
                'edit'                  => __( 'Edit', 'wpcalendars' ),
                'edit_item'             => __( 'Edit Event', 'wpcalendars' ),
                'new_item'              => __( 'New Event', 'wpcalendars' ),
                'all_items'             => __( 'All Events', 'wpcalendars' ),
                'view'                  => __( 'View Event', 'wpcalendars' ),
                'view_item'             => __( 'View Event', 'wpcalendars' ),
                'search_items'          => __( 'Search Event', 'wpcalendars' ),
                'not_found'             => __( 'No event found', 'wpcalendars' ),
                'not_found_in_trash'    => __( 'No event found in trash', 'wpcalendars' ),
                'parent'                => __( 'Parent Event', 'wpcalendars' ),
                'featured_image'        => __( 'Featured Image', 'wpcalendars' ),
                'set_featured_image'    => __( 'Set Featured Image', 'wpcalendars' ),
                'remove_featured_image' => __( 'Remove Image', 'wpcalendars' ),
                'use_featured_image'    => __( 'Use as Featured Image', 'wpcalendars' ),
                'insert_into_item'      => __( 'Insert into Event', 'wpcalendars' ),
                'uploaded_to_this_item' => __( 'Uploaded to this event', 'wpcalendars' ),
                'filter_items_list'     => __( 'Filter event', 'wpcalendars' ),
                'items_list_navigation' => __( 'Event navigation', 'wpcalendars' ),
                'items_list'            => __( 'Event list', 'wpcalendars' ),
            ),
            'description'         => __( 'This is where you can add new event that you can use in your WordPress site.', 'wpcalendars' ),
            'public'              => true,
            'show_ui'             => true,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'publicly_queryable'  => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-calendar-alt',
            'menu_position'       => 35,
            'hierarchical'        => false,
            'rewrite'             => array( 'slug' => $event_base, 'with_front' => false ),
            'has_archive'         => false,
            'query_var'           => 'event',
            'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author' ),
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true
        ) ) );
        
        register_post_type( 'wpcalendars_evcat', apply_filters( 'wpcalendars_register_post_type_event_cat', array(
            'public'              => false,
            'show_ui'             => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'hierarchical'        => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'query_var'           => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false
        ) ) );
        
        register_post_type( 'wpcalendars_venue', apply_filters( 'wpcalendars_register_post_type_venue', array(
            'labels' => array(
                'name'                  => __( 'Venues', 'wpcalendars' ),
                'singular_name'         => __( 'Venue', 'wpcalendars' ),
                'menu_name'             => _x( 'Venues', 'Admin menu name', 'wpcalendars' ),
                'add_new'               => __( 'Add New Venue', 'wpcalendars' ),
                'add_new_item'          => __( 'Add New Venue', 'wpcalendars' ),
                'edit'                  => __( 'Edit', 'wpcalendars' ),
                'edit_item'             => __( 'Edit Venue', 'wpcalendars' ),
                'new_item'              => __( 'New Venue', 'wpcalendars' ),
                'all_items'             => __( 'Venues', 'wpcalendars' ),
                'view'                  => __( 'View Venue', 'wpcalendars' ),
                'view_item'             => __( 'View Venue', 'wpcalendars' ),
                'search_items'          => __( 'Search Venue', 'wpcalendars' ),
                'not_found'             => __( 'No venue found', 'wpcalendars' ),
                'not_found_in_trash'    => __( 'No venue found in trash', 'wpcalendars' ),
                'parent'                => __( 'Parent Venue', 'wpcalendars' ),
                'featured_image'        => __( 'Featured Image', 'wpcalendars' ),
                'set_featured_image'    => __( 'Set Featured Image', 'wpcalendars' ),
                'remove_featured_image' => __( 'Remove Image', 'wpcalendars' ),
                'use_featured_image'    => __( 'Use as Featured Image', 'wpcalendars' ),
                'insert_into_item'      => __( 'Insert into Venue', 'wpcalendars' ),
                'uploaded_to_this_item' => __( 'Uploaded to this venue', 'wpcalendars' ),
                'filter_items_list'     => __( 'Filter venue', 'wpcalendars' ),
                'items_list_navigation' => __( 'Venue navigation', 'wpcalendars' ),
                'items_list'            => __( 'Venue list', 'wpcalendars' ),
            ),
            'description'         => __( 'This is where you can add new venue that you can use in your WordPress site.', 'wpcalendars' ),
            'public'              => false,
            'show_ui'             => true,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_in_menu'        => 'edit.php?post_type=wpcalendars_event',
            'hierarchical'        => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'query_var'           => false,
            'supports'            => array( 'title', 'editor' ),
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false
        ) ) );
        
        register_post_type( 'wpcalendars_organizr', apply_filters( 'wpcalendars_register_post_type_organizer', array(
            'labels' => array(
                'name'                  => __( 'Organizers', 'wpcalendars' ),
                'singular_name'         => __( 'Organizer', 'wpcalendars' ),
                'menu_name'             => _x( 'Organizers', 'Admin menu name', 'wpcalendars' ),
                'add_new'               => __( 'Add New Organizer', 'wpcalendars' ),
                'add_new_item'          => __( 'Add New Organizer', 'wpcalendars' ),
                'edit'                  => __( 'Edit', 'wpcalendars' ),
                'edit_item'             => __( 'Edit Organizer', 'wpcalendars' ),
                'new_item'              => __( 'New Organizer', 'wpcalendars' ),
                'all_items'             => __( 'Organizers', 'wpcalendars' ),
                'view'                  => __( 'View Organizer', 'wpcalendars' ),
                'view_item'             => __( 'View Organizer', 'wpcalendars' ),
                'search_items'          => __( 'Search Organizer', 'wpcalendars' ),
                'not_found'             => __( 'No organizer found', 'wpcalendars' ),
                'not_found_in_trash'    => __( 'No organizer found in trash', 'wpcalendars' ),
                'parent'                => __( 'Parent Organizer', 'wpcalendars' ),
                'featured_image'        => __( 'Featured Image', 'wpcalendars' ),
                'set_featured_image'    => __( 'Set Featured Image', 'wpcalendars' ),
                'remove_featured_image' => __( 'Remove Image', 'wpcalendars' ),
                'use_featured_image'    => __( 'Use as Featured Image', 'wpcalendars' ),
                'insert_into_item'      => __( 'Insert into Organizer', 'wpcalendars' ),
                'uploaded_to_this_item' => __( 'Uploaded to this organizer', 'wpcalendars' ),
                'filter_items_list'     => __( 'Filter organizer', 'wpcalendars' ),
                'items_list_navigation' => __( 'Organizer navigation', 'wpcalendars' ),
                'items_list'            => __( 'Organizer list', 'wpcalendars' ),
            ),
            'description'         => __( 'This is where you can add new organizer that you can use in your WordPress site.', 'wpcalendars' ),
            'public'              => false,
            'show_ui'             => true,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_in_menu'        => 'edit.php?post_type=wpcalendars_event',
            'hierarchical'        => false,
            'rewrite'             => false,
            'has_archive'         => false,
            'query_var'           => false,
            'supports'            => array( 'title', 'editor' ),
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false
        ) ) );
        
        do_action( 'wpcalendars_after_register_post_type' );
    }
    
    /**
     * Disable inline editing
     * @param type $actions
     * @param type $post
     * @return type
     */
    public function row_actions( $actions, $post ) {
        if ( in_array( $post->post_type, array( 'wpcalendars_event', 'wpcalendars_venue', 'wpcalendars_organizr' ) ) ) {
            if ( isset( $actions['inline hide-if-no-js'] ) ) {
                unset( $actions['inline hide-if-no-js'] );
            }
        }

        return $actions;
    }
    
    /**
     * Change "enter title here" text
     * @param type $text
     * @param type $post
     * @return type
     */
    public function enter_title_here( $text, $post ) {
        switch ( $post->post_type ) {
            case 'wpcalendars_event' :
                $text = __( 'Enter event title here', 'wpcalendars' );
                break;
            
            case 'wpcalendars_venue':
                $text = __( 'Enter venue name here', 'wpcalendars' );
                break;
            
            case 'wpcalendars_organizer':
                $text = __( 'Enter organizer name here', 'wpcalendars' );
                break;
        }

        return $text;
    }
    
    /**
     * Add upcoming event columns
     * @param type $existing_columns
     * @return type
     */
    public function event_columns( $existing_columns ) {
        $columns = array();
        
        $columns['cb']         = $existing_columns['cb'];
        $columns['name']       = __( 'Name', 'wpcalendars' );
        $columns['event_date'] = __( 'Date', 'wpcalendars' );
        $columns['event_time'] = __( 'Time', 'wpcalendars' );
        $columns['category']   = __( 'Category', 'wpcalendars' );
        $columns['venue']      = __( 'Venue', 'wpcalendars' );
        $columns['organizer']  = __( 'Organizer', 'wpcalendars' );

        return $columns;
    }
    
    /**
     * Add venue columns
     * @param type $existing_columns
     * @return type
     */
    public function venue_columns( $existing_columns ) {
        $columns = array();
        
        $columns['cb']       = $existing_columns['cb'];
        $columns['name']     = __( 'Name', 'wpcalendars' );
        $columns['location'] = __( 'Location', 'wpcalendars' );
        $columns['phone']    = __( 'Phone', 'wpcalendars' );
        $columns['email']    = __( 'Email', 'wpcalendars' );

        return $columns;
    }
    
    /**
     * Add organizer columns
     * @param array $existing_columns
     * @return array
     */
    public function organizer_columns( $existing_columns ) {
        $columns = array();
        
        $columns['cb']      = $existing_columns['cb'];
        $columns['name']    = __( 'Name', 'wpcalendars' );
        $columns['phone']   = __( 'Phone', 'wpcalendars' );
        $columns['email']   = __( 'Email', 'wpcalendars' );
        $columns['website'] = __( 'Website', 'wpcalendars' );

        return $columns;
    }
    
    /**
     * Add event sortable columns
     * @param type $columns
     * @return type
     */
    public function event_sortable_columns( $columns ) {
        $custom = array(
			'name' => 'name',
		);
        
		return wp_parse_args( $custom, $columns );
    }
    
    /**
     * Add venue sortable columns
     * @param type $columns
     * @return type
     */
    public function venue_sortable_columns( $columns ) {
        $custom = array(
			'name' => 'name',
		);
        
		return wp_parse_args( $custom, $columns );
    }
    
    /**
     * Add organizer sortable columns
     * @param array $columns
     * @return array
     */
    public function organizer_sortable_columns( $columns ) {
        $custom = array(
			'name' => 'name',
		);
        
		return wp_parse_args( $custom, $columns );
    }
    
    /**
     * Render upcoming event column content
     * @global type $post
     * @param type $column
     */
    public function render_event_columns( $column ) {
        global $post;
        
        $start_date   = $post->start_date;
        $end_date     = $post->end_date;
        $start_time   = $post->start_time;
        $end_time     = $post->end_time;
        $all_day      = $post->all_day;
        $category_id  = $post->category_id;
        $venue_id     = $post->venue_id;
        $organizer_id = $post->organizer_id;

        switch ( $column ) {
            case 'name':
                $edit_link = get_edit_post_link( $post->ID );
                $title = _draft_or_post_title();
                echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
                _post_states( $post );
                echo '</strong>';
                break;
            case 'event_date':
                if ( '0000-00-00' === $start_date ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    $event_date = date( 'Y/m/d', strtotime( $start_date ) );
                    
                    if ( '0000-00-00' === $end_date || $start_date === $end_date ) {}
                    else {
                        $event_date .= ' - ' . date( 'Y/m/d', strtotime( $end_date ) );
                    }
                    
                    echo apply_filters( 'wpcalendars_event_date_column', $event_date, $post );
                }
                break;
            case 'event_time':
                if ( 'Y' === $all_day ) {
                    echo __( 'All Day', 'wpcalendars' );
                } else {
                    printf( '%s - %s', date( 'H:i', strtotime( $start_time ) ), date( 'H:i', strtotime( $end_time ) ) );
                }
                break;
            case 'category':
                $category = wpcalendars_get_event_category( $category_id );
                echo $category['name'];
                break;
            case 'venue':
                if ( empty( $venue_id ) ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    $venue = wpcalendars_get_venue( $venue_id );
                    echo $venue['name'];
                }
                break;
            case 'organizer':
                if ( empty( $organizer_id ) ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    $organizer = wpcalendars_get_organizer( $organizer_id );
                    echo $organizer['name'];
                }
                break;
        }
    }
    
    /**
     * Render venue columns
     * @global type $post
     * @global type $states
     * @param type $column
     */
    public function render_venue_columns( $column ) {
        global $post, $states;
        
        $countries = include WPCALENDARS_PLUGIN_DIR . 'includes/countries.php';

        switch ( $column ) {
            case 'name':
                $edit_link = get_edit_post_link( $post->ID );
                $title = _draft_or_post_title();
                echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
                _post_states( $post );
                echo '</strong>';
                break;
            case 'location':
                $address     = get_post_meta( $post->ID, '_address', true );
                $city        = get_post_meta( $post->ID, '_city', true );
                $country     = get_post_meta( $post->ID, '_country', true );
                $state       = get_post_meta( $post->ID, '_state', true );
                $postal_code = get_post_meta( $post->ID, '_postal_code', true );
                
                if ( '' === $address && '' === $city && '' === $country && '' === $state && '' === $postal_code ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    $location = array();
                    
                    if ( '' !== $address ) {
                        $location[] = $address;
                    }
                    
                    if ( '' !== $city ) {
                        $location[] = $city;
                    }
                    
                    if ( '' !== $state ) {
                        if ( file_exists( WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php' ) ) {
                            include WPCALENDARS_PLUGIN_DIR . 'includes/states/' . $country . '.php';
                            $location[] = $states[$country][$state];
                        } else {
                            $location[] = $state;
                        }
                    }
                    
                    if ( '' !== $country ) {
                        $location[] = $countries[$country];
                    }
                    
                    if ( '' !== $postal_code ) {
                        $location[] = $postal_code;
                    }
                    
                    echo implode( ', ', $location );
                }
                
                break;
            case 'phone':
                $phone = get_post_meta( $post->ID, '_phone', true );
                
                if ( '' === $phone ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    echo $phone;
                }
                
                break;
            case 'email':
                $email = get_post_meta( $post->ID, '_email', true );
                
                if ( '' === $email ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    echo $email;
                }
                
                break;
        }
    }
    
    /**
     * Render organizer columns
     * @global object $post
     * @param string $column
     */
    public function render_organizer_columns( $column ) {
        global $post;
        
        switch ( $column ) {
            case 'name':
                $edit_link = get_edit_post_link( $post->ID );
                $title = _draft_or_post_title();
                echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
                _post_states( $post );
                echo '</strong>';
                break;
            case 'website':
                $address = get_post_meta( $post->ID, '_website', true );
                
                if ( '' === $address ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    echo $address;
                }
                
                break;
            case 'phone':
                $phone = get_post_meta( $post->ID, '_phone', true );
                
                if ( '' === $phone ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    echo $phone;
                }
                
                break;
            case 'email':
                $email = get_post_meta( $post->ID, '_email', true );
                
                if ( '' === $email ) {
                    echo '<span class="na">&mdash;</span>';
                } else {
                    echo $email;
                }
                
                break;
        }
    }
        
    /**
     * Remove months dropdown
     * @global type $typenow
     */
    public function remove_date_dropdown() {
        global $typenow;

        if ( in_array( $typenow, array( 'wpcalendars_event', 'wpcalendars_venue', 'wpcalendars_organizr' ) ) ) {
            add_filter( 'months_dropdown_results', '__return_empty_array' );
        }
    }
    
    /**
     * Disable events bulk actions
     * @param type $actions
     * @return type
     */
    public function event_bulk_actions( $actions ) {
        if ( isset( $actions['edit'] ) ) {
            unset( $actions['edit'] );
        }
        
        return $actions;
    }
    
    /**
     * Disable venue bulk actions
     * @param type $actions
     * @return type
     */
    public function venue_bulk_actions( $actions ) {
        if ( isset( $actions['edit'] ) ) {
            unset( $actions['edit'] );
        }
        
        return $actions;
    }
    
    /**
     * Disable organizer bulk actions
     * @param array $actions
     * @return array
     */
    public function organizer_bulk_actions( $actions ) {
        if ( isset( $actions['edit'] ) ) {
            unset( $actions['edit'] );
        }
        
        return $actions;
    }
    
    /**
     * Change update message for upcoming event
     * @global type $post
     * @global type $post_ID
     * @param type $messages
     * @return string
     */
    public function post_updated_messages( $messages ) {
        global $post, $post_ID;

        $messages['wpcalendars_event'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Event updated.', 'wpcalendars' ),
            2 => __( 'Custom field updated.', 'wpcalendars' ),
            3 => __( 'Custom field deleted.', 'wpcalendars' ),
            4 => __( 'Event updated.', 'wpcalendars' ),
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s', 'wpcalendars' ), wp_post_revision_title( ( int ) $_GET['revision'], false ) ) : false,
            6 => __( 'Event published.', 'wpcalendars' ),
            7 => __( 'Event saved.', 'wpcalendars' ),
            8 => __( 'Event submitted.', 'wpcalendars' ),
            9 => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>.', 'wpcalendars' ), date_i18n( __( 'M j, Y @ G:i', 'wpcalendars' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Event draft updated.', 'wpcalendars' )
        );
        
        $messages['wpcalendars_venue'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Venue updated.', 'wpcalendars' ),
            2 => __( 'Custom field updated.', 'wpcalendars' ),
            3 => __( 'Custom field deleted.', 'wpcalendars' ),
            4 => __( 'Venue updated.', 'wpcalendars' ),
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Venue restored to revision from %s', 'wpcalendars' ), wp_post_revision_title( ( int ) $_GET['revision'], false ) ) : false,
            6 => __( 'Venue published.', 'wpcalendars' ),
            7 => __( 'Venue saved.', 'wpcalendars' ),
            8 => __( 'Venue submitted.', 'wpcalendars' ),
            9 => sprintf( __( 'Venue scheduled for: <strong>%1$s</strong>.', 'wpcalendars' ), date_i18n( __( 'M j, Y @ G:i', 'wpcalendars' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Venue draft updated.', 'wpcalendars' )
        );
        
        $messages['wpcalendars_organizr'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Organizer updated.', 'wpcalendars' ),
            2 => __( 'Custom field updated.', 'wpcalendars' ),
            3 => __( 'Custom field deleted.', 'wpcalendars' ),
            4 => __( 'Organizer updated.', 'wpcalendars' ),
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Organizer restored to revision from %s', 'wpcalendars' ), wp_post_revision_title( ( int ) $_GET['revision'], false ) ) : false,
            6 => __( 'Organizer published.', 'wpcalendars' ),
            7 => __( 'Organizer saved.', 'wpcalendars' ),
            8 => __( 'Organizer submitted.', 'wpcalendars' ),
            9 => sprintf( __( 'Organizer scheduled for: <strong>%1$s</strong>.', 'wpcalendars' ), date_i18n( __( 'M j, Y @ G:i', 'wpcalendars' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Organizer draft updated.', 'wpcalendars' )
        );

        return $messages;
    }
    
    /**
     * Change bulk update message for upcoming event
     * @param type $bulk_messages
     * @param type $bulk_counts
     * @return type
     */
    public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
        $bulk_messages['wpcalendars_event'] = array(
            'updated'   => _n( '%s event updated.', '%s event updated.', $bulk_counts['updated'], 'wpcalendars' ),
            'locked'    => _n( '%s event not updated, somebody is editing it.', '%s event not updated, somebody is editing them.', $bulk_counts['locked'], 'wpcalendars' ),
            'deleted'   => _n( '%s event permanently deleted.', '%s event permanently deleted.', $bulk_counts['deleted'], 'wpcalendars' ),
            'trashed'   => _n( '%s event moved to the Trash.', '%s event moved to the Trash.', $bulk_counts['trashed'], 'wpcalendars' ),
            'untrashed' => _n( '%s event restored from the Trash.', '%s event restored from the Trash.', $bulk_counts['untrashed'], 'wpcalendars' ),
        );
        
        $bulk_messages['wpcalendars_venue'] = array(
            'updated'   => _n( '%s venue updated.', '%s venue updated.', $bulk_counts['updated'], 'wpcalendars' ),
            'locked'    => _n( '%s venue not updated, somebody is editing it.', '%s venue not updated, somebody is editing them.', $bulk_counts['locked'], 'wpcalendars' ),
            'deleted'   => _n( '%s venue permanently deleted.', '%s venue permanently deleted.', $bulk_counts['deleted'], 'wpcalendars' ),
            'trashed'   => _n( '%s venue moved to the Trash.', '%s venue moved to the Trash.', $bulk_counts['trashed'], 'wpcalendars' ),
            'untrashed' => _n( '%s venue restored from the Trash.', '%s venue restored from the Trash.', $bulk_counts['untrashed'], 'wpcalendars' ),
        );
        
        $bulk_messages['wpcalendars_organizr'] = array(
            'updated'   => _n( '%s organizer updated.', '%s organizer updated.', $bulk_counts['updated'], 'wpcalendars' ),
            'locked'    => _n( '%s organizer not updated, somebody is editing it.', '%s organizer not updated, somebody is editing them.', $bulk_counts['locked'], 'wpcalendars' ),
            'deleted'   => _n( '%s organizer permanently deleted.', '%s organizer permanently deleted.', $bulk_counts['deleted'], 'wpcalendars' ),
            'trashed'   => _n( '%s organizer moved to the Trash.', '%s organizer moved to the Trash.', $bulk_counts['trashed'], 'wpcalendars' ),
            'untrashed' => _n( '%s organizer restored from the Trash.', '%s organizer restored from the Trash.', $bulk_counts['untrashed'], 'wpcalendars' ),
        );

        return $bulk_messages;
    }    
    
    /**
     * Join 'wpcalendars_events' table with 'posts' table
     * @global object $wpdb
     * @global object $typenow
     * @param array $clauses
     * @param array $query
     * @return string
     */
    public function posts_clauses( $clauses, $query ) {
        global $wpdb, $typenow, $pagenow;

        if ( 'wpcalendars_event' === $query->get('post_type') ) {
            $table_name = $wpdb->prefix . 'wpcalendars_events';
            
            $clauses['join']   .= " LEFT JOIN {$table_name} b ON {$wpdb->posts}.ID = b.event_id ";
            $clauses['fields'] .= ", b.event_id, b.event_type, {$wpdb->posts}.post_title as event_title, {$wpdb->posts}.post_name as event_name ";
            $clauses['fields'] .= ", b.start_date, b.end_date, b.start_time, b.end_time, b.all_day ";
            $clauses['fields'] .= ", b.category_id, b.venue_id, b.organizer_id, b.disable_event_details, b.website ";
            $clauses['orderby'] = "b.start_date desc, b.start_time desc";
            
            if ( ! empty( get_query_var( 'event_date' ) ) ) {
                $start_date = sanitize_text_field( get_query_var( 'event_date' ) );
                $clauses['where'] .= " AND b.start_date = '{$start_date}' ";
            }
            
            if ( $pagenow == 'edit.php' || $pagenow == 'post.php' ) {
                $clauses['where'] .= " AND b.event_parent = 0 ";
            }
            
            if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] ) {
                $clauses['where'] .= " AND b.event_parent = 0 ";
            }
            
            if ( $pagenow == 'edit.php' ) {
                $where = '';
                
                if ( isset( $_GET['wpcalendars_category'] ) ) {
                    $category_id = intval( $_GET['wpcalendars_category'] );

                    if ( ! empty( $category_id ) ) {
                        $where .= " AND b.category_id = $category_id ";
                    }
                }
                
                if ( isset( $_GET['wpcalendars_venue'] ) ) {
                    $venue_id = intval( $_GET['wpcalendars_venue'] );

                    if ( ! empty( $venue_id ) ) {
                        $where .= " AND b.venue_id = $venue_id ";
                    }
                }
                
                if ( isset( $_GET['wpcalendars_organizer'] ) ) {
                    $organizer_id = intval( $_GET['wpcalendars_organizer'] );

                    if ( ! empty( $organizer_id ) ) {
                        $where .= " AND b.organizer_id = $organizer_id ";
                    }
                }
                
                if ( $where !== '' ) {
                    $clauses['where'] .= $where;
                }
            }
          
        }
        
        return $clauses;
    }
    
    /**
     * Disable autosave on upcoming event
     * @global type $post
     */
    public function disable_autosave() {
        global $post;

        if ( $post && in_array( get_post_type( $post->ID ), array( 'wpcalendars_event' ) ) ) {
            wp_dequeue_script( 'autosave' );
        }
    }
    
    /**
     * Disable event details
     * @global object $post
     * @return type
     */
    public function disable_event_details() {
        global $post;

        if ( !is_singular( 'wpcalendars_event' ) ) {
            return;
        }
        
        $event = wpcalendars_get_event( $post->ID );

        if ( 'Y' === $event['disable_event_details'] ) {
            $redirect_url = apply_filters( 'wpcalendars_disable_event_details_redirect_url', site_url() );

            if ( isset( $_REQUEST['HTTP_REFERER'] ) ) {
                $referer = esc_url( $_REQUEST['HTTP_REFERER '] );

                if ( strpos( $referer, $redirect_url ) !== false ) {
                    $redirect_url = $referer;
                }
            }

            wp_redirect( $redirect_url, 301 );
            exit;
        }
    }
    
    /**
     * Modify Yoast SEO posts where
     * @global object $wpdb
     * @param type $post_where
     * @param type $post_type
     * @return type
     */
    public function wpseo_posts_where( $post_where, $post_type ) {
        global $wpdb;
        
        if ( 'wpcalendars_event' === $post_type ) {
            $hidden_events = wpcalendars_get_hidden_events();
            
            if ( $hidden_events === array() ) {
                return $post_where;
            }
            
            $hidden_events = implode( ',', $hidden_events );
            $post_where = " AND {$wpdb->posts}.ID NOT IN ({$hidden_events})";
        }
        
        return $post_where;
    }
    
    /**
     * Add custom rewrite rule for events
     */
    public function add_rewrite_rule() {
        $permalinks = (array) get_option( 'wpcalendars_permalinks', array() );
        $event_base = isset( $permalinks['event_base'] ) && '' !== $permalinks['event_base'] ? $permalinks['event_base'] : 'event';
        
        add_rewrite_rule( '^' . $event_base . '/([^/]*)/([0-9]{4}-[0-9]{2}-[0-9]{2})/?', 'index.php?event=$matches[1]&event_date=$matches[2]', 'top' );
    }
    
    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        if ( 'yes' === get_option( 'wpcalendars_queue_flush_rewrite_rules' ) ) {
            update_option( 'wpcalendars_queue_flush_rewrite_rules', 'no' );
            flush_rewrite_rules();
        }
    }
    
    /**
     * Add event_date into query vars
     * @param array $vars
     * @return string
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'event_date';
        
        return $vars;
    }
    
    /**
     * Add some filter on events administration
     * @global type $typenow
     */
    public function restrict_manage_posts() {
        global $typenow;

        if ( 'wpcalendars_event' == $typenow ) {
            $categories = wpcalendars_get_event_categories();
            $venues     = wpcalendars_get_venues();
            $organizers = wpcalendars_get_organizers();
            
            $current_category_id  = isset( $_GET['wpcalendars_category'] ) ? intval( $_GET['wpcalendars_category'] ) : false;
            $current_venue_id     = isset( $_GET['wpcalendars_venue'] ) ? intval( $_GET['wpcalendars_venue'] ) : false;
            $current_organizer_id = isset( $_GET['wpcalendars_organizer'] ) ? intval( $_GET['wpcalendars_organizer'] ) : false;
            ?>
            <select name="wpcalendars_category">
                <option value=""><?php echo __( 'All Categories', 'wpcalendars' ) ?></option>
                <?php foreach ( $categories as $category ): ?>
                <option <?php selected( $current_category_id, $category['category_id'] ) ?> value="<?php echo $category['category_id'] ?>"><?php echo esc_html( $category['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="wpcalendars_venue">
                <option value=""><?php echo __( 'All Venues', 'wpcalendars' ) ?></option>
                <?php foreach ( $venues as $venue ): ?>
                <option <?php selected( $current_venue_id, $venue['venue_id'] ) ?> value="<?php echo $venue['venue_id'] ?>"><?php echo esc_html( $venue['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="wpcalendars_organizer">
                <option value=""><?php echo __( 'All Organizers', 'wpcalendars' ) ?></option>
                <?php foreach ( $organizers as $organizer ): ?>
                <option <?php selected( $current_organizer_id, $organizer['organizer_id'] ) ?> value="<?php echo $organizer['organizer_id'] ?>"><?php echo esc_html( $organizer['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
    
    /**
     * Execute some actions on delete events
     * @global type $post_type
     * @param type $event_id
     * @return type
     */
    public function delete_event( $event_id ) {
        global $post_type;
        
        if ( 'wpcalendars_event' !== $post_type ) {
            return;
        }
        
        wpcalendars_delete_event( $event_id );
        
        do_action( 'wpcalendars_delete_event', $event_id );
    }
}

WPCalendars_Post_Type::instance();