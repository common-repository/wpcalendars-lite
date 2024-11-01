<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class WPCalendars_Event_Categories {

    private static $_instance = NULL;
    public $action = NULL;

    /**
     * Initialize all variables, filters and actions
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
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
     * Add Settings menu page
     */
    public function admin_menu() {
        add_submenu_page( 'edit.php?post_type=wpcalendars_event', __( 'Categories', 'wpcalendars' ), __( 'Categories', 'wpcalendars' ), 'manage_options', 'wpcalendars-category', array( $this, 'admin_page' ) );
    }
    
    /**
     * Admin actions
     */
    public function admin_init() {
        $sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'category', 'add_new' ), wp_get_referer() );
        
        if ( isset( $_REQUEST['page'] ) && 'wpcalendars-category' === $_REQUEST['page'] ) {
            if ( ! empty( $_POST['add_new_category'] ) ) {
                $result = $this->process_add_category();
                
                if ( is_wp_error( $result ) ) {
                    echo '<div class="error"><p>' . wp_kses_post( $result->get_error_message() ) . '</p></div>';
                } else {
                    wp_redirect( $sendback );
                    exit;
                }
            } elseif ( ! empty( $_GET['delete'] ) ) {
                $result = $this->process_delete_category();
                wp_redirect( $sendback );
                exit;
            } elseif ( ! empty( $_POST['save_category'] ) ) {
                $result = $this->process_save_category();
                
                if ( is_wp_error( $result ) ) {
                    echo '<div class="error"><p>' . wp_kses_post( $result->get_error_message() ) . '</p></div>';
                } else {
                    wp_redirect( $sendback );
                    exit;
                }
            } elseif ( ! empty( $_GET['action'] ) && '-1' !== $_GET['action'] ) {
                if ( 'delete-selected' === $_GET['action'] ) {
                    $result = $this->process_bulk_delete_category();
                }
                wp_redirect( $sendback );
                exit;
            }
        }
    }
    
    /**
     * Process add new event category
     * @return \WP_Error
     */
    public function process_add_category() {
        $args = $_POST['new_category'];
        check_admin_referer( 'category_create' );
        
        if ( empty( $args['name'] ) || empty( $args['bgcolor'] ) ) {
            return new WP_Error( 'error_category', esc_html__( 'Please, provide a category name and background color.', 'wpcalendars' ), array( 'status' => 400 ) );
        }
        
        $args = array(
            'name'    => sanitize_text_field( $args['name'] ),
            'bgcolor' => sanitize_text_field( $args['bgcolor'] ),
        );
        
        wpcalendars_add_new_event_category( $args );
    }
    
    /**
     * Process delete event category
     * @return type
     */
    public function process_delete_category() {
        $category_id = intval( $_GET['delete'] );
        check_admin_referer( 'category_delete-' . $category_id );
        
        $default_event_category = wpcalendars_settings_value( 'general', 'default_event_category' );
        
        if ( intval( $default_event_category ) === $category_id ) {
            return;
        }
        
        wpcalendars_delete_event_category( $category_id );
    }
    
    /**
     * Process save event category
     * @return \WP_Error
     */
    public function process_save_category() {
        $args = $_POST['edit_category'];
        check_admin_referer( 'category_edit-' . $args['category_id'] );
        
        if ( empty( $args['name'] ) || empty( $args['bgcolor'] ) ) {
            return new WP_Error( 'error_category', esc_html__( 'Please, provide a category name and background color.', 'wpcalendars' ), array( 'status' => 400 ) );
        }
        
        $args = array(
            'category_id' => intval( $args['category_id'] ),
            'name'        => sanitize_text_field( $args['name'] ),
            'bgcolor'     => sanitize_text_field( $args['bgcolor'] ),
        );
        
        wpcalendars_save_event_category( $args );
    }
    
    /**
     * Process bulk delete event categories
     */
    public function process_bulk_delete_category() {
        $category_ids = $_GET['category'];
        check_admin_referer( 'category_action' );
        
        $default_event_category = wpcalendars_settings_value( 'general', 'default_event_category' );
        
        foreach ( $category_ids as $category_id ) {
            if ( intval( $default_event_category ) !== intval( $category_id ) ) {
                wpcalendars_delete_event_category( intval( $category_id ) );
            }
        }
    }
    
    /**
     * Template of admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__( 'Event Categories', 'wpcalendars' );?></h1>
            <hr class="wp-header-end">
            <?php 
            if ( ! empty( $_GET['edit'] ) ) {
                $this->edit();
            } else {
                $this->table();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render category edit form
     */
    private function edit() {
        $event_category = wpcalendars_get_event_category( intval( $_REQUEST['edit'] ) );
		?>
		<form method="post" action="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-category&edit=' . intval( $_REQUEST['edit'] ) ); ?>">
			<?php wp_nonce_field( 'category_edit-' . $event_category['category_id'] ); ?>
            <input name="edit_category[category_id]" type="hidden" value="<?php echo esc_attr( $event_category['category_id'] ); ?>" />
			<table class="form-table">
                <tr>
					<th scope="row"><label for="name"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></label></th>
					<td>
                        <input id="name" type="text" name="edit_category[name]" value="<?php echo esc_attr( $event_category['name'] ) ?>" class="regular-text">
					</td>
				</tr>
                <tr>
                    <th scope="row"><label for="bgcolor"><?php echo esc_html__( 'Background Color', 'wpcalendars' ); ?></label></th>
                    <td>
                        <input id="bgcolor" type="text" name="edit_category[bgcolor]" value="<?php echo esc_attr( $event_category['bgcolor'] ) ?>" class="color-picker">
                    </td>
				</tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" name="save_category" value="<?php echo esc_attr__( 'Save Changes', 'wpcalendars' ); ?>" /></p>
		</form>
		<?php
	}
    
    /**
     * Table list of event categories
     */
    private function table() {
        $event_categories = wpcalendars_get_event_categories();
        $default_event_category = wpcalendars_settings_value( 'general', 'default_event_category' );
		?>
        <div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					<h3><?php echo esc_html__( 'Available Categories', 'wpcalendars' ); ?></h3>
                    <form method="get">
                        <?php wp_nonce_field( 'category_action' ); ?>
                        <input type="hidden" name="post_type" value="wpcalendars_event">
                        <input type="hidden" name="page" value="wpcalendars-category">
                        <div class="tablenav top">
                            <div class="alignleft actions bulkactions">
                                <select name="action">
                                    <option value="-1"><?php echo esc_html__( 'Bulk Action', 'wpcalendars' ) ?></option>
                                    <option value="delete-selected"><?php echo esc_html__( 'Delete', 'wpcalendars' ) ?></option>
                                </select>
                                <input type="submit" class="button action" value="<?php echo esc_attr__( 'Apply', 'wpcalendars' ) ?>">
                            </div>
                        </div>
                        <table class="wp-list-table widefat plugins">
                            <thead>
                                <tr>
                                    <td class="manage-column column-cb check-column"><input id="cb-select-all" type="checkbox"></td>
                                    <th scope="col" class="manage-column column-name column-primary"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></th>
                                    <th scope="col" class="manage-column column-bgcolor"><?php echo esc_html__( 'Background Color', 'wpcalendars' ); ?></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <td class="manage-column column-cb check-column"><input id="cb-select-all" type="checkbox"></td>
                                    <th scope="col" class="manage-column column-name column-primary"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></th>
                                    <th scope="col" class="manage-column column-bgcolor"><?php echo esc_html__( 'Background Color', 'wpcalendars' ); ?></th>
                                </tr>
                            </tfoot>
                            <tbody id="the-list" class="wpcalendars-list-table">	
                                <?php foreach ( $event_categories as $event_category ): ?>
                                <tr class="inactive">
                                    <th scope="row" class="check-column">
                                        <?php if ( intval( $default_event_category ) !== intval( $event_category['category_id'] ) ): ?>
                                        <input type="checkbox" name="category[]" value="<?php echo intval( $event_category['category_id'] ) ?>">
                                        <?php endif ?>
                                    </th>
                                    <td class="plugin-title column-primary">
                                        <strong><?php echo $event_category['name'] ?></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a href="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&amp;page=wpcalendars-category&edit=' . intval( $event_category['category_id'] ) ); ?>"><?php _e( 'Edit', 'wpcalendars' ); ?></a></span>
                                            <?php if ( intval( $default_event_category ) !== intval( $event_category['category_id'] ) ): ?>
                                            <span class="delete"> | <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-category&delete=' . intval( $event_category['category_id'] ) ), 'category_delete-' . $event_category['category_id'] ); ?>"><?php _e( 'Delete', 'wpcalendars' ); ?></a></span> 
                                            <?php endif ?>
                                        </div>
                                    </td>
                                    <td class="column-bgcolor"><span class="wpcalendars-color-bar" style="background:<?php echo esc_attr( $event_category['bgcolor'] ) ?>;"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
			</div>
			<!-- /col-right -->
			<div id="col-left">
				<div class="col-wrap">
					<div class="form-wrap">
						<h3><?php echo esc_html__( 'Add Category', 'wpcalendars' ); ?></h3>
						<form method="post" action="<?php echo admin_url( 'edit.php?post_type=wpcalendars_event&page=wpcalendars-category' ) ?>">
                            <?php wp_nonce_field( 'category_create' ); ?>	
                            <div class="form-field form-required">
								<label for="name"><?php echo esc_html__( 'Name', 'wpcalendars' ); ?></label>
								<input name="new_category[name]" id="name" type="text" value="" aria-required="true" />
							</div>
                            <div class="form-field form-required">
                                <label for="bgcolor"><?php echo esc_html__( 'Background Color', 'wpcalendars' ); ?></label> 
                                <input name="new_category[bgcolor]" id="bgcolor" type="text" value="" class="color-picker" />
							</div>
							<p class="submit">
								<input type="submit" class="button button-primary" name="add_new_category" id="submit" value="<?php echo esc_attr__( 'Add Category', 'wpcalendars' ); ?>" />
							</p>
						</form>
					</div>
				</div>
			</div>
			<!-- /col-left -->
        </div>
		<?php
	}
}
WPCalendars_Event_Categories::instance();