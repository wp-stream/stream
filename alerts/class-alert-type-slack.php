<?php
/**
 * Slack Alerts.
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class Alert_Type_Slack
 *
 * @package WP_Stream
 */
class Alert_Type_Slack extends Alert_Type {

	/**
	 * Alert type name
	 *
	 * @var string
	 */
	public $name = 'Slack';

	/**
	 * Alert type slug
	 *
	 * @var string
	 */
	public $slug = 'slack';

	/**
	 * Class Constructor
	 *
	 * @param Plugin $plugin Plugin object.
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );
		$this->plugin = $plugin;
		if ( ! is_admin() ) {
			return;
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
	 * Sends an message to the Slack channel.
	 *
	 * @param int   $record_id Record that triggered notification.
	 * @param array $recordarr Record details.
	 * @param Alert $alert Alert options.
	 * @return void
	 */
	public function alert( $record_id, $recordarr, $alert ) {
		$options = wp_parse_args(
			$alert->alert_meta,
			array(
				'webhook'           => '',
				'channel'           => '',
				'username'          => '',
				'icon'              => '',
				'trigger_action'    => '',
				'trigger_connector' => '',
				'trigger_context'   => '',
			)
		);
		if ( empty( $options['webhook'] ) ) {
			return;
		}
		$user_id           = (int) $recordarr['user_id'];
		$user              = get_userdata( $user_id );
		$maybe_custom_logo = get_theme_mod( 'custom_logo' );
		$default_logo_url  = ( ! empty( $maybe_custom_logo ) ) ? wp_get_attachment_image_url( $maybe_custom_logo, 'full' ) : '';
		$context           = $recordarr['context'];
		$action            = $recordarr['action'];

		if ( ! empty( $alert->alert_meta['trigger_context'] ) ) {
			$context = $this->plugin->alerts->alert_triggers['context']->get_display_value( 'list_table', $alert );
		}
		if ( ! empty( $alert->alert_meta['trigger_action'] ) ) {
			$action = $this->plugin->alerts->alert_triggers['action']->get_display_value( 'list_table', $alert );
		}

		$fields = array(
			array(
				'title' => 'IP Address',
				'value' => $recordarr['ip'],
				'short' => true,
			),
			array(
				'title' => 'Connector',
				'value' => $recordarr['connector'],
				'short' => true,
			),
			array(
				'title' => 'Context',
				'value' => $context,
				'short' => true,
			),
			array(
				'title' => 'Action',
				'value' => $action,
				'short' => true,
			),
		);

		$post = null;
		if ( isset( $recordarr['object_id'] ) ) {
			$post_id = $recordarr['object_id'];
			$post    = get_post( $post_id );
		}
		if ( is_object( $post ) && ! empty( $post ) ) {
			$post_type      = get_post_type_object( $post->post_type );
			$edit_post_link = get_edit_post_link( $post->ID, 'raw' );
			array_push(
				$fields,
				array(
					'title' => 'Edit ' . $post_type->labels->singular_name,
					'value' => "<$edit_post_link>",
					'short' => false,
				)
			);
		}

		$edit_alert_link = admin_url( 'edit.php?post_type=wp_stream_alerts#post-' . $alert->ID );
		array_push(
			$fields,
			array(
				'title' => 'Edit Alert',
				'value' => "<$edit_alert_link>",
				'short' => false,
			)
		);

		$attachment = array(
			'author_icon' => get_avatar_url( $user_id, 16 ),
			'author_link' => admin_url( "admin.php?page=wp_stream&user_id=$user_id" ),
			'author_name' => trim( "$user->first_name $user->last_name" ),
			'fallback'    => html_entity_decode( $recordarr['summary'], ENT_COMPAT ),
			'fields'      => $fields,
			'footer'      => get_bloginfo( 'name' ),
			'footer_icon' => get_site_icon_url( 16, $default_logo_url, $recordarr['blog_id'] ),
			'title'       => html_entity_decode( $recordarr['summary'], ENT_COMPAT ),
			'ts'          => strtotime( $recordarr['created'] ),
		);
		if ( array_key_exists( 'object_id', $recordarr ) ) {
			$object_id                = (int) $recordarr['object_id'];
			$context                  = $recordarr['context'];
			$attachment['title_link'] = admin_url( "admin.php?page=wp_stream&object_id=$object_id&context=$context" );
		}
		$data = array(
			'attachments' => array( $attachment ),
		);
		if ( ! empty( $options['channel'] ) ) {
			$data['channel'] = $options['channel'];
		}
		if ( ! empty( $options['username'] ) ) {
			$data['username'] = $options['username'];
		}
		if ( ! empty( $options['icon'] ) ) {
			if ( substr( $options['icon'], 0, 1 ) === ':' ) {
				$data['icon_emoji'] = $options['icon'];
			} elseif ( substr( $options['icon'], 0, 4 ) === 'http' ) {
				$data['icon_url'] = $options['icon'];
			}
		}
		wp_safe_remote_post(
			$options['webhook'],
			array(
				'body'    => wp_json_encode( $data ),
				'headers' => array( 'Content-Type' => 'application/json' ),
			)
		);
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
				'webhook'  => '',
				'channel'  => '',
				'username' => '',
				'icon'     => '',
			)
		);
		$form    = new Form_Generator();
		echo '<span class="wp_stream_alert_type_description">' . esc_html__( 'Send a rich message notification to Slack.', 'stream' ) . '</span>';
		echo '<label for="wp_stream_slack_webhook"><span class="title">' . esc_html__( 'Webhook URL', 'stream' ) . '</span>';
		echo '<span class="input-text-wrap">';
		$form->render_field(
			'text',
			array(
				'name'  => 'wp_stream_slack_webhook',
				'title' => esc_attr( __( 'Webhook URL', 'stream' ) ),
				'value' => $options['webhook'],
			)
		);
		echo '</span>';
		echo '<span class="input-text-wrap">' . esc_html__( 'The webhook URL', 'stream' ) . '</span>';
		echo '</label>';
		echo '<label for="wp_stream_slack_channel"><span class="title">' . esc_html__( 'Channel', 'stream' ) . '</span>';
		echo '<span class="input-text-wrap">';
		$form->render_field(
			'text',
			array(
				'name'  => 'wp_stream_slack_channel',
				'title' => esc_attr( __( 'Channel', 'stream' ) ),
				'value' => $options['channel'],
			)
		);
		echo '</span>';
		echo '<span class="input-text-wrap">' . esc_html__( 'The channel to send to (optional)', 'stream' ) . '</span>';
		echo '</label>';
		echo '<label for="wp_stream_slack_username"><span class="title">' . esc_html__( 'Username', 'stream' ) . '</span>';
		echo '<span class="input-text-wrap">';
		$form->render_field(
			'text',
			array(
				'name'  => 'wp_stream_slack_username',
				'title' => esc_attr( __( 'Username', 'stream' ) ),
				'value' => $options['username'],
			)
		);
		echo '</span>';
		echo '<span class="input-text-wrap">' . esc_html__( 'The username to send as (optional)', 'stream' ) . '</span>';
		echo '</label>';
		echo '<label for="wp_stream_slack_icon"><span class="title">' . esc_html__( 'Icon', 'stream' ) . '</span>';
		echo '<span class="input-text-wrap">';
		$form->render_field(
			'text',
			array(
				'name'  => 'wp_stream_slack_icon',
				'title' => esc_attr( __( 'Icon', 'stream' ) ),
				'value' => $options['icon'],
			)
		);
		echo '</span>';
		echo '<span class="input-text-wrap">' . esc_html__( 'The URL or emoji (with colons!) to use as the icon (optional)', 'stream' ) . '</span>';
		echo '</label>';
	}

	/**
	 * Validates and saves form settings for later use.
	 *
	 * @param Alert $alert Alert object for the currently displayed alert.
	 * @return void
	 */
	public function save_fields( $alert ) {
		check_admin_referer( 'save_alert', 'wp_stream_alerts_nonce' );

		$webhook = wp_stream_filter_input( 'INPUT_POST', 'wp_stream_slack_webhook', 'FILTER_VALIDATE_URL' );
		if ( ! empty( $webhook ) ) {
			$alert->alert_meta['webhook'] = $webhook;
		}
		$channel = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_channel' );
		if ( ! empty( $channel ) ) {
			$alert->alert_meta['channel'] = $channel;
		}
		$username = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_username' );
		if ( ! empty( $username ) ) {
			$alert->alert_meta['username'] = $username;
		}
		$icon = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_icon' );
		if ( ! empty( $icon ) ) {
			$alert->alert_meta['icon'] = $icon;
		}
	}

	/**
	 * Add alert meta if this is a Slack alert
	 *
	 * @param array  $alert_meta The metadata to be inserted for this alert.
	 * @param string $alert_type The type of alert being added or updated.
	 *
	 * @return mixed
	 */
	public function add_alert_meta( $alert_meta, $alert_type ) {
		if ( $this->slug === $alert_type ) {
			$webhook = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_webhook' );
			if ( ! empty( $webhook ) ) {
				$alert_meta['webhook'] = $webhook;
			}
			$channel = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_channel' );
			if ( ! empty( $channel ) ) {
				$alert_meta['channel'] = $channel;
			}
			$username = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_username' );
			if ( ! empty( $username ) ) {
				$alert_meta['username'] = $username;
			}
			$icon = wp_stream_filter_input( INPUT_POST, 'wp_stream_slack_icon' );
			if ( ! empty( $icon ) ) {
				$alert_meta['icon'] = $icon;
			}
		}

		return $alert_meta;
	}
}
