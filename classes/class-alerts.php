<?php
/**
 * Alerts feature class.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class Alerts
 *
 * @package WP_Stream
 */
class Alerts {
	/**
	 * Alerts post type slug
	 */
	const POST_TYPE = 'wp_stream_alerts';

	/**
	 * Triggered Alerts meta key for Records
	 */
	const ALERTS_TRIGGERED_META_KEY = 'wp_stream_alerts_triggered';

	/**
	 * Nonce name.
	 */
	const NONCE_NAME = 'wp-stream-alerts-nonce';

	/**
	 * Hold Plugin class
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Post meta prefix
	 *
	 * @var string
	 */
	public $meta_prefix = 'wp_stream';

	/**
	 * Alert Types
	 *
	 * @var array
	 */
	public $alert_types = array();

	/**
	 * Alert Triggers
	 *
	 * @var array
	 */
	public $alert_triggers = array();

	/**
	 * Class constructor.
	 *
	 * @param Plugin $plugin The main Plugin class.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// Register custom post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Add custom post type to menu.
		add_action( 'wp_stream_admin_menu', array( $this, 'register_menu' ) );

		// Add metaboxes to post screens.
		add_action( 'load-post.php', array( $this, 'register_meta_boxes' ) );
		add_action( 'load-post-new.php', array( $this, 'register_meta_boxes' ) );

		// Add scripts to post screens.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

		add_action( 'network_admin_menu', array( $this, 'change_menu_link_url' ), 99 );

		add_filter( 'wp_stream_record_inserted', array( $this, 'check_records' ), 10, 2 );

		add_filter( 'post_updated_messages', array( $this, 'filter_update_messages' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'save_post_info' ), 10, 2 );

		add_action( 'wp_ajax_load_alerts_settings', array( $this, 'load_alerts_settings' ) );
		add_action( 'wp_ajax_update_actions', array( $this, 'update_actions' ) );

		$this->load_alert_types();
		$this->load_alert_triggers();
	}

	/**
	 * Load alert_type classes
	 *
	 * @return void
	 */
	function load_alert_types() {
		$alert_types = array(
			'none',
			'highlight',
			'email',
			'iftt',
		);

		$classes = array();
		foreach ( $alert_types as $alert_type ) {

			// @todo check if file exists.
			include_once $this->plugin->locations['dir'] . '/alerts/class-alert-type-' . $alert_type . '.php';
			$class_name = sprintf( '\WP_Stream\Alert_Type_%s', str_replace( '-', '_', $alert_type ) );
			if ( ! class_exists( $class_name ) ) {
				continue;
			}
			$class = new $class_name( $this->plugin );
			if ( ! property_exists( $class, 'slug' ) ) {
				continue;
			}
			$classes[ $class->slug ] = $class;
		}

		/**
		 * Allows for adding additional alert_types via classes that extend Notifier.
		 *
		 * @param array $classes An array of Notifier objects. In the format alert_type_slug => Notifier_Class()
		 */
		$this->alert_types = apply_filters( 'wp_stream_alert_types', $classes );

		// Ensure that all alert_types extend Notifier.
		foreach ( $this->alert_types as $key => $alert_type ) {
			if ( ! $this->is_valid_alert_type( $alert_type ) ) {
				unset( $this->alert_types[ $key ] );
				trigger_error(
					sprintf(
						esc_html__( 'Registered alert_type %s does not extend WP_Stream\Alert_Type.', 'stream' ),
						esc_html( get_class( $alert_type ) )
					)
				);
			}
		}
	}

	/**
	 * Load alert_type classes
	 *
	 * @return void
	 */
	function load_alert_triggers() {
		$alert_triggers = array(
			'author',
			'context',
			'action',
		);

		$classes = array();
		foreach ( $alert_triggers as $alert_trigger ) {
			// @todo check if file exists.
			include_once $this->plugin->locations['dir'] . '/alerts/class-alert-trigger-' . $alert_trigger . '.php';
			$class_name = sprintf( '\WP_Stream\Alert_Trigger_%s', str_replace( '-', '_', $alert_trigger ) );
			if ( ! class_exists( $class_name ) ) {
				continue;
			}
			$class = new $class_name( $this->plugin );
			if ( ! property_exists( $class, 'slug' ) ) {
				continue;
			}
			$classes[ $class->slug ] = $class;
		}

		/**
		 * Allows for adding additional alert_triggers via classes that extend Notifier.
		 *
		 * @param array $classes An array of Notifier objects. In the format alert_trigger_slug => Notifier_Class()
		 */
		$this->alert_triggers = apply_filters( 'wp_stream_alert_triggers', $classes );

		// Ensure that all alert_triggers extend Notifier.
		foreach ( $this->alert_triggers as $key => $alert_trigger ) {
			if ( ! $this->is_valid_alert_trigger( $alert_trigger ) ) {
				unset( $this->alert_triggers[ $key ] );
				trigger_error(
					sprintf(
						esc_html__( 'Registered alert_trigger %s does not extend WP_Stream\Alert_Trigger.', 'stream' ),
						esc_html( get_class( $alert_trigger ) )
					)
				);
			}
		}
	}

	/**
	 * Checks whether a Alert Type class is valid
	 *
	 * @param Alert_Type $alert_type The class to check.
	 * @return bool
	 */
	public function is_valid_alert_type( $alert_type ) {
		if ( ! is_a( $alert_type, 'WP_Stream\Alert_Type' ) ) {
			return false;
		}

		if ( ! method_exists( $alert_type, 'is_dependency_satisfied' ) || ! $alert_type->is_dependency_satisfied() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks whether a Alert Trigger class is valid
	 *
	 * @param Alert_Trigger $alert_trigger The class to check.
	 * @return bool
	 */
	public function is_valid_alert_trigger( $alert_trigger ) {
		if ( ! is_a( $alert_trigger, 'WP_Stream\Alert_Trigger' ) ) {
			return false;
		}

		if ( ! method_exists( $alert_trigger, 'is_dependency_satisfied' ) || ! $alert_trigger->is_dependency_satisfied() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks record being processed against active alerts.
	 *
	 * @filter wp_stream_record_inserted
	 *
	 * @param int   $record_id The record being processed.
	 * @param array $recordarr Record data.
	 * @return array
	 */
	function check_records( $record_id, $recordarr ) {
		$args = array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'wp_stream_enabled',
		);

		$alerts = new \WP_Query( $args );
		foreach ( $alerts->posts as $alert ) {
			$alert = $this->get_alert( $alert->ID );

			$status = $alert->check_record( $record_id, $recordarr );
			if ( $status ) {
				$alert->send_alert( $record_id, $recordarr ); // @todo send_alert expects int, not array.
			}
		}

		return $recordarr;

	}

	/**
	 * Register scripts for page load
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @param string $page Current file name.
	 * @return void
	 */
	function register_scripts( $page ) {
		if ( 'post.php' === $page || 'post-new.php' === $page || 'edit.php' === $page ) {
			wp_register_script( 'wp-stream-alerts', $this->plugin->locations['url'] . 'ui/js/alerts.js', array( 'wp-stream-select2', 'jquery' ) );
			wp_localize_script( 'wp-stream-alerts', 'streamAlerts',
				array(
					'any'        => __( 'Any', 'stream' ),
					'anyContext' => __( 'Any Context', 'stream' ),
					'security'   => wp_create_nonce( self::NONCE_NAME ),
					)
			);
			wp_enqueue_script( 'wp-stream-alerts' );
			wp_enqueue_style( 'wp-stream-select2' );
		}
	}

	/**
	 * Register custom post type
	 *
	 * @action init
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Alerts', 'post type general name', 'stream' ),
			'singular_name'      => _x( 'Alert', 'post type singular name', 'stream' ),
			'menu_name'          => _x( 'Alerts', 'admin menu', 'stream' ),
			'name_admin_bar'     => _x( 'Alert', 'add new on admin bar', 'stream' ),
			'add_new'            => _x( 'Add New', 'book', 'stream' ),
			'add_new_item'       => __( 'Add New Alert', 'stream' ),
			'new_item'           => __( 'New Alert', 'stream' ),
			'edit_item'          => __( 'Edit Alert', 'stream' ),
			'view_item'          => __( 'View Alert', 'stream' ),
			'all_items'          => __( 'Alerts', 'stream' ),
			'search_items'       => __( 'Search Alerts', 'stream' ),
			'parent_item_colon'  => __( 'Parent Alerts:', 'stream' ),
			'not_found'          => __( 'No alerts found.', 'stream' ),
			'not_found_in_trash' => __( 'No alerts found in Trash.', 'stream' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Alerts for Stream.', 'stream' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // @see modify_admin_menu
			'supports'            => false,
			'capabilities'        => array(
				'publish_posts'       => 'manage_options',
				'edit_others_posts'   => 'manage_options',
				'delete_posts'        => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts'  => 'manage_options',
				'edit_post'           => 'manage_options',
				'delete_post'         => 'manage_options',
				'read_post'           => 'manage_options',
			),
		);

		register_post_type( self::POST_TYPE, $args );

		$args = array(
			'label'                     => _x( 'Enabled', 'alert', 'stream' ),
			'public'                    => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Enabled <span class="count">(%s)</span>', 'Enabled <span class="count">(%s)</span>', 'stream' ),
		);

		register_post_status( 'wp_stream_enabled', $args );

		$args = array(
			'label'                     => _x( 'Disabled', 'alert', 'stream' ),
			'public'                    => false,
			'internal'                  => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'stream' ),
		);

		register_post_status( 'wp_stream_disabled', $args );
	}

	/**
	 * Changes update messages for use with Alerts.
	 *
	 * @filter post_updated_messages
	 *
	 * @param array $messages Array of post update messages by post type.
	 * @return array
	 */
	public function filter_update_messages( $messages ) {

		$updated = __( 'Alert updated.', 'stream' );
		$messages['wp_stream_alerts'] = array(
			 0 => '', // Unused.
			 1 => $updated, // Regular update.
			 2 => '', // Unused. Custom fields updated.
			 3 => '', // Unused. Custom fields deleted.
			 4 => $updated, // Regular update.
			 5 => '', // Unused. Revision restored.
			 6 => $updated, // Publish.
			 7 => $updated, // Save.
			 8 => '', // Unused. Submit for review.
			 9 => '', // Unused. Scheduled.
			10 => $updated, // Draft updated.
		);

		return $messages;
	}

	/**
	 * Return alert object of the given ID
	 *
	 * @param string $post_id Post ID for the alert.
	 * @return Alert
	 */
	public function get_alert( $post_id = '' ) {

		if ( ! $post_id ) {
			$obj = new Alert( null, $this->plugin );
			return $obj;
		}

		$post = get_post( $post_id );
		$meta = get_post_custom( $post_id );

		$obj = (object) array(
			'ID'             => $post->ID,
			'status'         => $post->post_status,
			'date'           => $post->post_date,
			'author'         => $post->post_author,
			'filter_action'  => isset( $meta['filter_action'] ) ? $meta['filter_action'][0] : null,
			'filter_author'  => isset( $meta['filter_author'] ) ? $meta['filter_author'][0] : null,
			'filter_context' => isset( $meta['filter_context'] ) ? $meta['filter_context'][0] : null,
			'alert_type'     => isset( $meta['alert_type'] ) ? $meta['alert_type'][0] : null,
			'alert_meta'     => isset( $meta['alert_meta'] ) ? (array) maybe_unserialize( $meta['alert_meta'][0] ) : array(),
		);

		return new Alert( $obj, $this->plugin );

	}

	/**
	 * Add custom post type to menu
	 *
	 * @action admin_menu
	 *
	 * @return void
	 */
	function register_menu() {

		add_submenu_page(
			$this->plugin->admin->records_page_slug,
			__( 'Alerts', 'stream' ),
			__( 'Alerts', 'stream' ),
			'manage_options',
			'edit.php?post_type=wp_stream_alerts'
		);
	}

	/**
	 * Modify the Stream > Alerts Network Admin Menu link.
	 *
	 * In self::register_menu(), the Alerts submenu item
	 * is essentially set to go to the Site's admin area.
	 *
	 * However, on the Network admin, we need to redirect
	 * it to the first site in the network, as this is
	 * where the true Network Alerts settings page is located.
	 *
	 * @action network_admin_menu
	 * @return bool
	 */
	function change_menu_link_url() {
		global $submenu;

		$parent = 'wp_stream';
		$page   = 'edit.php?post_type=wp_stream_alerts';

		// If we're not on the Stream menu item, return.
		if ( ! isset( $submenu[ $parent ] ) ) {
			return false;
		}

		// Get the first existing Site in the Network.
		$sites = wp_get_sites(
			array(
				'limit' => 5, // Limit the size of the query.
			)
		);

		$site_id = '1';

		// Function wp_get_sites() can return an empty array if the network is too large.
		if ( ! empty( $sites ) && ! empty( $sites[0]['blog_id'] ) ) {
			$site_id = $sites[0]['blog_id'];
		}

		$new_url = get_admin_url( $site_id, $page );

		foreach ( $submenu[ $parent ] as $key => $value ) {

			// Set correct URL for the menu item.
			if ( $page === $value[2] ) {
				$submenu[ $parent ][ $key ][2] = $new_url;
				break;
			}
		}

		return true;
	}

	/**
	 * Register metaboxes with post screens
	 *
	 * @action load-post-new.php
	 *
	 * @return void
	 */
	function register_meta_boxes() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'parent_file', array( $this, 'filter_parent_file' ), 10, 1 );
		add_filter( 'submenu_file', array( $this, 'filter_submenu_file' ), 10, 1 );
	}

	/**
	 * Add metaboxes to post screens
	 *
	 * @filter add_meta_boxes
	 *
	 * @return void
	 */
	function add_meta_boxes() {
		remove_meta_box( 'submitdiv',  self::POST_TYPE, 'side' );

		add_meta_box(
			'wp_stream_alerts_triggers',
			__( 'Alert Trigger', 'stream' ),
			array( $this, 'display_triggers_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'wp_stream_alerts_alert_type',
			__( 'Alert Type', 'stream' ),
			array( $this, 'display_notification_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'wp_stream_alerts_submit',
			__( 'Alert Status', 'stream' ),
			array( $this, 'display_submit_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Fixes menu highlighting when Alerts are being edited.
	 *
	 * @filter parent_file
	 *
	 * @param string $parent_file Top level menu item to highlight.
	 * @return string
	 */
	function filter_parent_file( $parent_file ) {
		$screen = get_current_screen();
		if ( 'post' === $screen->base && self::POST_TYPE === $screen->post_type ) {
			$parent_file = 'wp_stream';
		}
		return $parent_file;
	}

	/**
	 * Fixes menu highlighting when Alerts are being edited.
	 *
	 * @filter submenu_file
	 *
	 * @param string $submenu_file Submenu level menu item to highlight.
	 * @return string
	 */
	function filter_submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		if ( 'post' === $screen->base && self::POST_TYPE === $screen->post_type ) {
			$submenu_file = 'edit.php?post_type=wp_stream_alerts';
		}
		return $submenu_file;
	}

	/**
	 * Display Alert Type Meta Box
	 *
	 * @param WP_Post $post Post object for current alert.
	 * @return void
	 */
	function display_notification_box( $post ) {
		$alert = $this->get_alert( $post->ID );
		$form  = new Form_Generator;

		$field_html = $form->render_field( 'select', array(
			'id'          => 'wp_stream_alert_type',
			'name'        => 'wp_stream_alert_type',
			'value'       => $alert->alert_type,
			'options'     => $this->get_notification_values(),
			'placeholder' => __( 'No Alert', 'stream' ),
			'title'       => 'Alert Type:',
		) );

		echo '<p>' . esc_html__( 'Alert me by:', 'stream' ) . '</p>';
		echo $field_html; // Xss ok.

		echo '<div id="wp_stream_alert_type_form">';
		$alert->get_alert_type_obj()->display_fields( $alert );
		echo '</div>';
	}

	/**
	 * Returns settings form HTML for AJAX use
	 *
	 * @action wp_ajax_load_alerts_settings
	 *
	 * @return void
	 */
	function load_alerts_settings() {
		$post_id = wp_stream_filter_input( INPUT_POST, 'post_id' );
		$alert = $this->get_alert( $post_id );
		if ( ! $alert ) {
			wp_send_json_error( array(
				'message' => 'Could not find alert.',
			) );
		}

		$alert_type = wp_stream_filter_input( INPUT_POST, 'alert_type' );
		if ( ! array_key_exists( $alert_type, $this->alert_types ) ) {
			wp_send_json_error( array(
				'message' => 'Could not find alert type.',
			) );
		}

		ob_start();
		$this->alert_types[ $alert_type ]->display_fields( $alert );
		$output = ob_get_contents();
		ob_end_clean();

		$data = array( 'html' => $output );
		wp_send_json_success( $data );
	}

	/**
	 * Display Trigger Meta Box
	 *
	 * @param WP_Post $post Post object for current alert.
	 * @return void
	 */
	function display_triggers_box( $post ) {
		$alert = $this->get_alert( $post->ID );

		$form  = new Form_Generator;
		do_action( 'wp_stream_alert_trigger_form_display', $form, $alert );

		// @TODO use human readable text.
		echo '<p>' . esc_html__( 'Create an alert whenever:', 'stream' ) . '</p>';
		echo $form->render_fields(); // Xss ok.

		wp_nonce_field( 'save_post', 'wp_stream_alerts_nonce' );

	}

	/**
	 * Display Submit Box
	 *
	 * @param WP_Post $post Post object for current alert.
	 * @return void
	 */
	function display_submit_box( $post ) {
		if ( empty( $post ) ) {
			return;
		}

		$post_status = $post->post_status;
		if ( 'auto-draft' === $post_status ) {
			$post_status = 'wp_stream_enabled';
		}
		?>
		<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
				<div id="misc-publishing-actions">
					<div class="misc-pub-section misc-pub-post-status">
					<label for="wp_stream_alert_status"><?php esc_html_e( 'Currently active:', 'stream' ) ?></label>
						<select name='wp_stream_alert_status' id='wp_stream_alert_status'>
							<option<?php selected( $post_status, 'wp_stream_enabled' ); ?> value='wp_stream_enabled'><?php esc_html_e( 'Enabled', 'stream' ) ?></option>
							<option<?php selected( $post_status, 'wp_stream_disabled' ); ?> value='wp_stream_disabled'><?php esc_html_e( 'Disabled', 'stream' ) ?></option>
						</select>
					</div>
				</div>
				<div class="clear"></div>
		</div>

		<div id="major-publishing-actions">
			<div id="delete-action">
				<?php
				if ( current_user_can( 'delete_post', $post->ID ) ) {
					if ( ! EMPTY_TRASH_DAYS ) {
						$delete_text = __( 'Delete Permanently', 'stream' );
					} else {
						$delete_text = __( 'Move to Trash', 'stream' );
					}
					?>
				<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php esc_html( $delete_text ); ?></a><?php
				} ?>
				</div>
				<div id="publishing-action">
					<span class="spinner"></span>
					<?php submit_button( __( 'Save' ), 'primary button-large', 'publish', false ); ?>
				</div>
				<div class="clear"></div>
			</div>
	</div>
		<?php
	}

	/**
	 * Return all notification values
	 *
	 * @return array
	 */
	function get_notification_values() {
		$result = array();
		$names  = wp_list_pluck( $this->alert_types, 'name', 'slug' );
		foreach ( $names as $slug => $name ) {
			$result[ $slug ] = $name;
		}
		return $result;
	}

	/**
	 * Process alert settings
	 *
	 * @filter wp_insert_post_data
	 *
	 * @param array $data Processed post data.
	 * @param array $postarr Raw POST data.
	 * @return array
	 */
	function save_post_info( $data, $postarr ) {

		if ( ! isset( $_POST['wp_stream_alerts_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_stream_alerts_nonce'] ) ), 'save_post' ) ) {
				return $data;
		}

		if ( self::POST_TYPE !== $data['post_type'] || ( isset( $data['post_status'] ) && 'auto-draft' === $data['post_status'] ) ) {
			return $data;
		}

		$post_type = get_post_type_object( $data['post_type'] );
		if ( ! current_user_can( $post_type->cap->edit_post, $postarr['ID'] ) ) {
			return $data;
		}

		$alert = $this->get_alert( $postarr['ID'] );
		$alert->status     = wp_stream_filter_input( INPUT_POST, 'wp_stream_alert_status' );
		$alert->alert_type = wp_stream_filter_input( INPUT_POST, 'wp_stream_alert_type' );

		do_action( 'wp_stream_alert_trigger_form_save', $alert );
		$alert->get_alert_type_obj()->save_fields( $alert );

		$data = $alert->process_settings_form( $data );

		return $data;
	}

	/**
	 * Update actions dropdown options based on the connector selected.
	 */
	function update_actions() {
		check_ajax_referer( self::NONCE_NAME, 'security' );
		if ( empty( $_POST['connector'] ) ) {
			return false;
		}
		$connector = sanitize_text_field( wp_unslash( $_POST['connector'] ) );
		$actions = $GLOBALS['wp_stream']->connectors->term_labels['stream_action'];
		if ( ! empty( $connector ) ) {
			$class_name = '\WP_Stream\Connector_' . ucfirst( $connector );
			if ( class_exists( $class_name ) ) {
				$connector_class = new $class_name();
				$actions         = $connector_class->get_action_labels();
			}
		}
		ksort( $actions );
		die( wp_json_encode( $actions ) );
	}
}
