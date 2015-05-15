<?php

class WP_Stream_Log {

	/**
	 * Hold class instance
	 *
	 * @var string
	 */
	public static $instance;

	/**
	 * Hold event transaction object
	 *
	 * @var object
	 */
	private static $transaction;

	/**
	 * Load log handler class, filterable by extensions
	 *
	 * @return void
	 */
	public static function load() {
		/**
		 * Filter allows developers to change log handler class
		 *
		 * @since 0.2.0
		 *
		 * @return string  Class name to use for log handling
		 */
		$log_handler = apply_filters( 'wp_stream_log_handler', __CLASS__ );

		self::$instance    = new $log_handler;
		self::$transaction = new stdClass;

		add_action( 'wp_loaded', array( __CLASS__, 'transaction_start' ), 999 );
		add_action( 'shutdown', array( __CLASS__, 'transaction_reset' ), 999 );
	}

	/**
	 * Return an active instance of this class, and create one if it doesn't exist
	 *
	 * @return WP_Stream_Log
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Start the transaction timer when WordPress is fully loaded
	 *
	 * @return void
	 */
	public static function transaction_start() {
		self::$transaction->start = microtime( true );

		/**
		 * Fires immediately after the transaction timer has started
		 *
		 * @since 2.0.6
		 *
		 * @param int $transaction_start
		 */
		do_action( 'wp_stream_transaction_start', self::$transaction->start );
	}

	/**
	 * Reset the transaction timer on shutdown
	 *
	 * @return void
	 */
	public static function transaction_reset() {
		self::$transaction->start = null;
	}

	/**
	 * Log handler
	 *
	 * @param         $connector
	 * @param  string $message   sprintf-ready error message string
	 * @param  array  $args      sprintf (and extra) arguments to use
	 * @param  int    $object_id Target object id
	 * @param  string $context   Context of the event
	 * @param  string $action    Action of the event
	 * @param  int    $user_id   User responsible for the event
	 *
	 * @return mixed True if updated, otherwise false|WP_Error
	 */
	public function log( $connector, $message, $args, $object_id, $context, $action, $user_id = null ) {
		global $wpdb;

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( is_null( $object_id ) ) {
			$object_id = 0;
		}

		$wp_cron_tracking = isset( WP_Stream_Settings::$options['advanced_wp_cron_tracking'] ) ? WP_Stream_Settings::$options['advanced_wp_cron_tracking'] : false;
		$author           = new WP_Stream_Author( $user_id );
		$agent            = $author->get_current_agent();

		// WP Cron tracking requires opt-in and WP Cron to be enabled
		if ( ! $wp_cron_tracking && 'wp_cron' === $agent ) {
			return false;
		}

		$user       = new WP_User( $user_id );
		$roles      = get_option( $wpdb->get_blog_prefix() . 'user_roles' );
		$visibility = 'publish';

		if ( self::is_record_excluded( $connector, $context, $action, $user ) ) {
			$visibility = 'private';
		}

		$author_meta = array(
			'user_email'      => (string) ! empty( $user->user_email ) ? $user->user_email : '',
			'display_name'    => (string) $author->get_display_name(),
			'user_login'      => (string) ! empty( $user->user_login ) ? $user->user_login : '',
			'user_role_label' => (string) $author->get_role(),
			'agent'           => (string) $agent,
		);

		if ( 'wp_cli' === $agent && function_exists( 'posix_getuid' ) ) {
			$uid       = posix_getuid();
			$user_info = posix_getpwuid( $uid );

			$author_meta['system_user_id']   = (int) $uid;
			$author_meta['system_user_name'] = (string) $user_info['name'];
		}

		// Prevent any meta with null values from being logged
		$stream_meta = array_filter(
			$args,
			function ( $var ) {
				return ! is_null( $var );
			}
		);

		// All meta must be strings, so we will serialize any array meta values
		array_walk(
			$stream_meta,
			function( &$v ) {
				$v = (string) maybe_serialize( $v );
			}
		);

		// Get the current time in milliseconds
		$iso_8601_extended_date = wp_stream_get_iso_8601_extended_date();

		$recordarr = array(
			'object_id'   => (int) $object_id,
			'site_id'     => (int) is_multisite() ? get_current_site()->id : 1,
			'blog_id'     => (int) apply_filters( 'wp_stream_blog_id_logged', get_current_blog_id() ),
			'author'      => (int) $user_id,
			'author_role' => (string) ! empty( $user->roles ) ? $user->roles[0] : '',
			'author_meta' => (array) $author_meta,
			'created'     => (string) $iso_8601_extended_date,
			'visibility'  => (string) $visibility,
			'type'        => 'stream',
			'summary'     => (string) vsprintf( $message, $args ),
			'connector'   => (string) $connector,
			'context'     => (string) $context,
			'action'      => (string) $action,
			'stream_meta' => (array) $stream_meta,
			'ip'          => (string) wp_stream_filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ),
		);

		// Stop the transaction timer and add values to record meta
		if ( ! empty( self::$transaction->start ) ) {
			self::$transaction->stop = microtime( true );
			self::$transaction->time = round( self::$transaction->stop - self::$transaction->start, 3 ) * 1000; // Use milliseconds

			$recordarr['stream_meta']['transaction_start'] = self::$transaction->start;
			$recordarr['stream_meta']['transaction_stop']  = self::$transaction->stop;
			$recordarr['stream_meta']['transaction_time']  = self::$transaction->time;

			/**
			 * Fires immediately after the transaction timer has stopped
			 *
			 * @since 2.0.6
			 *
			 * @param object $transaction
			 * @param array  $recordarr
			 */
			do_action( 'wp_stream_transaction_stop', self::$transaction, $recordarr );

			// Restart the timer to properly time any subsequent bulk actions
			self::transaction_start();
		}

		$result = WP_Stream::$db->store( array( $recordarr ) );

		self::debug_backtrace( $recordarr );

		return $result;
	}

	/**
	 * This function is use to check whether or not a record should be excluded from the log
	 *
	 * @param $connector string name of the connector being logged
	 * @param $context   string name of the context being logged
	 * @param $action    string name of the action being logged
	 * @param $user_id   int    id of the user being logged
	 * @param $ip        string ip address being logged
	 * @return bool
	 */
	public function is_record_excluded( $connector, $context, $action, $user = null, $ip = null ) {
		if ( is_null( $user ) ) {
			$user = wp_get_current_user();
		}

		if ( is_null( $ip ) ) {
			$ip = wp_stream_filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
		} else {
			$ip = wp_stream_filter_var( $ip, FILTER_VALIDATE_IP );
		}

		$user_role = isset( $user->roles[0] ) ? $user->roles[0] : null;

		$record = array(
			'connector'  => $connector,
			'context'    => $context,
			'action'     => $action,
			'author'     => $user->ID,
			'role'       => $user_role,
			'ip_address' => $ip,
		);

		$exclude_settings = isset( WP_Stream_Settings::$options['exclude_rules'] ) ? WP_Stream_Settings::$options['exclude_rules'] : array();

		if ( isset( $exclude_settings['exclude_row'] ) && ! empty( $exclude_settings['exclude_row'] ) ) {
			foreach ( $exclude_settings['exclude_row'] as $key => $value ) {
				// Prepare values
				$author_or_role = isset( $exclude_settings['author_or_role'][ $key ] ) ? $exclude_settings['author_or_role'][ $key ] : '';
				$connector      = isset( $exclude_settings['connector'][ $key ] ) ? $exclude_settings['connector'][ $key ] : '';
				$context        = isset( $exclude_settings['context'][ $key ] ) ? $exclude_settings['context'][ $key ] : '';
				$action         = isset( $exclude_settings['action'][ $key ] ) ? $exclude_settings['action'][ $key ] : '';
				$ip_address     = isset( $exclude_settings['ip_address'][ $key ] ) ? $exclude_settings['ip_address'][ $key ] : '';

				$exclude = array(
					'connector'  => ! empty( $connector ) ? $connector : null,
					'context'    => ! empty( $context ) ? $context : null,
					'action'     => ! empty( $action ) ? $action : null,
					'ip_address' => ! empty( $ip_address ) ? $ip_address : null,
					'author'     => is_numeric( $author_or_role ) ? absint( $author_or_role ) : null,
					'role'       => ( ! empty( $author_or_role ) && ! is_numeric( $author_or_role ) ) ? $author_or_role : null,
				);

				$exclude_rules = array_filter( $exclude, 'strlen' );

				if ( ! empty( $exclude_rules ) ) {
					$excluded = true;

					foreach ( $exclude_rules as $exclude_key => $exclude_value ) {
						if ( $record[ $exclude_key ] !== $exclude_value ) {
							$excluded = false;
							break;
						}
					}

					if ( $excluded ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Send a full backtrace of calls to the PHP error log for debugging
	 *
	 * @param array $recordarr
	 *
	 * @return void
	 */
	public static function debug_backtrace( $recordarr ) {
		/**
		 * Enable debug backtrace on records.
		 *
		 * This filter is for developer use only. When enabled, Stream will send
		 * a full debug backtrace of PHP calls for each record. Optionally, you may
		 * use the available $recordarr parameter to specify what types of records to
		 * create backtrace logs for.
		 *
		 * @since 2.0.2
		 *
		 * @param array $recordarr
		 *
		 * @return bool  Set to FALSE by default (backtrace disabled)
		 */
		$enabled = apply_filters( 'wp_stream_debug_backtrace', false, $recordarr );

		if ( ! $enabled ) {
			return;
		}

		if ( version_compare( PHP_VERSION, '5.3.6', '<' ) ) {
			error_log( 'WP Stream debug backtrace requires at least PHP 5.3.6' );
			return;
		}

		// Record details
		$summary   = isset( $recordarr['summary'] ) ? $recordarr['summary'] : null;
		$author    = isset( $recordarr['author'] ) ? $recordarr['author'] : null;
		$connector = isset( $recordarr['connector'] ) ? $recordarr['connector'] : null;
		$context   = isset( $recordarr['context'] ) ? $recordarr['context'] : null;
		$action    = isset( $recordarr['action'] ) ? $recordarr['action'] : null;

		// Stream meta
		$stream_meta = isset( $recordarr['stream_meta'] ) ? $recordarr['stream_meta'] : null;

		if ( $stream_meta ) {
			array_walk( $stream_meta, function( &$value, $key ) {
				$value = sprintf( '%s: %s', $key, ( '' === $value ) ? 'null' : $value );
			});

			$stream_meta = implode( ', ', $stream_meta );
		}

		// Author meta
		$author_meta = isset( $recordarr['author_meta'] ) ? $recordarr['author_meta'] : null;

		if ( $author_meta ) {
			array_walk( $author_meta, function( &$value, $key ) {
				$value = sprintf( '%s: %s', $key, ( '' === $value ) ? 'null' : $value );
			});

			$author_meta = implode( ', ', $author_meta );
		}

		ob_start();

		// @codingStandardsIgnoreStart

		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // Option to ignore args requires PHP 5.3.6

		// @codingStandardsIgnoreEnd

		$backtrace = ob_get_clean();
		$backtrace = array_values( array_filter( explode( "\n", $backtrace ) ) );

		$output = sprintf(
			"WP Stream Debug Backtrace\n\n    Summary | %s\n     Author | %s\n  Connector | %s\n    Context | %s\n     Action | %s\nStream Meta | %s\nAuthor Meta | %s\n\n%s\n",
			$summary,
			$author,
			$connector,
			$context,
			$action,
			$stream_meta,
			$author_meta,
			implode( "\n", $backtrace )
		);

		error_log( $output );
	}

}
