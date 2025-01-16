<?php
namespace WP_Stream;

/**
 * Class Test_Alerts
 *
 * @package WP_Stream
 * @group alerts
 */
class Test_Alerts extends WP_StreamTestCase {

	public function tearDown(): void {
		// See test_load_bad_alert_type() and test_load_bad_alert_trigger.
		remove_filter( 'wp_stream_alert_types', array( $this, 'callback_load_bad_alert_register' ), 10, 1 );
		remove_filter( 'wp_stream_alert_triggers', array( $this, 'callback_load_bad_alert_register' ), 10, 1 );
	}

	public function test_construct() {
		$alerts = new Alerts( $this->plugin );
		$this->assertNotEmpty( $alerts->plugin );
	}

	public function test_load_alert_types() {
		$action = new \MockAction();
		add_filter( 'wp_stream_alert_types', array( $action, 'filter' ) );

		$alerts = new Alerts( $this->plugin );

		$this->assertNotEmpty( $alerts->alert_types );
		$this->assertContainsOnlyInstancesOf( 'WP_Stream\Alert_Type', $alerts->alert_types );
		$this->assertArrayHasKey( 'none', $alerts->alert_types );
		$this->assertArrayHasKey( 'highlight', $alerts->alert_types );
		$this->assertArrayHasKey( 'email', $alerts->alert_types );

		$this->assertEquals( 1, $action->get_call_count() );
	}

	/**
	 * Test bad alert type is not added.
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_load_bad_alert_type() {
		$alerts                    = new Alerts( $this->plugin );
		$alert_types_before_filter = count( $alerts->alert_types );
		unset( $alerts );

		add_filter( 'wp_stream_alert_types', array( $this, 'callback_load_bad_alert_register' ), 10, 1 );
		$alerts                   = new Alerts( $this->plugin );
		$alert_types_after_filter = count( $alerts->alert_types );

		$this->assertEquals( $alert_types_before_filter, $alert_types_after_filter );
	}

	public function test_load_alert_triggers() {
		$action = new \MockAction();
		add_filter( 'wp_stream_alert_triggers', array( $action, 'filter' ) );

		$alerts = new Alerts( $this->plugin );

		$this->assertNotEmpty( $alerts->alert_triggers );
		$this->assertContainsOnlyInstancesOf( 'WP_Stream\Alert_Trigger', $alerts->alert_triggers );
		$this->assertArrayHasKey( 'author', $alerts->alert_triggers );
		$this->assertArrayHasKey( 'context', $alerts->alert_triggers );
		$this->assertArrayHasKey( 'author', $alerts->alert_triggers );

		$this->assertEquals( 1, $action->get_call_count() );
	}

	/**
	 * Test bad trigger is not added.
	 */
	public function test_load_bad_alert_trigger() {
		$alerts                       = new Alerts( $this->plugin );
		$alert_triggers_before_filter = count( $alerts->alert_triggers );
		unset( $alerts );

		add_filter( 'wp_stream_alert_triggers', array( $this, 'callback_load_bad_alert_register' ), 10, 1 );
		$alerts                      = new Alerts( $this->plugin );
		$alert_triggers_after_filter = count( $alerts->alert_triggers );

		$this->assertEquals( $alert_triggers_before_filter, $alert_triggers_after_filter );
	}

	public function callback_load_bad_alert_register( $classes ) {
		$classes['bad_alert_trigger'] = new \stdClass();
		return $classes;
	}

	public function test_is_valid_alert_type() {
		$alerts = new Alerts( $this->plugin );
		$this->assertFalse( $alerts->is_valid_alert_type( new \stdClass() ) );
		$this->assertFalse( $alerts->is_valid_alert_type( new Alert_Trigger_Action( $this->plugin ) ) );
	}

	public function test_is_valid_alert_trigger() {
		$alerts = new Alerts( $this->plugin );
		$this->assertFalse( $alerts->is_valid_alert_trigger( new \stdClass() ) );
		$this->assertFalse( $alerts->is_valid_alert_trigger( new Alert_Type_None( $this->plugin ) ) );
	}

	public function test_check_records() {
		$this->markTestIncomplete(
			'This test is incomplete.'
		);
		// WP_Query not finding active alerts.

		$alerts = new Alerts( $this->plugin );
		$alert  = new Alert( $this->dummy_alert_data(), $this->plugin );
		$alert->save();

		$action = new \MockAction();
		add_filter( 'wp_stream_alert_trigger_check', array( $action, 'filter' ) );

		$alerts->check_records( 0, $this->dummy_stream_data() );

		$this->assertEquals( 1, $action->get_call_count() );
	}

	public function test_register_post_type() {
		global $wp_post_types, $wp_post_statuses;
		if ( isset( $wp_post_types['wp_stream_alerts'] ) ) {
			unset( $wp_post_types['wp_stream_alerts'] );
		}
		if ( isset( $wp_post_statuses['wp_stream_enabled'] ) ) {
			unset( $wp_post_statuses['wp_stream_enabled'] );
		}
		if ( isset( $wp_post_statuses['wp_stream_disabled'] ) ) {
			unset( $wp_post_statuses['wp_stream_disabled'] );
		}

		$alerts = new Alerts( $this->plugin );

		$alerts->register_post_type();

		$this->assertArrayHasKey( 'wp_stream_alerts', $wp_post_types );
		$post_type_obj = $wp_post_types['wp_stream_alerts'];

		$this->assertFalse( $post_type_obj->public );
		$this->assertFalse( $post_type_obj->publicly_queryable );
		$this->assertTrue( $post_type_obj->exclude_from_search );
		$this->assertTrue( $post_type_obj->show_ui );
		$this->assertFalse( $post_type_obj->show_in_menu );
		$this->assertFalse( $post_type_obj->supports );

		$this->assertArrayHasKey( 'wp_stream_enabled', $wp_post_statuses );
		$post_status_obj = $wp_post_statuses['wp_stream_enabled'];

		$this->assertFalse( $post_status_obj->public );
		$this->assertTrue( $post_status_obj->show_in_admin_all_list );
		$this->assertTrue( $post_status_obj->show_in_admin_status_list );

		$this->assertArrayHasKey( 'wp_stream_disabled', $wp_post_statuses );
		$post_status_obj = $wp_post_statuses['wp_stream_disabled'];

		$this->assertFalse( $post_status_obj->public );
		$this->assertTrue( $post_status_obj->show_in_admin_all_list );
		$this->assertTrue( $post_status_obj->show_in_admin_status_list );
	}

	public function test_get_alert() {
		$alerts = new Alerts( $this->plugin );

		$data           = $this->dummy_alert_data();
		$data->ID       = 0;
		$original_alert = new Alert( $data, $this->plugin );
		$post_id        = $original_alert->save();

		$alert = $alerts->get_alert( $post_id );
		$this->assertEquals( $original_alert, $alert );
	}

	public function test_get_alert_blank() {
		$alerts = new Alerts( $this->plugin );
		$alert  = $alerts->get_alert();

		$this->assertEmpty( $alert->ID );
		$this->assertEmpty( $alert->date );
		$this->assertEmpty( $alert->author );
		$this->assertEmpty( $alert->alert_type );

		$this->assertEquals( $alert->status, 'wp_stream_disabled' );
		$this->assertEquals( $alert->alert_meta, array() );
	}

	public function test_register_menu() {
		global $submenu;

		$this->markTestIncomplete();

		$this->assertEquals( array(), $submenu[ $this->plugin->admin->records_page_slug ] );
		$submenu[ $this->plugin->admin->records_page_slug ] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertEmpty( $submenu[ $this->plugin->admin->records_page_slug ] );

		$alerts = new Alerts( $this->plugin );
		$alerts->register_menu();
		$this->assertNotEmpty( $submenu[ $this->plugin->admin->records_page_slug ] );
	}

	public function test_display_notification_box() {
		$alerts = new Alerts( $this->plugin );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		ob_start();
		$alerts->display_notification_box( get_post( $alert->ID ) );
		$output = ob_get_contents();
		ob_end_clean();

		$len_test = strlen( $output ) > 0;
		$this->assertTrue( $len_test, 'Output length greater than zero.' );

		$field_test = strpos( $output, 'wp_stream_alert_type' ) !== -1;
		$this->assertTrue( $len_test, 'Alert type field is present.' );

		$form_test = strpos( $output, 'wp_stream_alert_type_form' ) !== -1;
		$this->assertTrue( $form_test, 'Alert type settings form is present' );
	}

	public function test_load_alerts_settings() {
		$alerts = new Alerts( $this->plugin );

		// Create administrator user to test with.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		try {
			$_POST['post_id']    = $post_id;
			$_POST['alert_type'] = 'highlight';
			$this->_handleAjax( 'load_alerts_settings' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
		$this->assertObjectHasProperty( 'data', $response );
		$this->assertObjectHasProperty( 'html', $response->data );
		$this->assertStringContainsString( 'Highlight this alert on the Stream records page.', $response->data->html );
	}

	public function test_load_alerts_settings_bad_alert_type() {
		$alerts = new Alerts( $this->plugin );

		// Create administrator user to test with.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		try {
			$_POST['post_id'] = $post_id;
			$this->_handleAjax( 'load_alerts_settings' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		// TODO: This returns an empty 'success => true' response. It should probably return a failure response instead - 400 Bad Request?
		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
		$this->assertObjectHasProperty( 'data', $response );
		$this->assertObjectHasProperty( 'html', $response->data );
		$this->assertEmpty( $response->data->html );
	}

	public function test_load_alerts_settings_missing_caps() {
		$alerts = new Alerts( $this->plugin );

		// Create a regular user for testing.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		try {
			$_POST['post_id']    = $post_id;
			$_POST['alert_type'] = 'highlight';
			$this->_handleAjax( 'load_alerts_settings' );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasProperty( 'data', $response );
		$this->assertObjectHasProperty( 'message', $response->data );
		$this->assertEquals( 'You do not have permission to do this.', $response->data->message );
	}

	public function test_display_triggers_box() {
		$alerts = new Alerts( $this->plugin );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		ob_start();
		$alerts->display_triggers_box( get_post( $alert->ID ) );
		$output = ob_get_contents();
		ob_end_clean();

		$len_test = strlen( $output ) > 0;
		$this->assertTrue( $len_test, 'Output length greater than zero.' );

		$field_test = strpos( $output, 'wp_stream_alerts_nonce' ) !== -1;
		$this->assertTrue( $len_test, 'Nonce field is present.' );
	}

	public function test_display_submit_box() {
		$alerts = new Alerts( $this->plugin );

		$data     = $this->dummy_alert_data();
		$data->ID = 0;
		$alert    = new Alert( $data, $this->plugin );
		$post_id  = $alert->save();

		ob_start();
		$alerts->display_submit_box( get_post( $alert->ID ) );
		$output = ob_get_contents();
		ob_end_clean();

		$len_test = strlen( $output ) > 0;
		$this->assertTrue( $len_test, 'Output length greater than zero.' );

		$field_test = strpos( $output, 'wp_stream_enabled' ) !== -1;
		$this->assertTrue( $len_test, 'Alert is shown as enabled.' );
	}

	public function test_get_notification_values() {
		$alerts = new Alerts( $this->plugin );

		$count  = count( $alerts->alert_types );
		$output = $alerts->get_notification_values();
		$this->assertEquals( $count, count( $output ) );
	}

	public function test_save_post_info() {
		$this->markTestIncomplete(
			'This test is incomplete'
		);
	}

	public function test_get_actions() {
		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['connector'] = '';
			$this->_handleAjax( 'get_actions' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
		$this->assertNotEmpty( $response->data );
	}
	public function test_save_new_alert_with_parent_context() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'save_alert' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
	}
	public function test_save_new_alert_with_child_context() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'save_alert' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
	}

	/**
	 * Test saving a new alert with no nonce.
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_save_new_alert_no_nonce() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';

			$this->expectException( 'WPAjaxDieStopException' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
			// Check that the exception was thrown.
			$this->assertTrue( isset( $exception ) );

			// The output should be a -1 for failure.
			$this->assertEquals( '-1', $exception->getMessage() );
		}
	}

	/**
	 * Test saving a new alert with an invalid nonce.
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_save_new_alert_invalid_nonce() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = 'invalid';

			$this->expectException( 'WPAjaxDieStopException' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
			// Check that the exception was thrown.
			$this->assertTrue( isset( $exception ) );

			// The output should be a -1 for failure.
			$this->assertEquals( '-1', $exception->getMessage() );
		}
	}

	/**
	 * Test saving a new alert with a mismatched nonce.
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_save_new_alert_mismatched_nonce() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'some_nonce' );

			$this->expectException( 'WPAjaxDieStopException' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
			// Check that the exception was thrown.
			$this->assertTrue( isset( $exception ) );

			// The output should be a -1 for failure.
			$this->assertEquals( '-1', $exception->getMessage() );
		}
	}

	/**
	 * Test saving a new alert with missing capabilities.
	 *
	 * @requires PHPUnit 5.7
	 */
	public function test_save_new_alert_missing_caps() {
		// Switch current user to a subscriber.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'save_alert' );

			$this->expectException( 'WPAjaxDieStopException' );
			$this->_handleAjax( 'save_new_alert' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
			// Check that the exception was thrown.
			$this->assertTrue( isset( $exception ) );

			// The output should be a -1 for failure.
			$this->assertEquals( '-1', $exception->getMessage() );
		}
	}

	public function test_get_new_alert_triggers_notifications() {
		// Switch current user to an administrator.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'save_alert' );
			$this->_handleAjax( 'get_new_alert_triggers_notifications' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
		}

		$response = json_decode( $this->_last_response );
		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'success', $response );
		$this->assertTrue( $response->success );
	}

	public function test_get_new_alert_triggers_notifications_missing_caps() {
		// Switch current user to a subscriber.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$alerts = new Alerts( $this->plugin );
		try {
			$_POST['wp_stream_trigger_author']  = 'me';
			$_POST['wp_stream_trigger_context'] = 'posts-post';
			$_POST['wp_stream_trigger_action']  = 'edit';
			$_POST['wp_stream_alert_type']      = 'highlight';
			$_POST['wp_stream_alerts_nonce']    = wp_create_nonce( 'save_alert' );

			$this->expectException( 'WPAjaxDieStopException' );
			$this->_handleAjax( 'get_new_alert_triggers_notifications' );
		} catch ( \WPAjaxDieContinueException $e ) {
			$exception = $e;
			// Check that the exception was thrown.
			$this->assertTrue( isset( $exception ) );

			// The output should be a -1 for failure.
			$this->assertEquals( '-1', $exception->getMessage() );
		}
	}

	private function dummy_alert_data() {
		return (object) array(
			'ID'         => 1,
			'date'       => gmdate( 'Y-m-d H:i:s' ),
			'status'     => 'wp_stream_enabled',
			'author'     => '1',
			'alert_type' => 'highlight',
			'alert_meta' => array(
				'trigger_action'  => 'activated',
				'trigger_author'  => 'administrator',
				'trigger_context' => 'plugins',
			),
		);
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
}
