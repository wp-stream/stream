<?php
namespace WP_Stream;

class Test_Admin extends WP_StreamTestCase {
	/**
	 * Holds the admin base class
	 *
	 * @var Admin
	 */
	protected $admin;

	/**
	 * Holds the administrator id.
	 *
	 * @var int
	 */
	protected $admin_user_id;

	public function setUp(): void {
		parent::setUp();

		$this->admin = $this->plugin->admin;
		$this->assertNotEmpty( $this->admin );

		// Add admin user to test caps
		// We need to change user to verify editing option as admin or editor
		$this->admin_user_id = \WP_UnitTestCase_Base::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'test_admin',
				'email'      => 'test@land.com',
			)
		);
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Tear down after each test. Delete the admin user and start afresh.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tear_down();

		if ( is_multisite() ) {
			wpmu_delete_user( $this->admin_user_id );
		} else {
			wp_delete_user( $this->admin_user_id );
		}
	}

	public function test_construct() {
		$this->assertNotEmpty( $this->admin->plugin );
		$this->assertInstanceOf( '\WP_Stream\Plugin', $this->admin->plugin );

		$this->assertTrue( function_exists( 'is_plugin_active_for_network' ) );

		if ( is_multisite() && is_plugin_active_for_network( $this->plugin->locations['plugin'] ) && ! is_network_admin() ) {
			$this->assertTrue( $this->admin->disable_access );
		} else {
			$this->assertFalse( $this->admin->disable_access );
		}
	}

	public function test_init() {
		$this->admin->init();
		$this->assertNotEmpty( $this->admin->network );
		$this->assertNotEmpty( $this->admin->live_update );
		$this->assertNotEmpty( $this->admin->export );

		$this->assertInstanceOf( '\WP_Stream\Network', $this->admin->network );
		$this->assertInstanceOf( '\WP_Stream\Live_Update', $this->admin->live_update );
		$this->assertInstanceOf( '\WP_Stream\Export', $this->admin->export );
	}

	public function test_prepare_admin_notices() {
		// Test no notices
		$this->admin->notices = array();
		$this->admin->prepare_admin_notices();
		$this->assertEmpty( $this->admin->notices );

		// Test settings reset notice
		$_GET['message'] = 'settings_reset';
		$this->admin->prepare_admin_notices();
		$this->assertNotEmpty( $this->admin->notices );

		// Prevent output
		$this->admin->notices = array();
	}

	public function test_notice() {
		// Start with nothing
		$this->admin->notices = array();
		$this->assertEmpty( $this->admin->notices );

		$message  = 'Affirmative, Dave. I read you.';
		$is_error = false;

		$this->admin->notice( $message, $is_error );
		$this->assertNotEmpty( $this->admin->notices );
		ob_start();
		$this->admin->admin_notices();
		$notice = ob_get_clean();

		$this->assertStringContainsString( $message, $notice );
		$this->assertStringContainsString( 'updated', $notice );
		$this->assertStringNotContainsString( 'error', $notice );

		// Clear notices and start again
		$this->admin->notices = array();
		$this->assertEmpty( $this->admin->notices );

		$is_error = true;

		$this->admin->notice( $message, $is_error );
		$this->assertNotEmpty( $this->admin->notices );
		ob_start();
		$this->admin->admin_notices();
		$notice = ob_get_clean();

		$this->assertStringContainsString( $message, $notice );
		$this->assertStringContainsString( 'error', $notice );
		$this->assertStringNotContainsString( 'updated', $notice );

		// Prevent output
		$this->admin->notices = array();
	}

	public function test_admin_notices() {
		$allowed_html         = '<progress class="migration" max="100"></progress>';
		$disallowed_html      = '<iframe></iframe>';
		$this->admin->notices = array(
			array(
				'message'  => "I'm sorry, Dave. I'm afraid I can't do that. $disallowed_html",
				'is_error' => false,
			),
			array(
				'message'  => "This mission is too important for me to allow you to jeopardize it. $allowed_html",
				'is_error' => false,
			),
		);

		ob_start();
		$this->admin->admin_notices();
		$notices = ob_get_clean();

		$this->assertStringContainsString( $allowed_html, $notices );
		$this->assertStringNotContainsString( $disallowed_html, $notices );
		$this->assertStringContainsString( str_replace( $disallowed_html, '', $this->admin->notices[0]['message'] ), $notices );
		$this->assertStringContainsString( wpautop( $this->admin->notices[1]['message'] ), $notices );

		// Prevent output
		$this->admin->notices = array();
	}

	public function test_register_menu() {
		global $menu;
		$menu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		do_action( 'admin_menu' );

		$this->assertNotEmpty( $this->admin->screen_id );
		$this->assertNotEmpty( $this->admin->screen_id['main'] );
		$this->assertNotEmpty( $this->admin->screen_id['settings'] );
	}

	public function test_admin_enqueue_scripts() {
		global $wp_scripts;

		// Non-Stream screen
		$this->admin->admin_enqueue_scripts( 'edit.php' );

		$this->assertFalse( wp_script_is( 'wp-stream-admin' ), 'wp-stream-admin script is not enqueued' );
		$this->assertFalse( wp_style_is( 'wp-stream-admin' ), 'wp-stream-admin style is not enqueued' );

		$this->assertTrue( wp_script_is( 'wp-stream-global' ), 'wp-stream-global script is enqueued' );

		$this->assertStringContainsString(
			'bulk_actions',
			$wp_scripts->get_inline_script_data( 'wp-stream-global', 'before' ),
		);

		// Stream screen
		$this->admin->admin_enqueue_scripts( $this->plugin->admin->screen_id['main'] );

		$this->assertTrue( wp_style_is( 'wp-stream-admin' ), 'wp-stream-admin style is enqueued' );

		$this->assertTrue( wp_script_is( 'wp-stream-select2' ), 'wp-stream-select2 script is enqueued' );
		$this->assertTrue( wp_script_is( 'wp-stream-select2-en' ), 'wp-stream-select2-en script is enqueued' );
		$this->assertTrue( wp_script_is( 'wp-stream-jquery-timeago' ), 'wp-stream-jquery-timeago script is enqueued' );
		$this->assertTrue( wp_script_is( 'wp-stream-jquery-timeago-en' ), 'wp-stream-jquery-timeago-en script is enqueued' );

		$this->assertTrue( wp_script_is( 'wp-stream-admin' ), 'wp-stream-admin script is enqueued' );
		$this->assertTrue( wp_script_is( 'wp-stream-live-updates' ), 'wp-stream-live-updates script is enqueued' );

		$this->assertStringContainsString(
			'i18n',
			$wp_scripts->get_inline_script_data( 'wp-stream-admin', 'before' ),
		);

		$this->assertStringContainsString(
			'current_screen',
			$wp_scripts->get_inline_script_data( 'wp-stream-live-updates', 'before' ),
		);
		$this->assertStringContainsString(
			$this->plugin->admin->screen_id['main'],
			$wp_scripts->get_inline_script_data( 'wp-stream-live-updates', 'before' ),
		);
	}

	public function test_is_stream_screen() {
		$this->assertFalse( $this->admin->is_stream_screen() );

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
		$_GET['page'] = $this->admin->records_page_slug;

		$this->assertTrue( $this->admin->is_stream_screen() );
	}

	public function test_admin_body_class() {
		// Make this the Stream screen
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
		$_GET['page'] = $this->admin->records_page_slug;

		$classes            = 'sit-down-calmy take-a-stress-pill think-things-over';
		$admin_body_classes = $this->admin->admin_body_class( $classes );

		$this->assertStringContainsString( 'think-things-over ', $admin_body_classes );
		$this->assertStringContainsString( $this->admin->admin_body_class . ' ', $admin_body_classes );
		$this->assertStringContainsString( $this->admin->records_page_slug . ' ', $admin_body_classes );
	}

	public function test_admin_menu_css() {
		global $wp_styles;

		$this->admin->admin_menu_css();

		$dependency = $wp_styles->registered['wp-admin'];
		$this->assertArrayHasKey( 'after', $dependency->extra );
		$this->assertNotEmpty( $dependency->extra['after'] );
		$this->assertStringContainsString( "body.{$this->admin->admin_body_class}", $dependency->extra['after'][0] );
	}

	/**
	 * Also tests private method erase_stream_records
	 */
	public function test_wp_ajax_reset() {
		$_REQUEST['wp_stream_nonce']       = wp_create_nonce( 'stream_nonce' );
		$_REQUEST['wp_stream_nonce_reset'] = wp_create_nonce( 'stream_nonce_reset' );

		global $wpdb;

		// Create dummy records
		$stream_data = $this->dummy_stream_data();
		$wpdb->insert( $wpdb->stream, $stream_data );
		$stream_id = $wpdb->insert_id;
		$this->assertNotFalse( $stream_id );

		// Create dummy meta
		$meta_data = $this->dummy_meta_data( $stream_id );
		$wpdb->insert( $wpdb->streammeta, $meta_data );
		$meta_id = $wpdb->insert_id;
		$this->assertNotFalse( $meta_id );

		// Check that records exist
		$stream_result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->stream} WHERE ID = %d", $stream_id ) );
		$this->assertNotEmpty( $stream_result );

		// Check that meta exists
		$meta_result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->streammeta} WHERE meta_id = %d", $meta_id ) );
		$this->assertNotEmpty( $meta_result );

		// Clear records and meta
		$reset = $this->admin->wp_ajax_reset();
		$this->assertTrue( $reset );

		// Check that records have been cleared
		$stream_results = $wpdb->get_results( "SELECT * FROM {$wpdb->stream}" );
		$this->assertEmpty( $stream_results );

		// Check that meta has been cleared
		$meta_results = $wpdb->get_results( "SELECT * FROM {$wpdb->streammeta}" );
		$this->assertEmpty( $meta_results );
	}

	/**
	 * Also tests private method erase_stream_records
	 */
	public function test_wp_ajax_reset_large_records_blog() {

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		global $wpdb;

		$_REQUEST['wp_stream_nonce']       = wp_create_nonce( 'stream_nonce' );
		$_REQUEST['wp_stream_nonce_reset'] = wp_create_nonce( 'stream_nonce_reset' );

		add_filter( 'wp_stream_is_large_records_table', '__return_true' );
		add_filter( 'wp_stream_is_network_activated', '__return_false' );

		$stream_data = $this->dummy_stream_data();
		$wpdb->insert( $wpdb->stream, $stream_data );
		$stream_id = $wpdb->insert_id;
		$this->assertNotFalse( $stream_id );

		$meta_data = $this->dummy_meta_data( $stream_id );
		$wpdb->insert( $wpdb->streammeta, $meta_data );
		$meta_id = $wpdb->insert_id;
		$this->assertNotFalse( $meta_id );

		$stream_data_2 = $this->dummy_stream_data_other_blog();
		$wpdb->insert( $wpdb->stream, $stream_data_2 );
		$stream_id_2 = $wpdb->insert_id;
		$this->assertNotFalse( $stream_id_2 );

		$meta_data = $this->dummy_meta_data( $stream_id_2 );
		$wpdb->insert( $wpdb->streammeta, $meta_data );
		$meta_id_2 = $wpdb->insert_id;
		$this->assertNotFalse( $meta_id_2 );

		// Clear records and meta
		$reset = $this->admin->wp_ajax_reset();
		$this->assertTrue( $reset );

		$current_blog = (int) get_current_blog_id();

		// Assert the scheduled action has been set.
		$this->assertTrue(
			as_has_scheduled_action(
				Admin::ASYNC_DELETION_ACTION
			)
		);

		// Check that records have not been cleared yet.
		$stream_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->stream} WHERE blog_id=%d",
				$current_blog
			)
		);
		$this->assertNotEmpty( $stream_results );

		$this->admin->erase_large_records( 1, 0, $meta_id, $current_blog );

		// Check that records have been cleared.
		$stream_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->stream} WHERE blog_id=%d",
				$current_blog
			)
		);
		$this->assertEmpty( $stream_results );

		// Check that records of the other blog have not been cleared.
		$stream_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->stream} WHERE blog_id=%d",
				$current_blog + 1
			)
		);
		$this->assertNotEmpty( $stream_results );

		// Check that one meta has been cleared
		$meta_results = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->streammeta}" );
		$this->assertEquals( 1, $meta_results );

		remove_filter( 'wp_stream_is_large_records_table', '__return_true' );
		remove_filter( 'wp_stream_is_network_activated', '__return_false' );
	}

	public function test_purge_schedule_setup() {
		wp_clear_scheduled_hook( 'wp_stream_auto_purge' );
		$this->assertFalse( wp_next_scheduled( 'wp_stream_auto_purge' ) );
		$this->admin->purge_schedule_setup();
		$this->assertNotFalse( wp_next_scheduled( 'wp_stream_auto_purge' ) );
	}

	public function test_purge_scheduled_action() {
		// Set the TTL to one day
		if ( is_multisite() && is_plugin_active_for_network( $this->plugin->locations['plugin'] ) ) {
			$options                        = (array) get_site_option( 'wp_stream_network', array() );
			$options['general_records_ttl'] = '1';
			update_site_option( 'wp_stream_network', $options );
		} else {
			$options                        = (array) get_option( 'wp_stream', array() );
			$options['general_records_ttl'] = '1';
			update_option( 'wp_stream', $options );
		}

		global $wpdb;

		// Create (two day old) dummy records
		$stream_data            = $this->dummy_stream_data();
		$stream_data['created'] = gmdate( 'Y-m-d h:i:s', strtotime( '2 days ago' ) );
		$wpdb->insert( $wpdb->stream, $stream_data );
		$stream_id = $wpdb->insert_id;
		$this->assertNotFalse( $stream_id );

		// Create dummy meta
		$meta_data = $this->dummy_meta_data( $stream_id );
		$wpdb->insert( $wpdb->streammeta, $meta_data );
		$meta_id = $wpdb->insert_id;
		$this->assertNotFalse( $meta_id );

		// Purge old records and meta
		$this->admin->purge_scheduled_action();

		// Check if the old records have been cleared
		$stream_results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->stream} WHERE ID = %d", $stream_id ) );
		$this->assertEmpty( $stream_results );

		// Check if the old meta has been cleared
		$meta_results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->streammeta} WHERE meta_id = %d", $meta_id ) );
		$this->assertEmpty( $meta_results );
	}

	public function test_plugin_action_links() {
		$links = array( '<a href="javascript:void(0);">Disconnect</a>' );
		$file  = plugin_basename( $this->plugin->locations['dir'] . 'stream.php' );

		$action_links = $this->admin->plugin_action_links( $links, $file );

		$this->assertStringContainsString( 'Disconnect', $action_links[0] );
		$this->assertStringContainsString( 'Settings', $action_links[1] );
	}

	public function test_render_list_table() {
		$this->admin->register_list_table();

		ob_start();
		$this->admin->render_list_table();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="wrap">', $html );
		$this->assertStringContainsString( 'record-filter-form', $html );
	}

	public function test_render_settings_page() {
		ob_start();
		$this->admin->render_settings_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="wrap">', $html );

		global $wp_scripts;

		$this->assertArrayHasKey( 'wp-stream-settings', $wp_scripts->registered );
	}

	public function test_register_list_table() {
		$this->admin->register_list_table();

		$this->assertNotEmpty( $this->admin->list_table );
		$this->assertInstanceOf( '\WP_Stream\List_Table', $this->admin->list_table );
	}

	/**
	 * Also tests private method role_can_view
	 */
	public function test_filter_user_caps() {
		$user = new \WP_User( $this->admin_user_id );

		$this->plugin->settings->options['general_role_access'] = array( 'administrator' );
		$this->assertTrue( $user->has_cap( $this->admin->view_cap ) );

		$this->plugin->settings->options['general_role_access'] = array( 'editor' );
		$this->assertFalse( $user->has_cap( $this->admin->view_cap ) );
	}

	/**
	 * Also tests private method role_can_view
	 */
	public function test_filter_role_caps() {
		$role = get_role( 'administrator' );

		$this->plugin->settings->options['general_role_access'] = array( 'administrator' );
		$this->assertTrue( $role->has_cap( $this->admin->view_cap ) );

		$this->plugin->settings->options['general_role_access'] = array( 'editor' );
		$this->assertFalse( $role->has_cap( $this->admin->view_cap ) );
	}

	/**
	 * Test Ajax Filters
	 *
	 * @group ajax
	 * @requires PHPUnit 5.7
	 */
	public function test_ajax_filters() {
		$user = new \WP_User( $this->admin_user_id );

		$this->_setRole( 'subscriber' );

		$_POST['filter'] = 'user_id';
		$_POST['q']      = $user->display_name;
		$_POST['nonce']  = wp_create_nonce( 'stream_filters_user_search_nonce' );

		$this->expectException( 'WPAjaxDieStopException' );

		try {
			$this->_handleAjax( 'wp_stream_filters' );
		} catch ( WPAjaxDieStopException $e ) {
			// Do nothing.
		}

		// Check that the exception was thrown.
		$this->assertTrue( isset( $e ) );

		// The output should be a -1 for failure.
		$this->assertEquals( '-1', $e->getMessage() );
		unset( $e );

		$this->_setRole( 'administrator' );

		$this->_handleAjax( 'wp_stream_filters' );
		$json = $this->_last_response;

		$this->assertNotEmpty( $json );
		$data = json_decode( $json );
		$this->assertNotFalse( $data );
		$this->assertNotEmpty( $data );
		$this->assertIsArray( $data );
	}

	public function test_get_users_record_meta() {
		$user_id = $this->admin_user_id;
		$authors = array(
			$user_id => get_user_by( 'id', $user_id ),
		);

		$records = $this->admin->get_users_record_meta( $authors );

		$this->assertArrayHasKey( $user_id, $records );
		$this->assertArrayHasKey( 'text', $records[ $user_id ] );
		$this->assertEquals( 'test_admin', $records[ $user_id ]['text'] );
	}

	public function test_get_user_meta() {
		$key   = 'message_1';
		$value = 'It is dangerous to remain here. You must leave within two days.';
		update_user_meta( $this->admin_user_id, $key, $value );
		$this->assertEquals( $this->admin->get_user_meta( $this->admin_user_id, $key, true ), $value );
	}

	public function test_update_user_meta() {
		$key   = 'message_2';
		$value = 'I understand. It is important that you believe me. Look behind you.';
		$this->admin->update_user_meta( $this->admin_user_id, $key, $value );
		$this->assertEquals( get_user_meta( $this->admin_user_id, $key, true ), $value );
	}

	public function test_delete_user_meta() {
		$key   = 'message_3';
		$value = 'I was David Bowman.';

		update_user_meta( $this->admin_user_id, $key, $value );
		$this->assertEquals( get_user_meta( $this->admin_user_id, $key, true ), $value );

		$this->admin->delete_user_meta( $this->admin_user_id, $key );

		$this->assertEmpty( get_user_meta( $this->admin_user_id, $key, true ) );
	}

	private function dummy_stream_data() {
		return array(
			'object_id' => null,
			'site_id'   => '1',
			'blog_id'   => get_current_blog_id(),
			'user_id'   => '1',
			'user_role' => 'administrator',
			'created'   => gmdate( 'Y-m-d H:i:s' ),
			'summary'   => '"Hello Dave" plugin activated',
			'ip'        => '192.168.0.1',
			'connector' => 'installer',
			'context'   => 'plugins',
			'action'    => 'activated',
		);
	}

	private function dummy_stream_data_other_blog() {
		return array(
			'object_id' => null,
			'site_id'   => '1',
			'blog_id'   => (int) get_current_blog_id() + 1,
			'user_id'   => '1',
			'user_role' => 'administrator',
			'created'   => gmdate( 'Y-m-d H:i:s' ),
			'summary'   => '"Hello Dave" plugin activated',
			'ip'        => '192.168.0.1',
			'connector' => 'installer',
			'context'   => 'plugins',
			'action'    => 'activated',
		);
	}

	private function dummy_meta_data( $stream_id ) {
		return array(
			'record_id'  => $stream_id,
			'meta_key'   => 'space_helmet',
			'meta_value' => 'false',
		);
	}
}
