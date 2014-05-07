<?php
/**
 * Plugin Name: Stream
 * Plugin URI: https://wp-stream.com/
 * Description: Stream tracks logged-in user activity so you can monitor every change made on your WordPress site in beautifully organized detail. All activity is organized by context, action and IP address for easy filtering. Developers can extend Stream with custom connectors to log any kind of action.
 * Version: 1.4.4
 * Author: X-Team
 * Author URI: https://wp-stream.com/
 * License: GPLv2+
 * Text Domain: stream
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 X-Team (http://x-team.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

class WP_Stream {

	/**
	 * Plugin version number
	 *
	 * @const string
	 */
	const VERSION = '1.4.4';

	/**
	 * Hold Stream instance
	 *
	 * @var string
	 */
	public static $instance;

	/**
	 * @var WP_Stream_DB
	 */
	public static $db = null;

	/**
	 * @var WP_Stream_Network
	 */
	public $network = null;

	/**
	 * Class constructor
	 */
	private function __construct() {
		define( 'WP_STREAM_PLUGIN', plugin_basename( __FILE__ ) );
		define( 'WP_STREAM_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WP_STREAM_URL', plugin_dir_url( __FILE__ ) );
		define( 'WP_STREAM_INC_DIR', WP_STREAM_DIR . 'includes/' );

		// Load filters polyfill
		require_once WP_STREAM_INC_DIR . 'filter-input.php';

		// Load DB helper interface/class
		require_once WP_STREAM_INC_DIR . 'record.php';
		require_once WP_STREAM_INC_DIR . 'db/base.php';
		$driver = apply_filters( 'wp_stream_db_adapter', 'wpdb' );
		if ( file_exists( WP_STREAM_INC_DIR . "db/$driver.php" ) ) {
			require_once WP_STREAM_INC_DIR . "db/$driver.php";
		}
		if ( ! self::$db ) {
			wp_die( __( 'Stream: Could not load chosen DB driver.', 'stream' ), 'Stream DB Error' );
		}

		// Check DB and add message if not present
		add_action( 'init', array( $this, 'verify_database_present' ) );

		// Load languages
		add_action( 'plugins_loaded', array( __CLASS__, 'i18n' ) );

		// Load settings, enabling extensions to hook in
		require_once WP_STREAM_INC_DIR . 'settings.php';
		add_action( 'init', array( 'WP_Stream_Settings', 'load' ) );

		// Load network class
		if ( is_multisite() ) {
			require_once WP_STREAM_INC_DIR . 'network.php';
			$this->network = new WP_Stream_Network;
		}

		// Load logger class
		require_once WP_STREAM_INC_DIR . 'log.php';
		add_action( 'plugins_loaded', array( 'WP_Stream_Log', 'load' ) );

		// Load connectors
		require_once WP_STREAM_INC_DIR . 'connectors.php';
		add_action( 'init', array( 'WP_Stream_Connectors', 'load' ) );

		// Load query class
		require_once WP_STREAM_INC_DIR . 'query.php';
		require_once WP_STREAM_INC_DIR . 'context-query.php';

		// Load support for feeds
		require_once WP_STREAM_INC_DIR . 'feeds.php';
		add_action( 'init', array( 'WP_Stream_Feeds', 'load' ) );

		// Add frontend indicator
		add_action( 'wp_head', array( $this, 'frontend_indicator' ) );

		// Include Stream extension updater
		require_once WP_STREAM_INC_DIR . 'updater.php';
		WP_Stream_Updater::instance();

		if ( is_admin() ) {
			require_once WP_STREAM_INC_DIR . 'admin.php';
			require_once WP_STREAM_INC_DIR . 'extensions.php';
			add_action( 'plugins_loaded', array( 'WP_Stream_Admin', 'load' ) );
			add_action( 'admin_init', array( 'WP_Stream_Extensions', 'get_instance' ) );

			add_action( 'init', array( __CLASS__, 'install' ), 10, 1 );

			// Registers a hook that connectors and other plugins can use whenever a stream update happens
			add_action( 'admin_init', array( __CLASS__, 'update_activation_hook' ) );

			require_once WP_STREAM_INC_DIR . 'dashboard.php';
			add_action( 'plugins_loaded', array( 'WP_Stream_Dashboard_Widget', 'load' ) );

			require_once WP_STREAM_INC_DIR . 'live-update.php';
			add_action( 'plugins_loaded', array( 'WP_Stream_Live_Update', 'load' ) );

			require_once WP_STREAM_INC_DIR . 'pointers.php';
			add_action( 'plugins_loaded', array( 'WP_Stream_Pointers', 'load' ) );
		}

		// Load deprecated functions
		require_once WP_STREAM_INC_DIR . 'deprecated.php';
	}

	/**
	 * Invoked when the PHP version check fails. Load up the translations and
	 * add the error message to the admin notices
	 */
	static function fail_php_version() {
		add_action( 'plugins_loaded', array( __CLASS__, 'i18n' ) );
		self::notice( __( 'Stream requires PHP version 5.3+, plugin is currently NOT ACTIVE.', 'stream' ) );
	}

	/**
	 * Loads the translation files.
	 *
	 * @access public
	 * @action plugins_loaded
	 * @return void
	 */
	public static function i18n() {
		load_plugin_textdomain( 'stream', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Installation / Upgrade checks
	 *
	 * @action register_activation_hook
	 * @return void
	 */
	public static function install() {
		/**
		 * Filter will halt install() if set to true
		 *
		 * @param  bool
		 * @return bool
		 */
		if ( apply_filters( 'wp_stream_no_tables', false ) ) {
			return;
		}

		// Install plugin tables
		require_once WP_STREAM_INC_DIR . 'install.php';
		$update = WP_Stream_Install::get_instance();
	}

	/**
	 * Verify that all needed databases are present and add an error message if not.
	 *
	 * @return void
	 */
	public function verify_database_present() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		/**
		 * Filter will halt verify_database_present() if set to true
		 *
		 * @param  bool
		 * @return bool
		 */
		if ( apply_filters( 'wp_stream_no_tables', false ) ) {
			return;
		}

		global $wpdb;

		$database_message  = '';
		$uninstall_message = '';

		// Check if all needed DB is present
		foreach ( self::$db->get_table_names() as $table_name ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
				$database_message .= sprintf( '%s %s', __( 'The following table is not present in the WordPress database:', 'stream' ), $table_name );
			}
		}

		if ( is_plugin_active_for_network( WP_STREAM_PLUGIN ) && current_user_can( 'manage_network_plugins' ) ) {
			$uninstall_message = sprintf( __( 'Please <a href="%s">uninstall</a> the Stream plugin and activate it again.', 'stream' ), network_admin_url( 'plugins.php#stream' ) );
		} elseif ( current_user_can( 'activate_plugins' ) ) {
			$uninstall_message = sprintf( __( 'Please <a href="%s">uninstall</a> the Stream plugin and activate it again.', 'stream' ), admin_url( 'plugins.php#stream' ) );
		}

		// Check upgrade routine
		self::install();

		if ( ! empty( $database_message ) ) {
			self::notice( $database_message );
			if ( ! empty( $uninstall_message ) ) {
				self::notice( $uninstall_message );
			}
		}
	}

	static function update_activation_hook() {
		WP_Stream_Admin::register_update_hook( dirname( plugin_basename( __FILE__ ) ), array( __CLASS__, 'install' ), self::VERSION );
	}

	/**
	 * Whether the current PHP version meets the minimum requirements
	 *
	 * @return bool
	 */
	public static function is_valid_php_version() {
		return version_compare( PHP_VERSION, '5.3', '>=' );
	}

	/**
	 * Show an error or other message, using admin_notice or WP-CLI logger
	 *
	 * @param string $message
	 * @param bool $is_error
	 * @return void
	 */
	public static function notice( $message, $is_error = true ) {
		if ( defined( 'WP_CLI' ) ) {
			$message = strip_tags( $message );
			if ( $is_error ) {
				WP_CLI::warning( $message );
			} else {
				WP_CLI::success( $message );
			}
		} else {
			$print_message = function () use ( $message, $is_error ) {
				$class_name   = ( $is_error ? 'error' : 'updated' );
				$html_message = sprintf( '<div class="%s">%s</div>', $class_name, wpautop( $message ) );
				echo wp_kses_post( $html_message );
			};
			add_action( 'all_admin_notices', $print_message );
		}
	}

	/**
	 * Displays an HTML comment in the frontend head to indicate that Stream is activated,
	 * and which version of Stream is currently in use.
	 *
	 * @since 1.4.5
	 *
	 * @action wp_head
	 * @return string|void An HTML comment, or nothing if the value is filtered out.
	 */
	public function frontend_indicator() {
		$comment = sprintf( 'Stream WordPress user activity plugin v%s', esc_html( self::VERSION ) ); // Localization not needed

		/**
		 * Filter allows the HTML output of the frontend indicator comment
		 * to be altered or removed, if desired.
		 *
		 * @return string $comment The content of the HTML comment
		 */
		$comment = apply_filters( 'wp_stream_frontend_indicator', $comment );

		if ( ! empty( $comment ) ) {
			echo sprintf( "<!-- %s -->\n", esc_html( $comment ) ); // xss ok
		}

		return;
	}

	/**
	 * Return active instance of WP_Stream, create one if it doesn't exist
	 *
	 * @return WP_Stream
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			$class = __CLASS__;
			self::$instance = new $class;
		}

		return self::$instance;
	}

}

if ( WP_Stream::is_valid_php_version() ) {
	$GLOBALS['wp_stream'] = WP_Stream::get_instance();
	register_activation_hook( __FILE__, array( 'WP_Stream', 'install' ) );
} else {
	WP_Stream::fail_php_version();
}
