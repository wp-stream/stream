<?php
/**
 * Initializes plugin
 *
 * @package WP_Stream;
 */

namespace WP_Stream;

use RuntimeException;

/**
 * Class Plugin
 */
class Plugin {
	/**
	 * Plugin version number.
	 *
	 * TODO Maybe pass this as a constructor dependency?
	 *
	 * @const string
	 */
	const VERSION = '4.1.0';

	/**
	 * WP-CLI command
	 *
	 * @const string
	 */
	const WP_CLI_COMMAND = 'stream';


	/**
	 * Used to check if it's a single site, not multisite.
	 *
	 * @const string
	 */
	const SINGLE_SITE = 'single';

	/**
	 * Used to check if it's a multisite with the plugin network enabled.
	 *
	 * @const string
	 */
	const MULTI_NETWORK = 'multisite-network';

	/**
	 * Used to check if it's a multisite with the plugin not network enabled.
	 *
	 * @const string
	 */
	const MULTI_NOT_NETWORK = 'multisite-not-network';

	/**
	 * Holds and manages WordPress Admin configurations.
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Holds and manages alerts.
	 *
	 * @var Alerts
	 */
	public $alerts;

	/**
	 * Holds and manages alerts lists.
	 *
	 * @var Alerts_List
	 */
	public $alerts_list;

	/**
	 * Holds and manages connectors
	 *
	 * @var Connectors
	 */
	public $connectors;

	/**
	 * Holds and manages DB connections.
	 *
	 * @var DB
	 */
	public $db;

	/**
	 * Holds and manages records.
	 *
	 * @var Log
	 */
	public $log;

	/**
	 * Stores and manages WordPress settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Process DB migrations.
	 *
	 * @var Install
	 */
	public $install;

	/**
	 * URLs and Paths used by the plugin
	 *
	 * @var array
	 */
	public $locations = array();

	/**
	 * IP address for the current request to be associated with the log entry.
	 *
	 * @var null|false|string Valid IP address, null if not set, false if invalid.
	 */
	protected $client_ip_address;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$locate = $this->locate_plugin();

		$this->locations = array(
			'plugin'    => $locate['plugin_basename'],
			'dir'       => $locate['dir_path'],
			'url'       => $locate['dir_url'],
			'inc_dir'   => $locate['dir_path'] . 'includes/',
			'class_dir' => $locate['dir_path'] . 'classes/',
		);

		spl_autoload_register( array( $this, 'autoload' ) );

		// Load Action Scheduler.
		require_once $this->locations['dir'] . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

		// Load helper functions.
		require_once $this->locations['inc_dir'] . 'functions.php';

		// Load DB helper interface/class.
		$driver_class = apply_filters( 'wp_stream_db_driver', '\WP_Stream\DB_Driver_WPDB' );
		$driver       = null;

		if ( class_exists( $driver_class ) ) {
			$driver   = new $driver_class();
			$this->db = new DB( $driver );
		}

		$error = false;
		if ( ! $this->db ) {
			$error = esc_html__( 'Stream: Could not load chosen DB driver.', 'stream' );
		} elseif ( ! $driver instanceof DB_Driver ) {
			$error = esc_html__( 'Stream: DB driver must implement DB Driver interface.', 'stream' );
		}

		if ( $error ) {
			wp_die(
				esc_html( $error ),
				esc_html__( 'Stream DB Error', 'stream' )
			);
		}

		// Load languages.
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );

		// Load logger class.
		$this->log = apply_filters( 'wp_stream_log_handler', new Log( $this ) );

		// Set the IP address for the current request.
		$this->client_ip_address = wp_stream_filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

		// Load settings and connectors after widgets_init and before the default init priority.
		add_action( 'init', array( $this, 'init' ), 9 );

		// Add frontend indicator.
		add_action( 'wp_head', array( $this, 'frontend_indicator' ) );

		// Change DB driver after plugin loaded if any add-ons want to replace.
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 20 );

		// Load admin area classes.
		if ( is_admin() || ( defined( 'WP_STREAM_DEV_DEBUG' ) && WP_STREAM_DEV_DEBUG ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$this->admin   = new Admin( $this );
			$this->install = $driver->setup_storage( $this );
		} elseif ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->admin = new Admin( $this, $driver );
		}

		// Load WP-CLI command.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( self::WP_CLI_COMMAND, 'WP_Stream\CLI' );
		}
	}

	/**
	 * Autoloader for classes
	 *
	 * @param string $class_name Fully qualified classname to be loaded.
	 */
	public function autoload( $class_name ) {
		if ( ! preg_match( '/^(?P<namespace>.+)\\\\(?P<autoload>[^\\\\]+)$/', $class_name, $matches ) ) {
			return;
		}

		static $reflection;

		if ( empty( $reflection ) ) {
			$reflection = new \ReflectionObject( $this );
		}

		if ( $reflection->getNamespaceName() !== $matches['namespace'] ) {
			return;
		}

		$autoload_name = $matches['autoload'];
		$autoload_dir  = \trailingslashit( $this->locations['class_dir'] );
		$autoload_path = sprintf( '%sclass-%s.php', $autoload_dir, strtolower( str_replace( '_', '-', $autoload_name ) ) );

		if ( is_readable( $autoload_path ) ) {
			require_once $autoload_path;
		}
	}

	/**
	 * Loads the translation files.
	 *
	 * @action plugins_loaded
	 */
	public function i18n() {
		load_plugin_textdomain( 'stream', false, dirname( $this->locations['plugin'] ) . '/languages/' );
	}

	/**
	 * Load Settings, Notifications, and Connectors
	 *
	 * @action init
	 */
	public function init() {
		$this->settings    = new Settings( $this );
		$this->connectors  = new Connectors( $this );
		$this->alerts      = new Alerts( $this );
		$this->alerts_list = new Alerts_List( $this );
	}

	/**
	 * Displays an HTML comment in the frontend head to indicate that Stream is activated,
	 * and which version of Stream is currently in use.
	 *
	 * @action wp_head
	 *
	 * @return string|void An HTML comment, or nothing if the value is filtered out.
	 */
	public function frontend_indicator() {
		/* translators: Localization not needed */
		$comment = sprintf( 'Stream WordPress user activity plugin v%s', esc_html( $this->get_version() ) );

		/**
		 * Filter allows the HTML output of the frontend indicator comment
		 * to be altered or removed, if desired.
		 *
		 * @return string  The content of the HTML comment
		 */
		$comment = apply_filters( 'wp_stream_frontend_indicator', $comment );

		if ( ! empty( $comment ) ) {
			printf( "<!-- %s -->\n", esc_html( $comment ) );
		}
	}

	/**
	 * Version of plugin_dir_url() which works for plugins installed in the plugins directory,
	 * and for plugins bundled with themes.
	 *
	 * @return array
	 */
	private function locate_plugin() {
		$dir_url         = trailingslashit( plugins_url( '', __DIR__ ) );
		$dir_path        = plugin_dir_path( __DIR__ );
		$dir_basename    = basename( $dir_path );
		$plugin_basename = trailingslashit( $dir_basename ) . 'stream.php';

		return compact( 'dir_url', 'dir_path', 'dir_basename', 'plugin_basename' );
	}

	/**
	 * Getter for the version number.
	 *
	 * @return string
	 */
	public function get_version() {
		return self::VERSION;
	}

	/**
	 * Change plugin database driver in case driver plugin loaded after stream
	 */
	public function plugins_loaded() {
		// Load DB helper interface/class.
		$driver_class = apply_filters( 'wp_stream_db_driver', '\WP_Stream\DB_Driver_WPDB' );

		if ( class_exists( $driver_class ) ) {
			$driver   = new $driver_class();
			$this->db = new DB( $driver );
		}
	}

	/**
	 * Returns true if Stream is network activated, otherwise false
	 *
	 * @return bool
	 */
	public function is_network_activated() {

		$is_network_activated = false;

		if ( $this->is_mustuse() ) {
			$is_network_activated = true;
		} else {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			$is_network_activated = is_plugin_active_for_network( $this->locations['plugin'] );
		}

		/**
		 * Filter allows the network activated detection to be overridden.
		 *
		 * @param string           $is_network_activated  Whether the plugin is network activated.
		 * @param WP_Stream\Plugin $plugin                The stream plugin object.
		 */
		return apply_filters( 'wp_stream_is_network_activated', $is_network_activated, $this );
	}

	/**
	 * Returns true if Stream is a must-use plugin, otherwise false
	 *
	 * @return bool
	 */
	public function is_mustuse() {
		$stream_php = trailingslashit( WPMU_PLUGIN_DIR ) . $this->locations['plugin'];

		if ( file_exists( $stream_php ) && class_exists( 'WP_Stream\Plugin' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the IP address for the current request.
	 *
	 * @return false|null|string Valid IP address, null if not set, false if invalid.
	 */
	public function get_client_ip_address() {
		return apply_filters( 'wp_stream_client_ip_address', $this->client_ip_address );
	}

	/**
	 * Get the site type.
	 *
	 * This function determines the type of site based on whether it is a single site or a multisite.
	 * If it is a multisite, it also checks if it is network activated or not.
	 *
	 * @return string The site type
	 */
	public function get_site_type(): string {

		// If it's a multisite, is it network activated or not?
		if ( is_multisite() ) {
			return $this->is_network_activated() ? self::MULTI_NETWORK : self::MULTI_NOT_NETWORK;
		}

		return self::SINGLE_SITE;
	}

	/**
	 * Should the number of records which need to be processed be considered "large"?
	 *
	 * @param int $record_number The number of rows in the {$wpdb->prefix}_stream table to be processed.
	 * @return bool Whether or not this should be considered large.
	 */
	public function is_large_records_table( int $record_number ): bool {
		/**
		 * Filters whether or not the number of records should be considered a large table.
		 *
		 * @since 4.1.0
		 *
		 * @param bool $is_large_table Whether or not the number of records should be considered large.
		 * @param int  $record_number The number of records being checked.
		 */
		return apply_filters( 'wp_stream_is_large_records_table', $record_number > 1000000, $record_number );
	}

	/**
	 * Checks if the plugin is running on a single site installation.
	 *
	 * @return bool True if the plugin is running on a single site installation, false otherwise.
	 */
	public function is_single_site() {
		return self::SINGLE_SITE === $this->get_site_type();
	}

	/**
	 * Check if the plugin is activated on a multisite installation but not network activated.
	 *
	 * @return bool True if the plugin is activated on a multisite installation but not network activated, false otherwise.
	 */
	public function is_multisite_not_network_activated() {
		return self::MULTI_NOT_NETWORK === $this->get_site_type();
	}

	/**
	 * Check if the plugin is activated on a multisite network.
	 *
	 * @return bool True if the plugin is network activated on a multisite, false otherwise.
	 */
	public function is_multisite_network_activated() {
		return self::MULTI_NETWORK === $this->get_site_type();
	}

	/**
	 * Enqueue a script along with a stylesheet if it exists.
	 *
	 * @param string $handle                  Script handle.
	 * @param array  $additional_dependencies Additional dependencies.
	 * @param array  $data                    Data to pass to the script.
	 *
	 * @throws RuntimeException If built JavaScript assets are not found.
	 * @return void
	 */
	public function enqueue_asset( $handle, $additional_dependencies = array(), $data = array() ) {
		// If is enqueued already, bail out.
		if ( wp_script_is( $handle ) ) {
			return;
		}

		$path = untrailingslashit( $this->locations['dir'] );
		$url  = untrailingslashit( $this->locations['url'] );

		$script_asset_path = "$path/build/$handle.asset.php";

		if ( ! file_exists( $script_asset_path ) ) {
			throw new RuntimeException( 'Built JavaScript assets not found. Please run `npm run build`' );
		}

		$script_asset = require $script_asset_path; // phpcs:disable WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

		wp_enqueue_script(
			"wp-stream-$handle",
			"$url/build/$handle.js",
			array_merge(
				$script_asset['dependencies'],
				(array) $additional_dependencies
			),
			$script_asset['version'],
			true
		);

		if ( file_exists( "$path/build/$handle.css" ) ) {
			wp_enqueue_style(
				"wp-stream-$handle",
				"$url/build/$handle.css",
				array(),
				$script_asset['version']
			);
		}

		if ( ! empty( $data ) ) {
			wp_add_inline_script(
				"wp-stream-$handle",
				sprintf( 'window["%s"] = %s;', esc_attr( "wp-stream-$handle" ), wp_json_encode( $data ) ),
				'before'
			);
		}
	}

	/**
	 * Enqueue select2 script and locale file if exists.
	 *
	 * @return string Script handle.
	 */
	public function with_select2() {
		$handle = 'wp-stream-select2';

		// If is enqueued already, bail out.
		if ( wp_script_is( $handle ) ) {
			return $handle;
		}

		$path = untrailingslashit( $this->locations['dir'] );
		$url  = untrailingslashit( $this->locations['url'] );

		wp_enqueue_script(
			$handle,
			"$url/build/select2/js/select2.full.min.js",
			array( 'jquery' ),
			filemtime( "$path/build/select2/js/select2.full.min.js" ),
			true
		);
		wp_enqueue_style(
			$handle,
			"$url/build/select2/css/select2.min.css",
			array(),
			filemtime( "$path/build/select2/css/select2.min.css" )
		);

		$locale       = get_locale();
		$lang         = substr( $locale, 0, 2 );
		$search_files = array( $locale, $lang, 'en' );

		foreach ( $search_files as $search_file ) {
			if ( file_exists( "$path/build/select2/js/i18n/$search_file.js" ) ) {
				wp_enqueue_script(
					sanitize_title( "$handle-$search_file" ),
					"$url/build/select2/js/i18n/$search_file.js",
					array( $handle ),
					filemtime( "$path/build/select2/js/i18n/$search_file.js" ),
					true
				);
				break;
			}
		}

		return $handle;
	}

	/**
	 * Enqueue jquery.timeago script and locale file if exists.
	 *
	 * @return string Script handle.
	 */
	public function with_jquery_timeago() {
		$handle = 'wp-stream-jquery-timeago';

		// If is enqueued already, bail out.
		if ( wp_script_is( $handle ) ) {
			return $handle;
		}

		$path = untrailingslashit( $this->locations['dir'] );
		$url  = untrailingslashit( $this->locations['url'] );

		wp_enqueue_script(
			$handle,
			"$url/build/timeago/js/jquery.timeago.js",
			array( 'jquery' ),
			filemtime( "$path/build/timeago/js/jquery.timeago.js" ),
			true
		);

		$locale       = get_locale();
		$lang         = substr( $locale, 0, 2 );
		$search_files = array( $locale, $lang, 'en' );

		foreach ( $search_files as $search_file ) {
			if ( file_exists( "$path/build/timeago/js/locales/jquery.timeago.$search_file.js" ) ) {
				wp_enqueue_script(
					sanitize_title( "$handle-$search_file" ),
					"$url/build/timeago/js/locales/jquery.timeago.$search_file.js",
					array( $handle ),
					filemtime( "$path/build/timeago/js/locales/jquery.timeago.$search_file.js" ),
					true
				);
				break;
			}
		}

		return $handle;
	}
}
