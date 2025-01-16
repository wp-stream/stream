<?php
/**
 * Connector for Two Factor
 *
 * @package WP_Stream
 */

namespace WP_Stream;

/**
 * Class - Connector_Two_Factor
 */
class Connector_Two_Factor extends Connector {
	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public $name = 'twofactor';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array(
		'update_user_meta', // Before user meta changes.
		'updated_user_meta', // After user meta changes.
		'added_user_meta', // After user meta is added.

		'two_factor_user_authenticated', // Authenticate via 2FA.
		'wp_login_failed', // Failed login.
	);

	/**
	 * Tracked option keys
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Record the user_meta meta_value before updates.
	 *
	 * @var array
	 */
	public $user_meta = array();

	/**
	 * Check if plugin dependencies are satisfied and add an admin notice if not
	 *
	 * @return bool
	 */
	public function is_dependency_satisfied() {
		if ( class_exists( 'Two_Factor_Core' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html__( 'Two Factor', 'stream' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public function get_action_labels() {
		return array(
			'enabled'       => esc_html_x( 'Enabled', 'two-factor', 'stream' ),
			'disabled'      => esc_html_x( 'Disabled', 'two-factor', 'stream' ),
			'recovered'     => esc_html_x( 'Recovered', 'two-factor', 'stream' ),
			'updated'       => esc_html_x( 'Updated', 'two-factor', 'stream' ),
			'removed'       => esc_html_x( 'Removed', 'two-factor', 'stream' ),
			'added'         => esc_html_x( 'Added', 'two-factor', 'stream' ),
			'authenticated' => esc_html_x( 'Authenticated', 'two-factor', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'user-settings' => esc_html_x( 'User Settings', 'two-factor', 'stream' ),
			'auth'          => esc_html_x( 'Authenticated', 'two-factor', 'stream' ),
		);
	}

	/**
	 * Register the connector
	 */
	public function register() {
		parent::register();

		add_filter( 'wp_stream_log_data', array( $this, 'log_override' ) );
	}

	/**
	 * Modify or prevent logging of some actions.
	 *
	 * @param array $data Record data.
	 *
	 * @return array|bool
	 */
	public function log_override( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// If a login was made but no cookies are being sent (ie. hit the 2FA interstitial), don't log it.
		if (
			'users' === $data['connector'] &&
			'sessions' === $data['context'] &&
			'login' === $data['action'] &&
			\Two_Factor_Core::is_user_using_two_factor( $data['user_id'] ) &&
			has_filter( 'send_auth_cookies', '__return_false' )
		) {
			$data = false;
		}

		return $data;
	}

	/**
	 * Callback to watch for user_meta changes BEFORE it's made.
	 *
	 * @param int    $meta_id        Meta ID.
	 * @param int    $user_id        User ID.
	 * @param string $meta_key       Meta key.
	 * @param mixed  $new_meta_value The NEW meta value.
	 */
	public function callback_update_user_meta( $meta_id, $user_id, $meta_key, $new_meta_value ) {
		unset( $meta_id );
		unset( $new_meta_value );

		switch ( $meta_key ) {
			case '_two_factor_backup_codes':
			case '_two_factor_totp_key':
			case '_two_factor_enabled_providers':
				$this->user_meta[ $user_id ][ $meta_key ] = get_user_meta( $user_id, $meta_key, true );
				break;
		}
	}

	/**
	 * Callback to watch for user_meta changes AFTER it's made.
	 *
	 * @param int    $meta_id        Meta ID.
	 * @param int    $user_id        User ID.
	 * @param string $meta_key       Meta key.
	 * @param mixed  $new_meta_value The NEW meta value.
	 */
	public function callback_updated_user_meta( $meta_id, $user_id, $meta_key, $new_meta_value ) {
		unset( $meta_id );

		$old_meta_value = $this->user_meta[ $user_id ][ $meta_key ] ?? null;
		unset( $this->user_meta[ $user_id ][ $meta_key ] );

		switch ( $meta_key ) {
			case '_two_factor_backup_codes':
				$this->log(
					__( 'Updated backup codes', 'stream' ),
					array(),
					$user_id,
					'user-settings',
					'updated'
				);
				break;
			case '_two_factor_totp_key':
				$this->log(
					__( 'Set TOTP secret key', 'stream' ),
					array(),
					$user_id,
					'user-settings',
					'updated'
				);
				break;
			case '_two_factor_enabled_providers':
				$old_providers = $old_meta_value ?? array();
				$new_providers = $new_meta_value ?? array();

				$enabled_providers  = array_diff( $new_providers, $old_providers );
				$disabled_providers = array_diff( $old_providers, $new_providers );

				foreach ( $enabled_providers as $provider ) {

					/* Translators: %s is the Two Factor provider. */
					$message = __(
						'Enabled provider: %s',
						'stream'
					);

					$this->log(
						$message,
						array(
							'provider' => $provider,
						),
						$user_id,
						'user-settings',
						'enabled'
					);
				}

				foreach ( $disabled_providers as $provider ) {

					/* Translators: %s is the Two Factor provider. */
					$message = __(
						'Disabled provider: %s',
						'stream'
					);

					$this->log(
						$message,
						array(
							'provider' => $provider,
						),
						$user_id,
						'user-settings',
						'disabled'
					);
				}
				break;
		}
	}

	/**
	 * Callback to watch for user_meta changes AFTER it's added.
	 *
	 * @param int    $meta_id        Meta ID.
	 * @param int    $user_id        User ID.
	 * @param string $meta_key       Meta key.
	 * @param mixed  $meta_value     Meta value.
	 */
	public function callback_added_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
		unset( $meta_id );

		switch ( $meta_key ) {
			case '_two_factor_backup_codes':
				$this->log(
					__( 'Added backup codes', 'stream' ),
					array(),
					$user_id,
					'user-settings',
					'added'
				);
				break;
			case '_two_factor_totp_key':
				$this->log(
					__( 'Added TOTP secret key', 'stream' ),
					array(),
					$user_id,
					'user-settings',
					'added'
				);
				break;
			case '_two_factor_enabled_providers':
				foreach ( $meta_value as $provider ) {

					/* Translators: %s is the Two Factor provider. */
					$message = __(
						'Enabled provider: %s',
						'stream'
					);

					$this->log(
						$message,
						array(
							'provider' => $provider,
						),
						$user_id,
						'user-settings',
						'enabled'
					);
				}
				break;
		}
	}

	/**
	 * Callback to watch for 2FA authenticated actions.
	 *
	 * @param \WP_User $user     Authenticated user.
	 * @param object   $provider The 2FA Provider used.
	 */
	public function callback_two_factor_user_authenticated( $user, $provider ) {

		/* Translators: %s is the Two Factor provider. */
		$message = __(
			'Authenticated via %s',
			'stream'
		);

		$this->log(
			$message,
			array(
				'provider' => $provider->get_key(),
			),
			$user->ID,
			'auth',
			'authenticated',
			$user->ID
		);
	}

	/**
	 * Callback to watch for failed logins with Two Factor errors.
	 *
	 * @param string    $user_login User login.
	 * @param \WP_Error $error WP_Error object.
	 */
	public function callback_wp_login_failed( $user_login, $error ) {
		if ( ! str_starts_with( $error->get_error_code(), 'two_factor_' ) ) {
			return;
		}

		$user = get_user_by( 'login', $user_login );
		if ( ! $user && is_email( $user_login ) ) {
			$user = get_user_by( 'email', $user_login );
		}

		/* Translators: %1$s is the user display name, %2$s is the error code, %3$s is the error message. */
		$message = __(
			'%1$s Failed 2FA: %2$s %3$s',
			'stream'
		);

		$this->log(
			$message,
			array(
				'display_name' => $this->escape_percentages( $user->display_name ),
				'code'         => $error->get_error_code(),
				'error'        => $error->get_error_message(),
			),
			$user->ID,
			'auth',
			'failed',
			$user->ID
		);
	}
}
