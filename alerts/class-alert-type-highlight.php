<?php
/**
 * Highlight Alert type.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class Alert_Type_Highlight
 *
 * @package WP_Stream
 */
class Alert_Type_Highlight extends Alert_Type {

	/**
	 * Main JS file script handle.
	 *
	 * @deprecated 4.1.0 Constant is not used anymore and will be removed.
	 */
	const SCRIPT_HANDLE = 'wp-stream-alert-highlight-js';

	/**
	 * Remove Highlight Ajax action label.
	 */
	const REMOVE_ACTION = 'stream_remove_highlight';

	/**
	 * Remove Action nonce name.
	 */
	const REMOVE_ACTION_NONCE = 'stream-remove-highlight';

	/**
	 * Alert type name
	 *
	 * @var string
	 */
	public $name = 'Highlight';

	/**
	 * Alert type slug
	 *
	 * @var string
	 */
	public $slug = 'highlight';

	/**
	 * The single Alert ID.
	 *
	 * @var int|string
	 */
	public $single_alert_id;

	/**
	 * The Plugin
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Class Constructor
	 *
	 * @param Plugin $plugin Plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );
		$this->plugin = $plugin;
		if ( ! is_admin() ) {
			return;
		}
		add_filter(
			'wp_stream_record_classes',
			array(
				$this,
				'post_class',
			),
			10,
			2
		);
		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'enqueue_scripts',
			)
		);
		add_action(
			'wp_ajax_' . self::REMOVE_ACTION,
			array(
				$this,
				'ajax_remove_highlight',
			)
		);

		if ( ! empty( $this->plugin->connectors->connectors ) && is_array( $this->plugin->connectors->connectors ) ) {
			foreach ( $this->plugin->connectors->connectors as $connector ) {
				add_filter(
					'wp_stream_action_links_' . $connector->name,
					array(
						$this,
						'action_link_remove_highlight',
					),
					10,
					2
				);
			}
		}

		add_filter(
			'wp_stream_alerts_save_meta',
			array(
				$this,
				'add_alert_meta',
			),
			10,
			2
		);
	}

	/**
	 * Record that the Alert was triggered by a Record.
	 *
	 * In self::post_class() this value is checked so we can determine
	 * if the class should be added to the Record's display.
	 *
	 * @param int|string $record_id Record that triggered alert.
	 * @param array      $recordarr Record details.
	 * @param object     $alert Alert options.
	 * @return void
	 */
	public function alert( $record_id, $recordarr, $alert ) {
		$recordarr['ID']       = $record_id;
		$this->single_alert_id = $alert->ID;
		if ( ! empty( $alert->alert_meta['color'] ) ) {
			$alert_meta = array(
				'highlight_color' => $alert->alert_meta['color'],
			);
			Alert::update_record_triggered_alerts( (object) $recordarr, $this->slug, $alert_meta );
		}
	}

	/**
	 * Displays a settings form for the alert type
	 *
	 * @param Alert $alert Alert object for the currently displayed alert.
	 * @return void
	 */
	public function display_fields( $alert ) {
		$alert_meta = array();
		if ( is_object( $alert ) ) {
			$alert_meta = $alert->alert_meta;
		}
		$options = wp_parse_args(
			$alert_meta,
			array(
				'color' => 'yellow',
			)
		);

		$form = new Form_Generator();
		echo '<span class="wp_stream_alert_type_description">' . esc_html__( 'Highlight this alert on the Stream records page.', 'stream' ) . '</span>';
		echo '<label for="wp_stream_highlight_color"><span class="title">' . esc_html__( 'Color', 'stream' ) . '</span>';
		echo '<span class="input-text-wrap">';
		$form->render_field(
			'select',
			array(
				'name'    => 'wp_stream_highlight_color',
				'title'   => esc_attr( __( 'Highlight Color', 'stream' ) ),
				'options' => $this->get_highlight_options(),
				'value'   => $options['color'],
			)
		);
		echo '</span></label>';
	}

	/**
	 * Lists available color options for alerts.
	 *
	 * @return array List of highlight color options.
	 */
	public function get_highlight_options() {
		return array(
			'yellow' => __( 'Yellow', 'stream' ),
			'red'    => __( 'Red', 'stream' ),
			'green'  => __( 'Green', 'stream' ),
			'blue'   => __( 'Blue', 'stream' ),
		);
	}

	/**
	 * Validates and saves form settings for later use.
	 *
	 * @param Alert $alert Alert object for the currently displayed alert.
	 * @return void
	 */
	public function save_fields( $alert ) {
		check_admin_referer( 'save_alert', 'wp_stream_alerts_nonce' );

		if ( empty( $_POST['wp_stream_highlight_color'] ) ) {
			$alert->alert_meta['color'] = 'yellow';
		}
		$input_color = sanitize_text_field( wp_unslash( $_POST['wp_stream_highlight_color'] ) );
		if ( ! array_key_exists( $input_color, $this->get_highlight_options() ) ) {
			$alert->alert_meta['color'] = 'yellow';
		} else {
			$alert->alert_meta['color'] = $input_color;
		}
	}

	/**
	 * Apply highlight to records
	 *
	 * @param array  $classes List of classes being applied to the post.
	 * @param object $record Record data.
	 * @return array New list of classes.
	 */
	public function post_class( $classes, $record ) {
		$record           = new Record( $record );
		$alerts_triggered = $record->get_meta( Alerts::ALERTS_TRIGGERED_META_KEY, true );
		if ( ! empty( $alerts_triggered[ $this->slug ]['highlight_color'] ) ) {
			$color = $alerts_triggered[ $this->slug ]['highlight_color'];
		}

		if ( empty( $color ) || ! is_string( $color ) ) {
			return $classes;
		}
		$classes[] = 'alert-highlight highlight-' . esc_attr( $color ) . ' record-id-' . $record->ID;

		return $classes;
	}

	/**
	 * Maybe add the "Remove Highlight" action link.
	 *
	 * This will appear on highlighted items on
	 * the Record List page.
	 *
	 * This is set to run for all Connectors
	 * in self::__construct().
	 *
	 * @filter wp_stream_action_links_{ connector }
	 *
	 * @param array  $actions Action links.
	 * @param object $record A record object.
	 *
	 * @return mixed
	 */
	public function action_link_remove_highlight( $actions, $record ) {
		$record           = new Record( $record );
		$alerts_triggered = $record->get_meta( Alerts::ALERTS_TRIGGERED_META_KEY, true );
		if ( ! empty( $alerts_triggered[ $this->slug ] ) ) {
			$actions[ __( 'Remove Highlight', 'stream' ) ] = '#';
		}

		return $actions;
	}

	/**
	 * Ajax action to remove highlight.
	 *
	 * This is fired from the "Remove Highlight"
	 * action links on the Records list page.
	 *
	 * First, we validate and sanitize.
	 *
	 * Then, we get the Record meta and remove
	 * the "highlight" array from the
	 * "Alerts triggered" meta field.
	 *
	 * Finally, the meta field is updated.
	 *
	 * Note: this removes ALL highlights
	 * that were triggered by this record, not just
	 * those triggered on specific Alert (post) IDs.
	 *
	 * @action wp_ajax_stream_remove_highlight
	 */
	public function ajax_remove_highlight() {
		check_ajax_referer( self::REMOVE_ACTION_NONCE, 'security' );

		$failure_message = __( 'Removing Highlight Failed', 'stream' );

		if ( empty( $_POST['recordId'] ) ) {
			wp_send_json_error( $failure_message );
		}

		$record_id = sanitize_text_field( wp_unslash( $_POST['recordId'] ) );

		if ( ! is_numeric( $record_id ) ) {
			wp_send_json_error( $failure_message );
		}
		$record_obj       = new \stdClass();
		$record_obj->ID   = $record_id;
		$record           = new Record( $record_obj );
		$alerts_triggered = $record->get_meta( Alerts::ALERTS_TRIGGERED_META_KEY, true );
		if ( isset( $alerts_triggered[ $this->slug ] ) ) {
			unset( $alerts_triggered[ $this->slug ] );
		}
		$record->update_meta( Alerts::ALERTS_TRIGGERED_META_KEY, $alerts_triggered );
		wp_send_json_success();
	}

	/**
	 * Enqueue Highlight-specific scripts.
	 *
	 * @param string $page WP admin page.
	 */
	public function enqueue_scripts( $page ) {
		if ( 'toplevel_page_wp_stream' !== $page ) {
			return;
		}

		$this->plugin->enqueue_asset(
			'alert-type-highlight',
			array(),
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'removeAction' => self::REMOVE_ACTION,
				'security'     => wp_create_nonce( self::REMOVE_ACTION_NONCE ),
			)
		);
	}

	/**
	 * Add alert meta if this is a highlight alert
	 *
	 * @param array  $alert_meta The metadata to be inserted for this alert.
	 * @param string $alert_type The type of alert being added or updated.
	 *
	 * @return mixed
	 */
	public function add_alert_meta( $alert_meta, $alert_type ) {
		if ( $this->slug === $alert_type ) {
			$color = wp_stream_filter_input( INPUT_POST, 'wp_stream_highlight_color' );
			if ( empty( $color ) ) {
				$alert_meta['color'] = 'yellow';
			} else {
				$alert_meta['color'] = $color;
			}
		}

		return $alert_meta;
	}
}
