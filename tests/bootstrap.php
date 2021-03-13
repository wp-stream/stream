<?php

// Defined in docker-compose.yml for the container running the tests.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( empty( $_tests_dir ) || ! file_exists( $_tests_dir . '/includes' ) ) {
	trigger_error( 'Unable to locate WP_TESTS_DIR', E_USER_ERROR );
}

// Use in code to trigger custom actions.
define( 'WP_STREAM_TESTS', true );
define( 'WP_STREAM_DEV_DEBUG', true );

// @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/functions.php
require_once $_tests_dir . '/includes/functions.php';

/**
 * Force plugins defined in a constant (supplied by phpunit.xml) to be active at runtime.
 *
 * @filter site_option_active_sitewide_plugins
 * @filter option_active_plugins
 *
 * @param array $active_plugins
 * @return array
 */
function wp_stream_filter_active_plugins_for_phpunit( $active_plugins ) {
	$forced_active_plugins = array();
	if ( defined( 'WP_TEST_ACTIVATED_PLUGINS' ) ) {
		$forced_active_plugins = preg_split( '/\s*,\s*/', WP_TEST_ACTIVATED_PLUGINS );
	}

	if ( ! empty( $forced_active_plugins ) ) {
		foreach ( $forced_active_plugins as $forced_active_plugin ) {
			$active_plugins[] = $forced_active_plugin;
		}
	}
	return $active_plugins;
}
tests_add_filter( 'site_option_active_sitewide_plugins', 'wp_stream_filter_active_plugins_for_phpunit' );
tests_add_filter( 'option_active_plugins', 'wp_stream_filter_active_plugins_for_phpunit' );

tests_add_filter(
	'muplugins_loaded',
	function() {
		// Manually load the plugin.
		require dirname( __DIR__ ) . '/stream.php';

		// Install database.
		$plugin = wp_stream_get_instance();
		$plugin->install->check();
	}
);

function xwp_manually_load_mercator() {
	define( 'MERCATOR_SKIP_CHECKS', true );
	require WPMU_PLUGIN_DIR . '/mercator/mercator.php';
}

tests_add_filter( 'muplugins_loaded', 'xwp_manually_load_mercator' );

function xwp_install_edd() {
	// Install Easy Digital Downloads
	edd_install();

	global $current_user, $edd_options;

	$edd_options = get_option( 'edd_settings' );

	$current_user = new WP_User(1);
	$current_user->set_role('administrator');
	wp_update_user( array( 'ID' => 1, 'first_name' => 'Admin', 'last_name' => 'User' ) );
	add_filter( 'edd_log_email_errors', '__return_false' );

	add_filter(
		'pre_http_request',
		function( $status = false, $args = array(), $url = '') {
			return new WP_Error( 'no_reqs_in_unit_tests', __( 'HTTP Requests disabled for unit tests', 'easy-digital-downloads' ) );
		}
	);
}

function wp_stream_install_wc() {
	WC_Install::install();

	// Initialize the WC API extensions.
	\Automattic\WooCommerce\Admin\Install::create_tables();
	\Automattic\WooCommerce\Admin\Install::create_events();

	// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
	if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
		$GLOBALS['wp_roles']->reinit();
	} else {
		$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_roles();
	}

	echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
}

// install WC.
tests_add_filter( 'setup_theme', 'wp_stream_install_wc' );

// @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/bootstrap.php
require $_tests_dir . '/includes/bootstrap.php';

define( 'EDD_USE_PHP_SESSIONS', false );
define( 'WP_USE_THEMES', false );
activate_plugin( 'easy-digital-downloads/easy-digital-downloads.php' );
xwp_install_edd();

require __DIR__ . '/testcase.php';

// Base class for future tests
require __DIR__ . '/tests/test-class-alert-trigger.php';
