<?php
/**
 * Listing of Alerts in the WP Admin.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class Alerts_List
 *
 * @package WP_Stream
 */
class Alerts_List {
	/**
	 * Holds instance of plugin object
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Class constructor.
	 *
	 * @param Plugin $plugin Instance of plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_filter( 'bulk_actions-edit-wp_stream_alerts', array( $this, 'suppress_bulk_actions' ), 10, 1 );
		add_filter( 'disable_months_dropdown', array( $this, 'suppress_months_dropdown' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'suppress_quick_edit' ), 10, 1 );

		// @todo Make more specific
		if ( is_admin() ) {
			add_filter( 'request', array( $this, 'parse_request' ), 10, 2 );
		}
		add_filter( 'views_edit-wp_stream_alerts', array( $this, 'manage_views' ) );

		add_filter( 'manage_wp_stream_alerts_posts_columns', array( $this, 'manage_columns' ) );
		add_action( 'manage_wp_stream_alerts_posts_custom_column', array( $this, 'column_data' ), 10, 2 );

		add_action( 'quick_edit_custom_box', array( $this, 'display_custom_quick_edit' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'save_alert_inline_edit' ), 99, 2 );
	}

	/**
	 * Default to wp_stream_enabled and wp_stream_disabled when querying for alerts
	 *
	 * @filter request
	 *
	 * @param array $query_vars Arguments for query to populate table.
	 * @return array
	 */
	public function parse_request( $query_vars ) {
		$screen = \get_current_screen();
		if ( 'edit-wp_stream_alerts' === $screen->id && Alerts::POST_TYPE === $query_vars['post_type'] && empty( $query_vars['post_status'] ) ) {
			$query_vars['post_status'] = array( 'wp_stream_enabled', 'wp_stream_disabled' );
		}
		return $query_vars;
	}

	/**
	 * Manage views on the alerts list view
	 *
	 * @filter views_edit-wp_stream_alerts
	 *
	 * @param array $views View links HTML.
	 * @return array
	 */
	public function manage_views( $views ) {

		if ( array_key_exists( 'trash', $views ) ) {
			$trash = $views['trash'];
			unset( $views['trash'] );
			$views['trash'] = $trash;
		}

		return $views;
	}

	/**
	 * Manages columns on the alerts list view
	 *
	 * @filter manage_wp_stream_alerts_posts_columns
	 *
	 * @param array $columns Column id -> title array.
	 * @return array
	 */
	public function manage_columns( $columns ) {
		$columns = array(
			'cb'            => $columns['cb'],
			'alert_trigger' => __( 'Trigger', 'stream' ),
			'alert_type'    => __( 'Type', 'stream' ),
			'alert_status'  => __( 'Status', 'stream' ),
		);
		return $columns;
	}

	/**
	 * Fills in column data for custom columns.
	 *
	 * @action manage_wp_stream_alerts_posts_custom_column
	 *
	 * @param string $column_name Column name to show data for.
	 * @param int    $post_id The post being processed.
	 * @return mixed
	 */
	public function column_data( $column_name, $post_id ) {

		$alert = $this->plugin->alerts->get_alert( $post_id );
		if ( false === $alert ) {
			return;
		}

		switch ( $column_name ) {
			case 'alert_trigger':
				$values = array();
				foreach ( $this->plugin->alerts->alert_triggers as $trigger_type => $trigger_obj ) {
					$value    = $trigger_obj->get_display_value( 'list_table', $alert );
					$values[] = '<span class="alert_trigger_value alert_trigger_' . esc_attr( $trigger_type ) . '">' . esc_html( $value ) . '</span>';
				}
				?>
				<div><?php echo wp_kses_post( join( '', $values ) ); ?></div>
				<div class="row-actions wp-stream-show-mobile">
					<?php echo wp_kses_post( $this->custom_column_actions( $post_id ) ); ?>
					<button type="button" class="toggle-row"><span class="screen-reader-text"><?php echo esc_html__( 'Show more details', 'stream' ); ?></span></button>
				</div>
				<?php
				if ( ! empty( $alert->alert_meta['trigger_connector'] ) ) {
					$trigger_connector = $alert->alert_meta['trigger_connector'];
				} else {
					$trigger_connector = '';
				}
				if ( ! empty( $alert->alert_meta['trigger_context'] ) ) {
					$trigger_context = $alert->alert_meta['trigger_context'];
				} else {
					$trigger_context = '';
				}
				if ( ! empty( $alert->alert_meta['trigger_action'] ) ) {
					$trigger_action = $alert->alert_meta['trigger_action'];
				} else {
					$trigger_action = '';
				}
				?>
				<input type="hidden" name="wp_stream_trigger_connector" value="<?php echo esc_attr( $trigger_connector ); ?>" />
				<input type="hidden" name="wp_stream_trigger_context" value="<?php echo esc_attr( $trigger_context ); ?>" />
				<input type="hidden" name="wp_stream_trigger_action" value="<?php echo esc_attr( $trigger_action ); ?>" />
				<?php
				echo wp_kses_post( $this->custom_column_actions( $post_id ) );
				break;
			case 'alert_type':
				$alert_type = $alert->alert_type;
				if ( ! empty( $this->plugin->alerts->alert_types[ $alert_type ]->name ) ) {
					$alert_name = $this->plugin->alerts->alert_types[ $alert_type ]->name;
				} else {
					$alert_name = 'Untitled Alert';
				}
				?>
				<input type="hidden" name="wp_stream_alert_type" value="<?php echo esc_attr( $alert->alert_type ); ?>" />
				<strong class="row-title"><?php echo esc_html( $alert_name ); ?></strong>
				<?php
				if ( ! empty( $alert->alert_meta['color'] ) ) {
					?>
					<input type="hidden" name="wp_stream_highlight_color" value="<?php echo esc_attr( $alert->alert_meta['color'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['email_recipient'] ) ) {
					?>
					<input type="hidden" name="wp_stream_email_recipient" value="<?php echo esc_attr( $alert->alert_meta['email_recipient'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['email_subject'] ) ) {
					?>
					<input type="hidden" name="wp_stream_email_subject" value="<?php echo esc_attr( $alert->alert_meta['email_subject'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['event_name'] ) ) {
					?>
					<input type="hidden" name="wp_stream_ifttt_event_name" value="<?php echo esc_attr( $alert->alert_meta['event_name'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['maker_key'] ) ) {
					?>
					<input type="hidden" name="wp_stream_ifttt_maker_key" value="<?php echo esc_attr( $alert->alert_meta['maker_key'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['slack_webhook'] ) ) {
					?>
					<input type="hidden" name="wp_stream_slack_webhook" value="<?php echo esc_attr( $alert->alert_meta['slack_webhook'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['slack_channel'] ) ) {
					?>
					<input type="hidden" name="wp_stream_slack_channel" value="<?php echo esc_attr( $alert->alert_meta['slack_channel'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['slack_username'] ) ) {
					?>
					<input type="hidden" name="wp_stream_slack_username" value="<?php echo esc_attr( $alert->alert_meta['slack_username'] ); ?>" />
					<?php
				}
				if ( ! empty( $alert->alert_meta['slack_icon'] ) ) {
					?>
					<input type="hidden" name="wp_stream_slack_icon" value="<?php echo esc_attr( $alert->alert_meta['slack_icon'] ); ?>" />
					<?php
				}
				break;
			case 'alert_status':
				$post_status_object = get_post_status_object( get_post_status( $post_id ) );
				if ( ! empty( $post_status_object ) ) {
					echo esc_html( $post_status_object->label );
				}
				?>
				<input type="hidden" name="wp_stream_alert_status" value="<?php echo esc_attr( $post_status_object->name ); ?>" />
				<?php
				break;
		}
	}

	/**
	 * Remove 'edit' action from bulk actions
	 *
	 * @filter bulk_actions-edit-wp_stream_alerts
	 *
	 * @param array $actions List of bulk actions available.
	 * @return array
	 */
	public function suppress_bulk_actions( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Remove quick edit action from inline edit actions
	 *
	 * @filter post_row_actions
	 *
	 * @param array $actions List of inline edit actions available.
	 * @return array
	 */
	public function suppress_quick_edit( $actions ) {
		if ( Alerts::POST_TYPE !== get_post_type() ) {
			return $actions;
		}
		unset( $actions['edit'] );
		unset( $actions['view'] );
		unset( $actions['trash'] );
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * Remove months dropdown from Alerts list page
	 *
	 * @filter disable_months_dropdown
	 *
	 * @param bool   $status Status of months dropdown enabling.
	 * @param string $post_type Post type status is related to.
	 * @return bool
	 */
	public function suppress_months_dropdown( $status, $post_type ) {
		if ( Alerts::POST_TYPE === $post_type ) {
			$status = true;
		}
		return $status;
	}

	/**
	 * Custom column actions for alerts main screen
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return string
	 */
	public function custom_column_actions( $post_id ) {
		$post_status = wp_stream_filter_input( INPUT_GET, 'post_status' );
		ob_start();
		if ( 'trash' !== $post_status ) {
			$bare_url  = admin_url( 'post.php?post=' . $post_id . '&action=trash' );
			$nonce_url = wp_nonce_url( $bare_url, 'trash-post_' . $post_id );
			?>
			<div class="row-actions">
				<span class="inline hide-if-no-js"><a href="#" class="editinline" aria-label="Quick edit “Hello world!” inline"><?php esc_html_e( 'Edit' ); ?></a>&nbsp;|&nbsp;</span>
				<span class="trash">
					<a href="<?php echo esc_url( $nonce_url ); ?>" class="submitdelete"><?php esc_html_e( 'Trash', 'stream' ); ?></a>
				</span>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Display a custom quick edit form.
	 */
	public function display_custom_quick_edit() {
		static $fired = false;
		if ( false !== $fired ) {
			return;
		}
		$screen = get_current_screen();
		if ( 'edit-wp_stream_alerts' !== $screen->id ) {
			return;
		}
		wp_nonce_field( plugin_basename( __FILE__ ), Alerts::POST_TYPE . '_edit_nonce' );
		$box_type = array(
			'triggers',
			'notification',
			'submit',
		);
		?>
		<legend class="inline-edit-legend"><?php esc_html_e( 'Edit', 'stream' ); ?></legend>
		<?php
		foreach ( $box_type as $type ) : // @todo remove inline styles.
			?>
			<fieldset class="inline-edit-col inline-edit-<?php echo esc_attr( Alerts::POST_TYPE ); ?>">
				<?php
				$function_name = 'display_' . $type . '_box';
				$the_post      = get_post();
				call_user_func( array( $this->plugin->alerts, $function_name ), $the_post );
				?>
			</fieldset>
			<?php
		endforeach;
		$fired = true;
	}

	/**
	 * Enqueue scripts for the alerts list screen.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'edit-wp_stream_alerts' !== $screen->id ) {
			return;
		}

		$this->plugin->enqueue_asset(
			'alerts-list',
			array( 'wp-stream-alerts' )
		);
	}

	/**
	 * Save alert meta after using the inline editor.
	 *
	 * @param array $data Filtered post data.
	 * @param array $postarr Raw post data.
	 *
	 * @return array
	 */
	public function save_alert_inline_edit( $data, $postarr ) {
		if ( did_action( 'customize_preview_init' ) || empty( $postarr['ID'] ) ) {
			return $data;
		}

		$post_id   = $postarr['ID'];
		$post_type = wp_stream_filter_input( INPUT_POST, 'post_type' );
		if ( Alerts::POST_TYPE !== $post_type ) {
			return $data;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $data;
		}

		$nonce = wp_stream_filter_input( INPUT_POST, Alerts::POST_TYPE . '_edit_nonce' );
		if ( null === $nonce || ! wp_verify_nonce( $nonce, plugin_basename( __FILE__ ) ) ) {
			return $data;
		}

		$trigger_author                      = wp_stream_filter_input( INPUT_POST, 'wp_stream_trigger_author' );
		$trigger_connector_and_context       = wp_stream_filter_input( INPUT_POST, 'wp_stream_trigger_connector_or_context' );
		$trigger_connector_and_context_split = explode( '-', $trigger_connector_and_context );
		$trigger_connector                   = array_shift( $trigger_connector_and_context_split ) ?? '';
		$trigger_context                     = array_shift( $trigger_connector_and_context_split ) ?? '';

		$trigger_action      = wp_stream_filter_input( INPUT_POST, 'wp_stream_trigger_action' );
		$alert_type          = wp_stream_filter_input( INPUT_POST, 'wp_stream_alert_type' );
		$alert_status        = wp_stream_filter_input( INPUT_POST, 'wp_stream_alert_status' );
		$data['post_status'] = $alert_status;

		update_post_meta( $post_id, 'alert_type', $alert_type );

		$alert_meta = array(
			'trigger_author'    => $trigger_author,
			'trigger_connector' => $trigger_connector,
			'trigger_action'    => $trigger_action,
			'trigger_context'   => $trigger_context,
		);
		$alert_meta = apply_filters( 'wp_stream_alerts_save_meta', $alert_meta, $alert_type );
		update_post_meta( $post_id, 'alert_meta', $alert_meta );
		return $data;
	}
}
